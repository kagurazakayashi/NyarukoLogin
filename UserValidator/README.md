# UserValidator - 登入狀態驗證器

NyarukoLogin 3 的登入狀態驗證服務模組。透過 NATS 接收由 ApiNatsBridge 轉發的 HTTP 請求，
提供使用者憑證驗證、PASETO 令牌簽發與令牌核實功能。

## 架構

```
用戶端 (HTTP POST)
    │
    ▼
ApiNatsBridge (HTTP → NATS 橋接，預設埠 9080)
    │  NATS Publish（主題: /auth/login 或 /auth/verify，按路徑路由）
    ▼
UserValidator (本服務)
    │  按主題名路由: /auth/login  → 簽發令牌
    │              /auth/verify → 核實令牌
    ▼
ApiNatsBridge ← 回應 JSON
    │
    ▼
用戶端 (HTTP Response)
```

UserValidator 不直接監聽 HTTP 埠，而是作為 NATS 微服務執行。
對外 HTTP 存取由 ApiNatsBridge 統一代理，本服務僅需訂閱 NATS 主題即可。

## NATS 通訊交互示例

以下展示各端點完整的 NATS 訊息交換流程。

### 訊息封裝格式

ApiNatsBridge 與 UserValidator 之間的請求/回應採用固定 JSON 結構傳輸。

**ApiNatsBridge → UserValidator（bridgeRequest）**

```json
{
  "body": "{...}",
  "ip": "192.168.1.45",
  "path": "/auth/login",
  "method": "POST",
  "headers": {
    "Content-Type": "application/json"
  },
  "remote_addr": "192.168.1.45:54321"
}
```

| 欄位 | 說明 |
|------|------|
| `body` | HTTP 請求主體的原始字串，由本服務自行解析 |
| `ip` | 用戶端 IP 位址 |
| `path` | 請求路徑，用於路由分發 |
| `method` | HTTP 方法 |
| `headers` | HTTP 請求標頭 |
| `remote_addr` | 用戶端完整位址（含埠號） |

**UserValidator → ApiNatsBridge（bridgeResponse）**

```json
{
  "status_code": 200,
  "headers": {
    "Content-Type": "application/json; charset=utf-8"
  },
  "body": "{\"success\":true,\"token\":\"v2.local....\"}"
}
```

| 欄位 | 說明 |
|------|------|
| `status_code` | HTTP 狀態碼，由 ApiNatsBridge 直接回傳給用戶端 |
| `headers` | HTTP 回應標頭 |
| `body` | HTTP 回應主體（JSON 字串） |

---

### `/auth/login` 完整交互流程

```
用戶端                     ApiNatsBridge               UserValidator              gateway-db
  │                             │                           │                         │
  │  POST /auth/login           │                           │                         │
  │  {"username":"user",        │                           │                         │
  │   "password":"pass",        │                           │                         │
  │   "app":"app"}        │                           │                         │
  │────────────────────────────►│                           │                         │
  │                             │  NATS Publish             │                         │
  │                             │  subject: /auth/login     │                         │
  │                             │  bridgeRequest            │                         │
  │                             │──────────────────────────►│                         │
  │                             │                           │                         │
  │                             │                           │  NATS Request           │
  │                             │                           │  subject: db_request    │
  │                             │                           │  {"method":"user.login",│
  │                             │                           │   "data":{              │
  │                             │                           │    "username":"user",   │
  │                             │                           │    "password":"pass"}}  │
  │                             │                           │────────────────────────►│
  │                             │                           │                         │
  │                             │                           │  NATS Reply             │
  │                             │                           │  {"success":true,       │
  │                             │                           │   "data":{              │
  │                             │                           │    "id":1,              │
  │                             │                           │    "username":"user",   │
  │                             │                           │    "nickname":"User"}}  │
  │                             │                           │◄────────────────────────│
  │                             │                           │                         │
  │                             │                           │  簽發 PASETO 令牌        │
  │                             │                           │                         │
  │                             │  NATS Reply               │                         │
  │                             │  bridgeResponse           │                         │
  │                             │  status_code: 200         │                         │
  │                             │  body: {"success":true,   │                         │
  │                             │    "token":"v2.local...", │                         │
  │                             │    "user":{...}}          │                         │
  │                             │◄──────────────────────────│                         │
  │                             │                           │                         │
  │  HTTP 200                  │                           │                         │
  │  {"success":true,          │                           │                         │
  │   "token":"v2.local....",  │                           │                         │
  │   "user":{...}}            │                           │                         │
  │◄────────────────────────────│                           │                         │
```

