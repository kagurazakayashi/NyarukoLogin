package main

import (
	"crypto/rand"
	"encoding/json"
	"fmt"
	"log"
	"time"

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

func handleLogin(req *bridgeRequest, keyRing *pasetoKeyRing, cfg *pasetoConfig, mapping *tokenClaimsMapping, natsReq natsRequester, dbSubject string, dbTimeout time.Duration) *loginResponse {
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
		if err := json.Unmarshal(dbResp.Data, &userDataMap); err != nil {
			log.Printf("[TOKEN] 無法解析上游資料為 map (包裹格式): %v", err)
		}
	} else {
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

	// 產生 32 字元高熵英數字隨機字串作為 jti
	jti, err := generateJTI(32)
	if err != nil {
		return &loginResponse{Success: false, Message: "failed to generate jti: " + err.Error()}
	}

	// 建立標準 claims（預設值來自登入請求）
	claims := paseto.JSONToken{
		Issuer:     req.Path,         // iss: 請求路由路徑
		Subject:    loginReq.Username, // sub: 使用者名稱（可被上游映射覆蓋）
		Audience:   loginReq.App,     // aud: 應用程式識別名稱（可被上游映射覆蓋）
		IssuedAt:   now,              // iat: 簽發時間
		NotBefore:  now,              // nbf: 立即生效
		Expiration: now.Add(ttl),     // exp: 過期時間
		Jti:        jti,              // jti: 32 字元高熵隨機字串
	}

	// kid: 簽發金鑰的時間戳，用於金鑰輪替時識別
	if entry := keyRing.SigningEntry(); entry != nil {
		claims.Set("kid", fmt.Sprintf("%d", entry.Timestamp))
	}

	// 套用上游資料欄位映射（僅 sub / iss / aud 三個標準 claims 可被上游覆蓋）
	// iat、nbf、exp、jti、kid 為系統計算，不可透過 mapping 覆蓋
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

	token, err := encryptToken(cfg.Version, keyRing.SigningKey(), claims)
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

// jtiCharSet 用於產生 jti 的字元集：大小寫英數字（排除易混淆字元 0/O/1/l/I）
const jtiCharSet = "abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789"

// generateJTI 產生指定長度的高熵隨機英數字字串
func generateJTI(length int) (string, error) {
	bytes := make([]byte, length)
	if _, err := rand.Read(bytes); err != nil {
		return "", err
	}
	for i := range bytes {
		bytes[i] = jtiCharSet[int(bytes[i])%len(jtiCharSet)]
	}
	return string(bytes), nil
}

// encryptToken 依據指定的 PASETO 版本對 claims 進行加密簽發
func encryptToken(version string, key []byte, payload interface{}) (string, error) {
	switch version {
	case "v1":
		return paseto.NewV1().Encrypt(key, payload, nil)
	case "v2", "":
		return paseto.NewV2().Encrypt(key, payload, nil)
	default:
		return "", fmt.Errorf("unsupported PASETO version: %s", version)
	}
}

// handleVerify 核實 PASETO 令牌的有效性，解密後進行時效與身分驗證
// 解密時會遍歷金鑰環中所有金鑰（由新到舊），任一金鑰解密成功即接受
func handleVerify(req *bridgeRequest, keyRing *pasetoKeyRing, cfg *pasetoConfig) *verifyResponse {
	var verifyReq verifyRequest
	if err := json.Unmarshal([]byte(req.Body), &verifyReq); err != nil {
		return &verifyResponse{Success: false, Message: "invalid request body"}
	}

	if verifyReq.Token == "" {
		return &verifyResponse{Success: false, Message: "token is required"}
	}

	// 遍歷金鑰環中所有金鑰進行解密嘗試，由最新金鑰開始
	var claims paseto.JSONToken
	decrypted := false
	for _, entry := range keyRing.Keys {
		err := decryptToken(cfg.Version, entry.Key, verifyReq.Token, &claims)
		if err == nil {
			decrypted = true
			break
		}
	}

	if !decrypted {
		return &verifyResponse{Success: false, Message: "token verification failed: unable to decrypt with any known key"}
	}

	// 驗證標準 claims（過期、尚未生效等）
	if err := claims.Validate(); err != nil {
		return &verifyResponse{Success: false, Message: "token validation failed: " + err.Error()}
	}

	// 從標準 claims 提取身分資訊（sub = username, aud = app）
	return &verifyResponse{
		Success:  true,
		Username: claims.Subject,
		AppKey:   claims.Audience,
		Subject:  claims.Subject,
		IssuedAt: claims.IssuedAt.Format(time.RFC3339),
		Expires:  claims.Expiration.Format(time.RFC3339),
	}
}

// decryptToken 依據指定的 PASETO 版本對令牌進行解密
func decryptToken(version string, key []byte, token string, payload interface{}) error {
	switch version {
	case "v1":
		return paseto.NewV1().Decrypt(token, key, payload, nil)
	case "v2", "":
		return paseto.NewV2().Decrypt(token, key, payload, nil)
	default:
		return fmt.Errorf("unsupported PASETO version: %s", version)
	}
}

func notFoundResponse() *loginResponse {
	return &loginResponse{Success: false, Message: "not found"}
}


