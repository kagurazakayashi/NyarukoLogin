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
	Username string `json:"username"`
	Password string `json:"password"`
	AppKey   string `json:"appkey"`
}

type loginResponse struct {
	Success bool      `json:"success"`
	Token   string    `json:"token,omitempty"`
	Message string    `json:"message,omitempty"`
	User    *userInfo `json:"user,omitempty"`
}

func handleLogin(req *bridgeRequest, secretKey []byte, cfg *pasetoConfig, natsReq natsRequester, dbSubject string, dbTimeout time.Duration) *loginResponse {
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

	ttl, err := time.ParseDuration(cfg.TokenTTL)
	if err != nil || ttl <= 0 {
		ttl = 24 * time.Hour
	}

	now := time.Now()
	claims := paseto.JSONToken{
		Subject:    loginReq.Username,
		IssuedAt:   now,
		Expiration: now.Add(ttl),
	}
	claims.Set("username", loginReq.Username)
	claims.Set("appkey", loginReq.AppKey)

	if cfg.Issuer != "" {
		claims.Issuer = cfg.Issuer
	}
	if cfg.Audience != "" {
		claims.Audience = cfg.Audience
	}

	if nbfOffset, err := time.ParseDuration(cfg.NotBefore); err == nil && nbfOffset > 0 {
		claims.NotBefore = now.Add(nbfOffset)
	}

	if cfg.EnableJTI {
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

// handleVerify 核實 PASETO 令牌的有效性，解密後進行時效與身分驗證，
// 成功時向 gateway-db 查詢使用者資訊（含權限）一併回傳
func handleVerify(req *bridgeRequest, secretKey []byte, cfg *pasetoConfig, natsReq natsRequester, dbSubject string, dbTimeout time.Duration) *verifyResponse {
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

	username := claims.Get("username")
	resp := &verifyResponse{
		Success:  true,
		Username: username,
		AppKey:   claims.Get("appkey"),
		Subject:  claims.Subject,
		IssuedAt: claims.IssuedAt.Format(time.RFC3339),
		Expires:  claims.Expiration.Format(time.RFC3339),
	}

	// 向 gateway-db 查詢使用者資訊
	ui := fetchUserInfo(username, natsReq, dbSubject, dbTimeout)
	if ui != nil {
		resp.User = ui
	}

	return resp
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

// fetchUserInfo 向 gateway-db 查詢使用者資訊（含權限），用於 /auth/verify 成功時補充回傳
func fetchUserInfo(username string, natsReq natsRequester, dbSubject string, dbTimeout time.Duration) *userInfo {
	// 呼叫 user.get 取得基本資訊
	userReq, _ := json.Marshal(dbGatewayRequest{
		Method: "user.get",
		Data:   map[string]string{"username": username},
	})
	userRespStr, err := natsReq(dbSubject, string(userReq), dbTimeout)
	if err != nil {
		log.Printf("[NATS] user.get failed for %s: %v", username, err)
		return nil
	}

	var ui userInfo
	if !parseGatewayData(userRespStr, &ui) {
		return nil
	}
	ui.Username = username

	// 呼叫 permission.user.get 取得權限
	permReq, _ := json.Marshal(dbGatewayRequest{
		Method: "permission.user.get",
		Data:   map[string]int64{"user_id": int64(ui.ID)},
	})
	permRespStr, err := natsReq(dbSubject, string(permReq), dbTimeout)
	if err != nil {
		log.Printf("[NATS] permission.user.get failed for %s: %v", username, err)
		// 權限查詢失敗不影響核實結果，仍回傳基本資訊
		return &ui
	}

	var permData struct {
		Permissions []interface{} `json:"permissions"`
	}
	if parseGatewayData(permRespStr, &permData) {
		ui.Permissions = permData.Permissions
	}

	return &ui
}

// parseGatewayData 解析 gateway-db 回應（自動相容包裹與原始格式），將資料填入 target
func parseGatewayData(respStr string, target interface{}) bool {
	var dbResp dbGatewayResponse
	if err := json.Unmarshal([]byte(respStr), &dbResp); err != nil {
		return false
	}
	if dbResp.Data != nil || dbResp.Error != "" {
		if dbResp.Data == nil {
			return false
		}
		return json.Unmarshal(dbResp.Data, target) == nil
	}
	return json.Unmarshal([]byte(respStr), target) == nil
}