**步驟說明：**

1. 用戶端發送 `POST /auth/login` 至 ApiNatsBridge（埠 9080）
2. ApiNatsBridge 將 HTTP 請求封裝為 `bridgeRequest`，發布至 NATS 主題 `/auth/login`
3. UserValidator 解析 `body` 欄位取得 `username`、`password`、`app`
4. UserValidator 透過 NATS `Request()` 向 `db_request` 主題發送 `user.login` 請求
5. gateway-db 驗證憑證後回傳使用者資料
6. UserValidator 驗證成功後簽發 PASETO v2 令牌
7. UserValidator 回傳 `bridgeResponse` 給 ApiNatsBridge
8. ApiNatsBridge 根據 `status_code` 將回應轉發回用戶端

**gateway-db 請求範例：**

```json
{"method":"user.login","data":{"username":"admin","password":"123"}}
```

**gateway-db 回應範例（包裹成功）：**

```json
{"success":true,"data":{"id":1,"username":"admin","nickname":"管理員","group":null,"permissions":[],"created_at":"2026-01-01","updated_at":"2026-01-01"}}
```

**gateway-db 回應範例（包裹失敗）：**

```json
{"success":false,"error":"[120000] invalid username or password"}
```

---

### `/auth/verify` 完整交互流程

```
用戶端                     ApiNatsBridge               UserValidator
  │                             │                           │
  │  POST /auth/verify         │                           │
  │  {"token":"v2.local...."}  │                           │
  │────────────────────────────►│                           │
  │                             │  NATS Publish             │
  │                             │  subject: /auth/verify    │
  │                             │  bridgeRequest            │
  │                             │──────────────────────────►│
  │                             │                           │
  │                             │                           │  解密並驗證令牌
  │                             │                           │  （不查詢資料庫）
  │                             │                           │
  │                             │  NATS Reply               │
  │                             │  bridgeResponse           │
  │                             │  status_code: 200         │
  │                             │  body: {"success":true,   │
  │                             │    "username":"user",     │
  │                             │    "app":"app",     │
  │                             │    "sub":"user",          │
  │                             │    "iat":"...",           │
  │                             │    "exp":"..."}           │
  │                             │◄──────────────────────────│
  │                             │                           │
  │  HTTP 200                  │                           │
  │  {"success":true,          │                           │
  │   "username":"user",       │                           │
  │   "app":"app", ...}  │                           │
  │◄────────────────────────────│                           │
```

**步驟說明：**

1. 用戶端發送 `POST /auth/verify` 至 ApiNatsBridge
2. ApiNatsBridge 將請求封裝為 `bridgeRequest`，發布至 NATS 主題 `/auth/verify`
3. UserValidator 解密令牌並驗證時效（過期、尚未生效等）
4. 驗證成功後直接回傳令牌內含的 claims，**不進行任何資料庫查詢**
5. ApiNatsBridge 將回應轉發回用戶端

---

## 專案結構

| 檔案 | 用途 |
|------|------|
| `main.go` | 入口：NATS 連線、訂閱、路由分發、優雅關閉 |
| `config.go` | 設定結構定義與 YAML 設定檔載入 |
| `types.go` | 橋接層請求/回應資料型別與業務請求/回應型別 |
| `handler.go` | 登入驗證與令牌核實的處理邏輯（PASETO v2 令牌簽發/解密） |
| `UserValidator.yaml` | NATS 連線、訂閱主題與 PASETO 金鑰/參數設定 |
| `test/validate.py` | 端對端測試腳本（驗證 + 核實） |
| `test/parse.py` | PASETO 令牌結構解析工具庫 |

## 需求與依賴

- Go 1.24.4+
- NATS Server（執行中，預設 `127.0.0.1:4222`）
- ApiNatsBridge（執行中，預設 `127.0.0.1:9080`）

Go 依賴：

| 套件 | 用途 |
|------|------|
| `github.com/kagurazakayashi/libNyaruko_Go/nyanats` | NATS 用戶端封裝（支援 AES 加密） |
| `github.com/o1egl/paseto` | PASETO v1/v2 令牌簽發與解密 |
| `github.com/google/uuid` | UUID v4 產生（用於 JTI） |
| `gopkg.in/yaml.v3` | YAML 設定檔解析 |

