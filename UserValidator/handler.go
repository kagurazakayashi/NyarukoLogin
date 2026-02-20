package main

import (
	"encoding/json"
	"time"

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

func handleLogin(req *bridgeRequest, secretKey []byte) *loginResponse {
	var loginReq loginRequest
	if err := json.Unmarshal([]byte(req.Body), &loginReq); err != nil {
		return &loginResponse{Success: false, Message: "invalid request body"}
	}

	if loginReq.Username != "user" || loginReq.Password != "pass" || loginReq.AppKey != "appkey" {
		return &loginResponse{Success: false, Message: "invalid credentials"}
	}

	now := time.Now()
	claims := tokenClaims{
		Username: loginReq.Username,
		AppKey:   loginReq.AppKey,
		JSONToken: paseto.JSONToken{
			Subject:    loginReq.Username,
			IssuedAt:   now,
			Expiration: now.Add(24 * time.Hour),
		},
	}

	token, err := paseto.NewV2().Encrypt(secretKey, claims, nil)
	if err != nil {
		return &loginResponse{Success: false, Message: "failed to generate token"}
	}

	return &loginResponse{Success: true, Token: token}
}

func notFoundResponse() *loginResponse {
	return &loginResponse{Success: false, Message: "not found"}
}
