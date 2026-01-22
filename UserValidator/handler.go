package main

import (
	"strconv"
	"time"
)

// pingResponse ping 請求的回應結構（暫時使用，後續將改為登入狀態驗證結果）
type pingResponse struct {
	Pong       int64  `json:"pong"`
	IP         string `json:"ip"`
	ServerTime int64  `json:"servertime"`
}

// handlePing 處理 ping 請求
// 從 params 中取得客戶端時間戳，計算與伺服器時間的差值
func handlePing(req *bridgeRequest) *pingResponse {
	var clientTimestampMs int64

	if ts, ok := req.Params["timestamp"]; ok && ts != "" {
		parsed, err := strconv.ParseInt(ts, 10, 64)
		if err == nil {
			clientTimestampMs = parsed
		}
	}

	nowMs := time.Now().UnixMilli()

	var pong int64
	if clientTimestampMs > 0 {
		pong = nowMs - clientTimestampMs
	} else {
		pong = nowMs
	}

	return &pingResponse{
		Pong:       pong,
		IP:         req.IP,
		ServerTime: nowMs,
	}
}