## 建置與執行

```bash
# 建置
cd UserValidator
go build .

# 執行（自動尋找同名的 .yaml 設定檔）
./UserValidator

# 指定設定檔
./UserValidator -c /path/to/config.yaml

# 同時輸出日誌到檔案
./UserValidator -o logs/UserValidator.log
```

### 命令列參數

| 參數 | 說明 |
|------|------|
| `-c` | YAML 設定檔路徑（預設：與執行檔同名的 `.yaml`） |
| `-o` | 日誌輸出檔案路徑（同時寫入終端與檔案） |

## 設定檔參考

參見 `UserValidator.yaml`，主要區段：

### `nats_config` — NATS 連線設定

所有欄位皆為選填，未填寫時使用 `nyanats` 預設值。

| 欄位 | 型別 | 預設值 | 說明 |
|------|------|--------|------|
| `nats_server_host` | string | `127.0.0.1` | NATS 伺服器位址 |
| `nats_server_port` | int | `4222` | NATS 伺服器埠號 |
| `nats_user` | string | (空) | 連線認證使用者名稱 |
| `nats_password` | string | (空) | 連線認證密碼 |
| `nats_client_name` | string | (UUID) | 用戶端識別名稱 |
| `nats_max_reconnects` | int | `5` | 最大重連次數 |
| `nats_reconnect_wait` | int | `2` | 重連等待秒數 |
| `nats_connect_timeout` | int | `10` | 初始連線逾時秒數 |
| `nats_encryption_key` | string | (空) | AES 全域加密金鑰（16/24/32 bytes） |
| `nats_theme_keys` | map | (空) | 個別主題專用金鑰 |

### `nats_subject` — 訂閱主題

| 欄位 | 型別 | 說明 |
|------|------|------|
| `nats_subject` | string | 本服務訂閱的 NATS 主題名稱，預設 `user_valid_req`（已棄用，改用 `nats_subjects`） |
| `nats_subjects` | []string | 本服務訂閱的 NATS 主題列表，主題名稱即為路由路徑，如 `["/auth/login", "/auth/verify"]` |

### `paseto_secret_key` — PASETO 對稱金鑰

| 欄位 | 型別 | 說明 |
|------|------|------|
| `paseto_secret_key` | string | 64 字元十六進位字串（32 bytes / 256-bit），用於 PASETO local 令牌加解密 |

### `paseto_config` — PASETO 令牌參數

所有欄位皆為選填。

| 欄位 | YAML 鍵 | 型別 | 預設值 | 說明 |
|------|---------|------|--------|------|
| 協定版本 | `paseto_version` | string | `v2` | PASETO 協定版本（`v1` / `v2`） |
| 有效時長 | `token_ttl` | string | `24h` | 令牌預設存活時間（Go duration 格式），生產環境推薦 `"2h"` |
| 時長上限 | `max_token_ttl` | string | (以 token_ttl 為上限) | 令牌最大允許時長，限制登入請求中的 `expires` 不得超過此值 |
| 簽發者 | `issuer` | string | (空) | 令牌簽發者（iss claim） |
| 受眾 | `audience` | string | (空) | 令牌受眾（aud claim） |
| 生效偏移 | `not_before` | string | `0s` | 令牌生效時間偏移（Go duration 格式） |
| 唯一識別碼 | `enable_jti` | bool | `false` | 是否產生 jti claim（UUID v4） |
| 自訂 Footer | `footer` | string | (空) | 附加於令牌 Footer 的明文字串 |

### `token_claims_mapping` — 令牌 Claims 與上游欄位映射

設定令牌中各個 claim 對應到上游服務（gateway-db）回傳資料中的哪個欄位。
所有欄位皆為選填，未設定或設為空字串時沿用預設行為。

| 欄位 | YAML 鍵 | 型別 | 預設行為 | 說明 |
|------|---------|------|----------|------|
| 主體 | `sub` | string | 使用登入請求 username | sub claim 對應的上游資料欄位名 |
| 簽發者 | `iss` | string | 使用 `paseto_config.issuer` | iss claim 對應的上游資料欄位名 |
| 受眾 | `aud` | string | 使用 `paseto_config.audience` | aud claim 對應的上游資料欄位名 |
| 唯一識別碼 | `jti` | string | 依 `enable_jti` 自動生成 UUID | jti claim 對應的上游資料欄位名 |
| 自訂映射 | `custom` | map | 僅映射 username / app | 自訂 claims 與上游欄位名的對應關係，格式：`<claim 名>: <上游欄位名>` |

