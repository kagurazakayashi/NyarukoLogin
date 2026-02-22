package main

import (
	"encoding/json"
	"fmt"
	"time"

	"github.com/google/uuid"
	"github.com/o1egl/paseto"
)

type loginRequest struct {
	Username string `json:"username"`
	Password string `json:"password"`
	AppKey   string `json:"appkey"`
}

type loginResponse struct {
	Success bool   `json:"success"`
	Token   string `json:"token,omitempty"`
	Message string `json:"message,omitempty"`
}

type tokenClaims struct {
	Username string `json:"username"`
	AppKey   string `json:"appkey"`
	paseto.JSONToken
}

func handleLogin(req *bridgeRequest, secretKey []byte, cfg *pasetoConfig) *loginResponse {
	var loginReq loginRequest
	if err := json.Unmarshal([]byte(req.Body), &loginReq); err != nil {
		return &loginResponse{Success: false, Message: "invalid request body"}
	}

	if loginReq.Username != "user" || loginReq.Password != "pass" || loginReq.AppKey != "appkey" {
		return &loginResponse{Success: false, Message: "invalid credentials"}
	}

	ttl, err := time.ParseDuration(cfg.TokenTTL)
	if err != nil || ttl <= 0 {
		ttl = 24 * time.Hour
	}

	now := time.Now()
	claims := tokenClaims{
		Username: loginReq.Username,
		AppKey:   loginReq.AppKey,
		JSONToken: paseto.JSONToken{
			Subject:    loginReq.Username,
			IssuedAt:   now,
			Expiration: now.Add(ttl),
		},
	}

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

	return &loginResponse{Success: true, Token: token}
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

// handleVerify 核實 PASETO 令牌的有效性，解密後進行時效與身分驗證
func handleVerify(req *bridgeRequest, secretKey []byte, cfg *pasetoConfig) *verifyResponse {
	var verifyReq verifyRequest
	if err := json.Unmarshal([]byte(req.Body), &verifyReq); err != nil {
		return &verifyResponse{Success: false, Message: "invalid request body"}
	}

	if verifyReq.Token == "" {
		return &verifyResponse{Success: false, Message: "token is required"}
	}

	// 解密令牌並還原 claims
	var claims tokenClaims
	if err := decryptToken(cfg.Version, secretKey, verifyReq.Token, &claims, nil); err != nil {
		return &verifyResponse{Success: false, Message: "token verification failed: " + err.Error()}
	}

	// 驗證標準 claims（過期、尚未生效等）
	if err := claims.Validate(); err != nil {
		return &verifyResponse{Success: false, Message: "token validation failed: " + err.Error()}
	}

	return &verifyResponse{
		Success:  true,
		Username: claims.Username,
		AppKey:   claims.AppKey,
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
