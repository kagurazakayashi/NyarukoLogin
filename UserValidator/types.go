package main

import "encoding/json"

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

// verifyResponse 令牌核實回應結構，回傳核實結果、令牌內容與使用者資訊
type verifyResponse struct {
	Success  bool      `json:"success"`            // 核實是否成功
	Username string    `json:"username,omitempty"` // 令牌所屬用戶名稱
	AppKey   string    `json:"appkey,omitempty"`   // 令牌對應的應用程式金鑰
	Subject  string    `json:"sub,omitempty"`      // 令牌主體 (subject)
	IssuedAt string    `json:"iat,omitempty"`      // 令牌簽發時間 (ISO 8601)
	Expires  string    `json:"exp,omitempty"`      // 令牌到期時間 (ISO 8601)
	Message  string    `json:"message,omitempty"`  // 錯誤訊息（僅核實失敗時存在）
	User     *userInfo `json:"user,omitempty"`     // 使用者資訊（核實成功時）
}

// dbGatewayRequest 發送給 gateway-db 資料控制微服務的請求結構
type dbGatewayRequest struct {
	Method string      `json:"method"` // 操作方法，如 "user.login"
	Data   interface{} `json:"data"`   // 請求資料承載
}

// dbGatewayResponse gateway-db 回應識別（同時相容包裹與原始格式）
// 包裹成功：{"success":true,"data":{"id":1,"username":"admin",...}}
// 包裹失敗：{"success":false,"error":"..."}
// 原始成功：{"id":1,"username":"admin",...}
// 原始失敗：{"err":"invalid username or password","errcode":"120000"}
type dbGatewayResponse struct {
	Success bool            `json:"success"` // 操作是否成功（包裹格式）
	Data    json.RawMessage `json:"data"`    // 回應資料承載（包裹格式，成功時）
	Error   string          `json:"error"`   // 錯誤訊息（包裹格式，失敗時）
	Err     string          `json:"err"`     // 錯誤訊息（原始格式，失敗時）
}

// dbGatewayUserData 由 gateway-db user.login 成功時回傳的使用者資料
// 此資料位於 dbGatewayResponse.Data 內部，非頂層回應
type dbGatewayUserData struct {
	ID          int           `json:"id"`          // 使用者內部 ID
	Username    string        `json:"username"`    // 使用者名稱
	Nickname    string        `json:"nickname"`    // 顯示暱稱
	Group       interface{}   `json:"group"`       // 所屬群組資訊
	Permissions []interface{} `json:"permissions"` // 權限列表
	CreatedAt   string        `json:"created_at"`  // 建立時間
	UpdatedAt   string        `json:"updated_at"`  // 最後更新時間
}

// userInfo 提供給前端的使用者資訊，含群組與權限
type userInfo struct {
	ID          int           `json:"id"`                    // 使用者內部 ID
	Username    string        `json:"username"`              // 使用者名稱
	Nickname    string        `json:"nickname,omitempty"`    // 顯示暱稱
	Group       interface{}   `json:"group,omitempty"`       // 所屬群組資訊
	Permissions []interface{} `json:"permissions,omitempty"` // 權限列表
	CreatedAt   string        `json:"created_at,omitempty"`  // 建立時間
	UpdatedAt   string        `json:"updated_at,omitempty"`  // 最後更新時間
}