**映射優先級：** 若某 claim 同時有多個來源，生效順序為：
1. `token_claims_mapping` 上游資料映射（最高優先）
2. `paseto_config` 設定檔靜態值
3. 登入請求中的值（`username`、`app`）

**注意：** `exp`（過期時間）、`iat`（簽發時間）、`nbf`（生效時間）為系統時間類 claim，總是由系統自動計算，無法從上游映射。

設定範例：
```yaml
token_claims_mapping:
  sub: "id"                          # 上游的 "id" 欄位 → token sub claim
  iss: ""                            # 空字串：不從上游映射，使用 paseto_config.issuer
  aud: ""                            # 空字串：不從上游映射，使用 paseto_config.audience
  jti: ""                            # 空字串：自動生成 UUID（若 enable_jti 為 true）
  custom:
    nickname: "nickname"             # token claim "nickname" ← 上游 "nickname" 欄位
    group: "group"                   # token claim "group" ← 上游 "group" 欄位
    permissions: "permissions"       # token claim "permissions" ← 上游 "permissions" 欄位
    id: "id"                         # token claim "id" ← 上游 "id" 欄位
```

### `nats_publish` — 對外發布訊息設定

用於向 gateway-db 資料控制微服務發送請求。

| 欄位 | YAML 鍵 | 型別 | 預設值 | 說明 |
|------|---------|------|--------|------|
| 請求主題 | `db_request_subject` | string | `db_request` | 向 gateway-db 發送 Request 的 NATS 主题 |
| 請求逾時 | `db_request_timeout` | string | `5s` | NATS Request 等待回應的逾時時間（Go duration 格式） |

---

## API 介面

本服務透過 NATS 主題清單 `nats_subjects` 接收請求，每個主題名稱即對應一個 HTTP 路徑。
ApiNatsBridge 依 `path` 欄位將 HTTP 請求發布至對應主題，本服務按主題名稱路由。

### `POST /auth/login` — 登入驗證

驗證使用者名稱、密碼與 APPKEY，成功時簽發 PASETO 令牌。

**請求 (HTTP → NATS bridgeRequest.Body)**

```json
{
  "username": "user",
  "password": "pass",
  "app": "app",
  "expires": 3600
}
```

| 欄位 | 型別 | 必要 | 說明 |
|------|------|------|------|
| `username` | string | 是 | 使用者名稱 |
| `password` | string | 是 | 密碼 |
| `app` | string | 是 | 應用程式識別名稱 |
| `expires` | int64 | 否 | 令牌有效秒數，0 或未提供則使用 `token_ttl` 設定值，上限受 `max_token_ttl` 限制 |

**回應 — 成功 (HTTP 200)**

```json
{
  "success": true,
  "token": "v2.local.FcG...（PASETO v2 加密令牌）"
}
```

**回應 — 憑證錯誤 (HTTP 401)**

```json
{
  "success": false,
  "message": "invalid credentials"
}
```

**回應 — 請求格式錯誤 (HTTP 400)**

```json
{
  "success": false,
  "message": "invalid request body"
}
```

**回應 — 令牌產生失敗 (HTTP 404)**

```json
{
  "success": false,
  "message": "failed to generate token: <錯誤詳情>"
}
```

---

### `POST /auth/verify` — 令牌核實

解密並驗證 PASETO 令牌的有效性（時效、完整性），回傳令牌內含的身分資訊。

**請求 (HTTP → NATS bridgeRequest.Body)**

```json
{
  "token": "v2.local.FcG..."
}
```

**回應 — 核實成功 (HTTP 200)**

```json
{
  "success": true,
  "username": "user",
  "app": "app",
  "sub": "user",
  "iat": "2026-05-19T17:24:06+08:00",
  "exp": "2026-05-20T17:24:06+08:00"
}
```

