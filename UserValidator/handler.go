package main

import (
	"crypto/rand"
	"encoding/json"
	"fmt"
	"log"
	"strconv"
	"strings"
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

func handleLogin(req *bridgeRequest, keyRing *pasetoKeyRing, cfg *pasetoConfig, mapping *tokenClaimsMapping, natsReq natsRequester, dbSubject string, dbTimeout time.Duration, extraFields map[string]string) *loginResponse {
	var loginReq loginRequest
	if err := json.Unmarshal([]byte(req.Body), &loginReq); err != nil {
		return &loginResponse{Success: false, Message: "invalid request body"}
	}

	// 向 gateway-db 資料控制微服務發送驗證請求
	// 使用 map 建構以支援附加任意額外欄位（由設定檔 db_request_extra 指定）
	dbReqMap := map[string]interface{}{
		"method": "user.login",
		"data": map[string]string{
			"username": loginReq.Username,
			"password": loginReq.Password,
		},
	}
	for k, v := range extraFields {
		dbReqMap[k] = v
	}
	dbReqJSON, err := json.Marshal(dbReqMap)
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

	// 套用自訂 claims 映射：將上游資料任意欄位寫入令牌的加密自訂 claims
	// 鍵為令牌中的 claim 名稱，值為上游回傳資料的欄位名
	// 典型用途：將 UUID 寫入令牌加密 payload，供其他微服務驗證後還原使用者身分
	// 注意：paseto.JSONToken.Set 僅接受 string 型別，複雜型別需先以 JSON 字串儲存
	for claimName, upstreamField := range mapping.CustomClaims {
		if val, ok := userDataMap[upstreamField]; ok {
			switch v := val.(type) {
			case string:
				claims.Set(claimName, v)
			default:
				// 複雜類型（陣列、物件等）以 JSON 字串形式存入
				if jsonBytes, err := json.Marshal(v); err == nil {
					claims.Set(claimName, string(jsonBytes))
				} else {
					claims.Set(claimName, fmt.Sprintf("%v", val))
				}
			}
		}
	}

	// 權限置入令牌：將使用者權限碼列表 JSON 序列化後寫入令牌
	// 供 ApiNatsBridge 在每次請求時進行本地權限檢查，無需查詢資料庫
	if len(userData.Permissions) > 0 {
		if jsonBytes, err := json.Marshal(userData.Permissions); err == nil {
			claims.Set("perm", string(jsonBytes))
		}
	}

	// UUID 置入令牌：確保撤銷檢查時始終可取得使用者 UUID
	// 不受 token_claims_mapping.sub 設定影響（sub 可能被映射為其他欄位）
	if userData.UUID != "" {
		claims.Set("uuid", userData.UUID)
	}

	token, err := encryptToken(cfg.Version, keyRing.SigningKey(), claims)
	if err != nil {
		return &loginResponse{Success: false, Message: "failed to generate token: " + err.Error()}
	}

	// 建構使用者資訊，UUID 優先（gateway-db v1.6.2+），ID 為向後相容欄位
	return &loginResponse{
		Success: true,
		Token:   token,
		User: &userInfo{
			UUID:        userData.UUID,
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

// verifyPasetoToken 核心令牌驗證邏輯，供 handleVerify 與 handleTokenVerifyDirect 共用
// 解密時會遍歷金鑰環中所有金鑰（由新到舊），任一金鑰解密成功即接受
// 回傳的 verifyResponse.Extra 包含令牌中所有非標準 claims（即自訂 claims，如 uuid）
func verifyPasetoToken(token string, keyRing *pasetoKeyRing, cfg *pasetoConfig) *verifyResponse {
	// 遍歷金鑰環中所有金鑰進行解密嘗試，由最新金鑰開始
	var claims paseto.JSONToken
	var rawClaims map[string]interface{} // 用於捕獲所有 claims（含自訂 claims）
	decrypted := false
	for _, entry := range keyRing.Keys {
		rawClaims = make(map[string]interface{})
		err := decryptToken(cfg.Version, entry.Key, token, &rawClaims)
		if err == nil {
			// 同時填入 JSONToken 以便後續使用標準欄位與 Validate()
			if err2 := decryptToken(cfg.Version, entry.Key, token, &claims); err2 == nil {
				decrypted = true
				break
			}
		}
	}

	if !decrypted {
		return &verifyResponse{Success: false, Message: "token verification failed: unable to decrypt with any known key"}
	}

	// 驗證標準 claims（過期、尚未生效等）
	if err := claims.Validate(); err != nil {
		return &verifyResponse{Success: false, Message: "token validation failed: " + err.Error()}
	}

	// 從 rawClaims 中提取自訂 claims（排除標準 claims 與系統欄位）
	standardKeys := map[string]bool{
		"iss": true, "sub": true, "aud": true,
		"iat": true, "nbf": true, "exp": true,
		"jti": true, "kid": true,
	}
	extra := make(map[string]interface{})
	for k, v := range rawClaims {
		if !standardKeys[k] {
			extra[k] = v
		}
	}

	// 從標準 claims 提取全部標準欄位
	return &verifyResponse{
		Success:   true,
		Username:  claims.Subject,
		AppKey:    claims.Audience,
		Subject:   claims.Subject,
		Issuer:    claims.Issuer,
		IssuedAt:  claims.IssuedAt.Format(time.RFC3339),
		NotBefore: claims.NotBefore.Format(time.RFC3339),
		Expires:   claims.Expiration.Format(time.RFC3339),
		Jti:       claims.Jti,
		Extra:     extra,
	}
}

// handleVerify 核實 PASETO 令牌的有效性（HTTP 橋接層介面）
// 從 bridgeRequest.Body 中解析令牌後，呼叫 verifyPasetoToken 進行驗證
func handleVerify(req *bridgeRequest, keyRing *pasetoKeyRing, cfg *pasetoConfig) *verifyResponse {
	var verifyReq verifyRequest
	if err := json.Unmarshal([]byte(req.Body), &verifyReq); err != nil {
		return &verifyResponse{Success: false, Message: "invalid request body"}
	}

	if verifyReq.Token == "" {
		return &verifyResponse{Success: false, Message: "token is required"}
	}

	return verifyPasetoToken(verifyReq.Token, keyRing, cfg)
}

// 令牌核實錯誤代碼（直接 NATS 簡單模式回傳值）
const (
	errCodeTokenValid    = 0 // 令牌有效
	errCodeInvalidReq    = 1 // 請求格式無效
	errCodeEmptyToken    = 2 // 令牌為空
	errCodeTokenDecrypt  = 3 // 令牌無法解密（金鑰不符、格式錯誤、被竄改）
	errCodeTokenValidate = 4 // 令牌時效驗證失敗（過期、尚未生效）
)

// handleTokenVerifyDirect 直接 NATS 介面的令牌核實處理
// 支援四種資訊層級與選填 tag：
//
//	發送格式：  [tag|]level|令牌
//	  level=0 → 精簡模式（回傳整數錯誤代碼，等同舊版 ! 模式）
//	  level=1 → 系統層級（僅回傳 success 與 message）
//	  level=2 → 系統 + token claims（success、message、username、app、sub、iat、exp）
//	  level=3 → 完整模式（回傳完整 verifyResponse JSON，等同舊版 ? 模式）
//
// 回覆格式：若提供 tag，則以 "tag|資料" 格式前綴回覆；無 tag 則直接回覆資料
//
// 層級選擇指南：
//
//	0 — 僅需判斷有效／無效，最小傳輸量
//	1 — 需要知道驗證是否成功即可
//	2 — 需要令牌中的使用者身分與時效資訊
//	3 — 需要所有資訊（含上游資料庫的使用者詳細資料）
func handleTokenVerifyDirect(message string, keyRing *pasetoKeyRing, cfg *pasetoConfig) string {
	var tag, token string
	var level int

	// 從右側尋找最後一個 | 以分隔 token 與前綴
	lastPipe := strings.LastIndex(message, "|")
	if lastPipe == -1 {
		errResp := &verifyResponse{Success: false, Message: "invalid request format: missing level separator"}
		resp, _ := json.Marshal(errResp)
		return string(resp)
	}

	token = message[lastPipe+1:]
	prefix := message[:lastPipe]

	// 在前綴中尋找層級分隔符：tag|level 或僅 level
	levelPipe := strings.LastIndex(prefix, "|")
	var levelStr string
	if levelPipe == -1 {
		// 格式：level|token（無 tag）
		levelStr = prefix
	} else {
		// 格式：tag|level|token
		tag = prefix[:levelPipe]
		levelStr = prefix[levelPipe+1:]
	}

	// 解析層級（必須為 0-3 的整數）
	parsedLevel, err := strconv.Atoi(levelStr)
	if err != nil || parsedLevel < 0 || parsedLevel > 3 {
		errResp := &verifyResponse{Success: false, Message: "invalid level: " + levelStr}
		resp, _ := json.Marshal(errResp)
		return formatTagResponse(tag, string(resp))
	}
	level = parsedLevel

	// 令牌不得為空
	if token == "" {
		if level == 0 {
			return formatTagResponse(tag, fmt.Sprintf("%d", errCodeEmptyToken))
		}
		return buildLevelResponse(level, tag, &verifyResponse{Success: false, Message: "token is required"})
	}

	// 核心驗證邏輯
	result := verifyPasetoToken(token, keyRing, cfg)

	// 撤銷檢查：令牌解密成功後檢查該使用者是否有待撤銷記錄
	// 若 token.iat < revokedSince，表示令牌在權限變動前簽發，應失效
	if result.Success {
		uuidStr := extractUUID(result)
		if uuidStr != "" {
			if revokedAt, found := revokedMap.Load(uuidStr); found {
				iatTime, err := time.Parse(time.RFC3339, result.IssuedAt)
				if err == nil && iatTime.Unix() < revokedAt.(int64) {
					result = &verifyResponse{
						Success: false,
						Message: "token revoked: user permissions have changed, please re-login",
					}
				}
			}
		}
	}

	return buildLevelResponse(level, tag, result)
}

// buildLevelResponse 依指定層級與驗證結果建構回覆內容
func buildLevelResponse(level int, tag string, result *verifyResponse) string {
	switch level {
	case 0:
		return buildLevel0Response(tag, result)
	case 1:
		return buildLevel1Response(tag, result)
	case 2:
		return buildLevel2Response(tag, result)
	case 3:
		return buildLevel3Response(tag, result)
	default:
		return buildLevel0Response(tag, result)
	}
}

// buildLevel0Response 精簡模式：僅回傳整數錯誤代碼（等同舊版 ! 模式）
func buildLevel0Response(tag string, result *verifyResponse) string {
	if result.Success {
		return formatTagResponse(tag, fmt.Sprintf("%d", errCodeTokenValid))
	}
	if strings.HasPrefix(result.Message, "token verification failed") {
		return formatTagResponse(tag, fmt.Sprintf("%d", errCodeTokenDecrypt))
	}
	if strings.HasPrefix(result.Message, "token validation failed") {
		return formatTagResponse(tag, fmt.Sprintf("%d", errCodeTokenValidate))
	}
	return formatTagResponse(tag, fmt.Sprintf("%d", errCodeInvalidReq))
}

// buildLevel1Response 系統層級：僅回傳 success 與 message
func buildLevel1Response(tag string, result *verifyResponse) string {
	resp := map[string]interface{}{
		"success": result.Success,
	}
	if !result.Success {
		resp["message"] = result.Message
	}
	data, _ := json.Marshal(resp)
	return formatTagResponse(tag, string(data))
}

// buildLevel2Response 系統 + token claims：回傳全部標準 claims 與自訂 claims（success、message、username、app、sub、iss、iat、nbf、exp、jti，以及所有 extra 自訂 claims）
func buildLevel2Response(tag string, result *verifyResponse) string {
	resp := map[string]interface{}{
		"success": result.Success,
	}
	if !result.Success {
		resp["message"] = result.Message
	} else {
		resp["username"] = result.Username
		resp["app"] = result.AppKey
		resp["sub"] = result.Subject
		resp["iss"] = result.Issuer
		resp["iat"] = result.IssuedAt
		resp["nbf"] = result.NotBefore
		resp["exp"] = result.Expires
		resp["jti"] = result.Jti
		// 合併自訂 claims（如 uuid），供呼叫方按設定提取
		for k, v := range result.Extra {
			resp[k] = v
		}
	}
	data, _ := json.Marshal(resp)
	return formatTagResponse(tag, string(data))
}

// buildLevel3Response 完整模式：回傳完整 verifyResponse JSON（等同舊版 ? 模式，含 user 物件）
func buildLevel3Response(tag string, result *verifyResponse) string {
	data, _ := json.Marshal(result)
	return formatTagResponse(tag, string(data))
}

// formatTagResponse 若 tag 非空則加上 "tag|" 前綴，否則直接回傳內容
func formatTagResponse(tag, body string) string {
	if tag != "" {
		return tag + "|" + body
	}
	return body
}

// extractUUID 從驗證結果中提取使用者 UUID
// 優先從 Extra 自訂 claims 中取得 uuid，其次使用 Subject（sub claim）
func extractUUID(result *verifyResponse) string {
	if uuidVal, ok := result.Extra["uuid"]; ok {
		return fmt.Sprintf("%v", uuidVal)
	}
	if result.Subject != "" {
		return result.Subject
	}
	return ""
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


