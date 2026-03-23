package main

import (
	"encoding/json"
	"fmt"
	"log"
	"time"

	"github.com/google/uuid"
	"github.com/o1egl/paseto"
)

// natsRequester NATS 請求函式型態，用於向其他微服務發送 Request/Reply 訊息
type natsRequester func(subject string, message string, timeout time.Duration) (string, error)

type loginRequest struct {
	Username  string `json:"username"`   // 使用者名稱
	Password  string `json:"password"`   // 密碼
	App       string `json:"app"`       // 應用程式識別名稱
	Expires   int64  `json:"expires"`  // 令牌有效秒數（選填，0 表示使用設定檔預設值，最大值受限於 max_token_ttl）
}

type loginResponse struct {
	Success bool      `json:"success"`
	Token   string    `json:"token,omitempty"`
	Message string    `json:"message,omitempty"`
	User    *userInfo `json:"user,omitempty"`
}

func handleLogin(req *bridgeRequest, secretKey []byte, cfg *pasetoConfig, mapping *tokenClaimsMapping, natsReq natsRequester, dbSubject string, dbTimeout time.Duration) *loginResponse {
	var loginReq loginRequest
	if err := json.Unmarshal([]byte(req.Body), &loginReq); err != nil {
		return &loginResponse{Success: false, Message: "invalid request body"}
	}

	// 向 gateway-db 資料控制微服務發送驗證請求
	dbReq := dbGatewayRequest{
		Method: "user.login",
		Data: map[string]string{
			"username": loginReq.Username,
			"password": loginReq.Password,
		},
	}
	dbReqJSON, err := json.Marshal(dbReq)
	if err != nil {
		return &loginResponse{Success: false, Message: "failed to marshal db request"}
	}

	dbRespStr, err := natsReq(dbSubject, string(dbReqJSON), dbTimeout)
	if err != nil {
		log.Printf("[NATS] db_request failed for user=%s: %v", loginReq.Username, err)
		return &loginResponse{Success: false, Message: "db request failed: " + err.Error()}
	}

	log.Printf("[NATS] db_request raw response: %s", dbRespStr)

	// 解析 gateway-db 回應，自動偵測格式：
	//   包裹成功：{"success":true,"data":{...}}
	//   包裹失敗：{"success":false,"error":"..."}
	//   原始失敗：{"err":"...","errcode":"..."}
	//   原始成功：{"id":1,"username":"admin",...}
	var dbResp dbGatewayResponse
	if err := json.Unmarshal([]byte(dbRespStr), &dbResp); err != nil {
		log.Printf("[NATS] db_request unmarshal error for user=%s: %v", loginReq.Username, err)
		return &loginResponse{Success: false, Message: "invalid db response format"}
	}

	// 原始錯誤格式
	if dbResp.Err != "" {
		return &loginResponse{Success: false, Message: dbResp.Err}
	}

	var userData dbGatewayUserData
	if dbResp.Data != nil || dbResp.Error != "" {
		// 包裹格式
		if !dbResp.Success {
			return &loginResponse{Success: false, Message: dbResp.Error}
		}
		if err := json.Unmarshal(dbResp.Data, &userData); err != nil {
			log.Printf("[NATS] db_request data unmarshal error for user=%s: %v", loginReq.Username, err)
			return &loginResponse{Success: false, Message: "invalid db response data"}
		}
	} else {
		// 原始成功格式
		if err := json.Unmarshal([]byte(dbRespStr), &userData); err != nil {
			log.Printf("[NATS] db_request unmarshal error for user=%s: %v", loginReq.Username, err)
			return &loginResponse{Success: false, Message: "invalid db response"}
		}
	}


	// 確認回應中包含必要的使用者名稱
	if userData.Username == "" {
		return &loginResponse{Success: false, Message: "invalid db response: missing username"}
	}

	// 解析上游回傳的原始資料為 map，用於 claims 欄位動態映射
	var userDataMap map[string]interface{}
	if dbResp.Data != nil || dbResp.Error != "" {
		// 包裹格式：從 Data 欄位解析
		if err := json.Unmarshal(dbResp.Data, &userDataMap); err != nil {
			log.Printf("[TOKEN] 無法解析上游資料為 map (包裹格式): %v", err)
		}
	} else {
		// 原始格式：從整筆回應解析
		if err := json.Unmarshal([]byte(dbRespStr), &userDataMap); err != nil {
			log.Printf("[TOKEN] 無法解析上游資料為 map (原始格式): %v", err)
		}
	}

	// 解析預設 TTL
	defaultTTL, err := time.ParseDuration(cfg.TokenTTL)
	if err != nil || defaultTTL <= 0 {
		defaultTTL = 24 * time.Hour
	}

	// 解析 TTL 上限（未設定時以預設 TTL 為上限）
	maxTTL := defaultTTL
	if cfg.MaxTokenTTL != "" {
		if parsed, err := time.ParseDuration(cfg.MaxTokenTTL); err == nil && parsed > 0 {
			maxTTL = parsed
		}
	}

	// 決定最終 TTL：若請求提供了 expires 則以其為準，但不得超過上限
	var ttl time.Duration
	if loginReq.Expires > 0 {
		ttl = time.Duration(loginReq.Expires) * time.Second
		if ttl > maxTTL {
			log.Printf("[TOKEN] expires=%d (%s) 超過上限 %s，已裁剪至上限", loginReq.Expires, ttl, maxTTL)
			ttl = maxTTL
		}
	} else {
		ttl = defaultTTL
	}

	now := time.Now()
	claims := paseto.JSONToken{
		IssuedAt:   now,
		Expiration: now.Add(ttl),
	}

	// 登入請求的預設自訂 claims（可被上游映射覆蓋）
	claims.Set("username", loginReq.Username)
	claims.Set("app", loginReq.App)

	// 預設 sub 來自登入請求的使用者名稱（可被上游映射覆蓋）
	claims.Subject = loginReq.Username

	// 套用上游資料欄位映射：標準 claims
	if mapping.Sub != "" {
		if val, ok := userDataMap[mapping.Sub]; ok {
			claims.Subject = fmt.Sprintf("%v", val)
		}
	}
	if mapping.Issuer != "" {
		if val, ok := userDataMap[mapping.Issuer]; ok {
			claims.Issuer = fmt.Sprintf("%v", val)
		}
	}
	if mapping.Audience != "" {
		if val, ok := userDataMap[mapping.Audience]; ok {
			claims.Audience = fmt.Sprintf("%v", val)
		}
	}
	if mapping.Jti != "" {
		if val, ok := userDataMap[mapping.Jti]; ok {
			claims.Jti = fmt.Sprintf("%v", val)
		}
	}

	// 套用上游資料欄位映射：自訂 claims（會覆蓋登入請求中同名的自訂欄位）
	for claimName, dbKey := range mapping.Custom {
		if val, ok := userDataMap[dbKey]; ok {
			claims.Set(claimName, fmt.Sprintf("%v", val))
		}
	}

	// 設定檔中的靜態 claims（僅在未從上游映射時生效）
	if claims.Issuer == "" && cfg.Issuer != "" {
		claims.Issuer = cfg.Issuer
	}
	if claims.Audience == "" && cfg.Audience != "" {
		claims.Audience = cfg.Audience
	}

	// 時間相關 claims
	if nbfOffset, err := time.ParseDuration(cfg.NotBefore); err == nil && nbfOffset > 0 {
		claims.NotBefore = now.Add(nbfOffset)
	}

	// JTI：若未從上游映射，則依設定自動生成
	if claims.Jti == "" && cfg.EnableJTI {
		claims.Jti = uuid.New().String()
	}

	var footer interface{}
	if cfg.Footer != "" {
		footer = cfg.Footer
	}

	token, err := encryptToken(cfg.Version, secretKey, claims, footer)
	if err != nil {
		return &loginResponse{Success: false, Message: "failed to generate token: " + err.Error()}
	}

	return &loginResponse{
		Success: true,
		Token:   token,
		User: &userInfo{
			ID:          userData.ID,
			Username:    userData.Username,
			Nickname:    userData.Nickname,
			Group:       userData.Group,
			Permissions: userData.Permissions,
			CreatedAt:   userData.CreatedAt,
			UpdatedAt:   userData.UpdatedAt,
		},
	}
}

