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
