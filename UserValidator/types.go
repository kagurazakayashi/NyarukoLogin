package main

// bridgeRequest ApiNatsBridge 轉發的 HTTP 請求結構
type bridgeRequest struct {
	Method     string            `json:"method"`
	Path       string            `json:"path"`
	Headers    map[string]string `json:"headers"`
	Cookies    map[string]string `json:"cookies"`
	RemoteAddr string            `json:"remote_addr"`
	IP         string            `json:"ip"`
	Params     map[string]string `json:"params"`
	Body       string            `json:"body"`
}

// bridgeResponse 回覆給 ApiNatsBridge 的 HTTP 回應結構
type bridgeResponse struct {
	StatusCode int               `json:"status_code"`
	Headers    map[string]string `json:"headers"`
	Body       string            `json:"body"`
}

// verifyRequest 令牌核實請求結構，含待驗證的 PASETO 令牌
type verifyRequest struct {
	Token string `json:"token"` // 待核實的 PASETO 令牌字串
}

// verifyResponse 令牌核實回應結構，回傳核實結果與令牌內容
type verifyResponse struct {
	Success  bool   `json:"success"`            // 核實是否成功
	Username string `json:"username,omitempty"` // 令牌所屬用戶名稱
	AppKey   string `json:"appkey,omitempty"`   // 令牌對應的應用程式金鑰
	Subject  string `json:"sub,omitempty"`      // 令牌主體 (subject)
	IssuedAt string `json:"iat,omitempty"`      // 令牌簽發時間 (ISO 8601)
	Expires  string `json:"exp,omitempty"`      // 令牌到期時間 (ISO 8601)
	Message  string `json:"message,omitempty"`  // 錯誤訊息（僅核實失敗時存在）
}