// encryptToken 依據指定的 PASETO 版本對 claims 進行加密簽發
func encryptToken(version string, key []byte, payload interface{}, footer interface{}) (string, error) {
	switch version {
	case "v1":
		return paseto.NewV1().Encrypt(key, payload, footer)
	case "v2", "":
		return paseto.NewV2().Encrypt(key, payload, footer)
	default:
		return "", fmt.Errorf("unsupported PASETO version: %s", version)
	}
}

// handleVerify 核實 PASETO 令牌的有效性，解密後進行時效與身分驗證，不回傳使用者資訊（不再查詢資料庫）
func handleVerify(req *bridgeRequest, secretKey []byte, cfg *pasetoConfig) *verifyResponse {
	var verifyReq verifyRequest
	if err := json.Unmarshal([]byte(req.Body), &verifyReq); err != nil {
		return &verifyResponse{Success: false, Message: "invalid request body"}
	}

	if verifyReq.Token == "" {
		return &verifyResponse{Success: false, Message: "token is required"}
	}

	// 解密令牌並還原 claims
	var claims paseto.JSONToken
	if err := decryptToken(cfg.Version, secretKey, verifyReq.Token, &claims, nil); err != nil {
		return &verifyResponse{Success: false, Message: "token verification failed: " + err.Error()}
	}

	// 驗證標準 claims（過期、尚未生效等）
	if err := claims.Validate(); err != nil {
		return &verifyResponse{Success: false, Message: "token validation failed: " + err.Error()}
	}

	return &verifyResponse{
		Success:  true,
		Username: claims.Get("username"),
		AppKey:   claims.Get("app"),
		Subject:  claims.Subject,
		IssuedAt: claims.IssuedAt.Format(time.RFC3339),
		Expires:  claims.Expiration.Format(time.RFC3339),
	}
}

// decryptToken 依據指定的 PASETO 版本對令牌進行解密
func decryptToken(version string, key []byte, token string, payload interface{}, footer interface{}) error {
	switch version {
	case "v1":
		return paseto.NewV1().Decrypt(token, key, payload, footer)
	case "v2", "":
		return paseto.NewV2().Decrypt(token, key, payload, footer)
	default:
		return fmt.Errorf("unsupported PASETO version: %s", version)
	}
}

func notFoundResponse() *loginResponse {
	return &loginResponse{Success: false, Message: "not found"}
}