| 欄位 | 說明 |
|------|------|
| `success` | 核實是否成功 |
| `username` | 令牌所屬使用者名稱（自訂 claim） |
| `app` | 令牌對應的應用程式識別名稱（自訂 claim） |
| `sub` | 令牌主體（標準 subject claim） |
| `iat` | 令牌簽發時間，ISO 8601 格式 |
| `exp` | 令牌到期時間，ISO 8601 格式 |

**回應 — 令牌為空 (HTTP 400)**

```json
{
  "success": false,
  "message": "token is required"
}
```

**回應 — 請求格式錯誤 (HTTP 400)**

```json
{
  "success": false,
  "message": "invalid request body"
}
```

**回應 — 令牌無法解密 (HTTP 401)**

金鑰不符、令牌格式錯誤、或被竄改時回傳：

```json
{
  "success": false,
  "message": "token verification failed: <錯誤詳情>"
}
```

**回應 — 令牌時效驗證失敗 (HTTP 401)**

令牌已過期或尚未生效時回傳：

```json
{
  "success": false,
  "message": "token validation failed: <錯誤詳情>"
}
```

常見的 Validate 錯誤訊息：
- `token has expired` — 令牌已過期
- `token is not valid yet` — 令牌尚未生效（NotBefore 限制）

---

### 未匹配路徑 (HTTP 404)

```json
{
  "success": false,
  "message": "not found"
}
```

---

## 所有可能的回應彙總

### `/auth/login`

| HTTP 狀態碼 | success | message | token |
|-------------|---------|---------|-------|
| 200 | `true` | (無) | PASETO 令牌字串 |
| 400 | `false` | `invalid request body` | (無) |
| 401 | `false` | `invalid credentials` | (無) |
| 404 | `false` | `failed to generate token: ...` | (無) |

### `/auth/verify`

| HTTP 狀態碼 | success | message | 其他欄位 |
|-------------|---------|---------|----------|
| 200 | `true` | (無) | `username`, `app`, `sub`, `iat`, `exp` |
| 400 | `false` | `invalid request body` | (無) |
| 400 | `false` | `token is required` | (無) |
| 401 | `false` | `token verification failed: ...` | (無) |
| 401 | `false` | `token validation failed: ...` | (無) |

### 其他路徑

| HTTP 狀態碼 | success | message |
|-------------|---------|---------|
| 404 | `false` | `not found` |

---

## 測試憑證

目前使用硬編碼測試憑證（開發階段）：

| 參數 | 值 |
|------|-----|
| 使用者名稱 | `user` |
| 密碼 | `pass` |
| APP | `app` |

## 測試

### 使用 Python 測試腳本

```bash
# 安裝依賴
pip install requests

# 執行完整測試（先 /auth/login 取得令牌，再 /auth/verify 核實）
cd UserValidator
python test/validate.py

# 自訂驗證端點
python test/validate.py "http://127.0.0.1:9080/auth/login"
```

測試腳本會依序執行：

1. **`POST /auth/login`** — 發送測試憑證，取得 PASETO 令牌
2. **`POST /auth/verify`** — 將取得的令牌送回核實端點，確認令牌有效
3. **令牌結構解析** — 使用 `parse.py` 解析令牌的版本、演算法、claims 等結構資訊

輸出範例：

```
============================================================
請求資訊
============================================================
  URL:     http://127.0.0.1:9080/auth/login
  Body:    {"username": "user", "password": "pass", "app": "app"}

============================================================
原始回應
============================================================
  HTTP Status: 200 OK
  ...

============================================================
令牌核實請求 (/auth/verify)
============================================================
  URL:     http://127.0.0.1:9080/auth/verify
  Body:    {"token": "v2.local...."}

============================================================
核實回應
============================================================
  HTTP Status: 200 OK
  ...

令牌核實成功:
  使用者名稱: user
   APP:     app
  簽發時間:   2026-05-19T17:24:06+08:00
  到期時間:   2026-05-20T17:24:06+08:00
```

### 手動測試（curl）

```bash
# 取得令牌
curl -X POST http://127.0.0.1:9080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"user","password":"pass","app":"app"}'

# 核實令牌（將 <token> 替換為上一步取得的令牌）
curl -X POST http://127.0.0.1:9080/auth/verify \
  -H "Content-Type: application/json" \
  -d '{"token":"<token>"}'
```

## PASETO 令牌規格

| 屬性 | 值 |
|------|-----|
| 協定版本 | v2（可設定為 v1） |
| 用途 | local（對稱式加密） |
| 演算法 | XChaCha20-Poly1305（v2）/ AES-256-CTR + HMAC-SHA-384（v1） |
| 金鑰長度 | 256-bit（32 bytes） |

### 令牌 Claims 完整對照表

以下列出令牌簽發時所有可能包含的 Claim，依資料來源優先級排序（**上游資料映射 > 設定檔靜態值 > 登入請求值**）：

#### 標準 Claims

| Claim | 類型 | 來源 (優先級 1) | 來源 (優先級 2) | 來源 (優先級 3) | 功能說明 |
|-------|------|-----------------|-----------------|-----------------|----------|
| `iat` | 標準 | — | 系統時間 (`time.Now()`) | — | 令牌簽發時間（Issued At），用於審計與判斷令牌年齡 |
| `exp` | 標準 | — | 系統時間 + 有效時長（預設 `token_ttl`，可經登入請求 `expires` 參數覆蓋，上限受 `max_token_ttl` 限制） | — | 令牌過期時間（Expiration），超過後令牌即失效 |
| `nbf` | 標準 | — | 系統時間 + `not_before` 偏移¹ | — | 令牌生效時間（Not Before），在此之前令牌不可用 |
| `sub` | 標準 | `token_claims_mapping.sub` → 上游欄位 | 登入請求 `username` | — | 令牌主體（Subject），識別令牌所屬的使用者 |
| `iss` | 標準 | `token_claims_mapping.iss` → 上游欄位 | `paseto_config.issuer` | — | 令牌簽發者（Issuer），標識簽發此令牌的服務 |
| `aud` | 標準 | `token_claims_mapping.aud` → 上游欄位 | `paseto_config.audience` | — | 令牌受眾（Audience），限制哪些服務接受此令牌 |
| `jti` | 標準 | `token_claims_mapping.jti` → 上游欄位 | UUID v4² | — | 令牌唯一識別碼（JWT ID），用於防重放攻擊與令牌撤銷 |

> ¹ `nbf` 僅在 `not_before` 偏移 > 0 時才寫入令牌
> ² UUID v4 僅在 `enable_jti: true` 且未從上游映射時自動產生

#### 自訂 Claims

| Claim | 類型 | 來源 (優先級 1) | 來源 (優先級 2) | 功能說明 |
|-------|------|-----------------|-----------------|----------|
| `username` | 自訂 | `token_claims_mapping.custom` 映射³ | 登入請求 `username` | 目前登入的使用者名稱 |
| `app` | 自訂 | `token_claims_mapping.custom` 映射³ | 登入請求 `app` | 應用程式/用戶端識別碼，區分不同接入端 |
| *任意名* | 自訂 | `token_claims_mapping.custom` 映射⁴ | — | 可將上游資料中任意欄位（如 `nickname`、`group`、`permissions`）映射為令牌 claim |

> ³ 若 `token_claims_mapping.custom` 中設定了同名的 key（如 `username: "db_user_name"`），則以此上游值覆蓋登入請求值
> ⁴ 格式為 `<claim 名>: <上游欄位名>`，例如 `nickname: "nickname"` 表示將上游資料的 `nickname` 欄位值寫入令牌的 `nickname` claim

#### Footer

| 項目 | 來源 | 功能說明 |
|------|------|----------|
| Footer | `paseto_config.footer` | 明文（不加密）附加資料，可在不解密令牌時讀取，適用於金鑰輪替 ID、環境標籤等公開中繼資料 |

令牌格式範例：

```
v2.local.<payload_base64url>
```

## 關聯外部設定

本服務的正確運作依賴以下外部服務設定，若修改 NATS 連線方式或主題，
需同步更新這些設定檔後手動重啟對應服務：

| 服務 | 設定檔 | 說明 |
|------|--------|------|
| NATS Server | `ExampleConfiguration/nats-server.conf` | 需為 `webapi` 使用者（ApiNatsBridge）設定 `/auth/login`、`/auth/verify` 的 publish 權限，並為 `userauth` 使用者（UserValidator）設定對應的 subscribe 權限 |
| ApiNatsBridge | `ExampleConfiguration/ApiNatsBridgeConfig.yaml` | 需在 `routes` 中為 `/auth/login` 與 `/auth/verify` 設定路由規則，`nats_subject` 應與本服務訂閱的主題名稱一致 |
