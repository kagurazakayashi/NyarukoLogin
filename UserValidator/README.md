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

此外，本服務也提供**直接 NATS 介面**（`auth.token.verify`），讓其他微服務可以跳過 HTTP 橋接層，
直接透過 NATS Request/Reply 模式進行令牌核實，無需繞經 HTTP。

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

### `auth.token.verify` — 直接 NATS 介面（令牌核實）

此介面**不經過 ApiNatsBridge**，其他微服務可直接透過 NATS Request/Reply 模式發送令牌進行核實。

**發送格式：**
- `!` 結尾的 token → **精簡模式**，回傳整數錯誤代碼
- `?` 結尾的 token → **詳細模式**，回傳完整 JSON
- 可在 `!` 或 `?` **前面**附加 tag（任意字串），回覆時會以 `tag|` 前綴帶回

```
發送格式： [tag]?令牌   →  回覆： tag|{JSON}
          [tag]!令牌   →  回覆： tag|整數
          ?令牌        →  回覆： {JSON}
          !令牌        →  回覆： 整數
```

> **注意：** `!` / `?` 必須存在。若兩者皆未出現或令牌為空，回傳錯誤。

---

#### 精簡模式（`!`）— 回傳整數錯誤代碼

只需判斷令牌是否有效時使用。

```
其他微服務                     UserValidator
  │                               │
  │  NATS Request                 │
  │  subject: auth.token.verify   │
  │  !v2.local.FcG...             │
  │──────────────────────────────►│
  │                               │  解密並驗證令牌
  │  NATS Reply                   │
  │  0                            │
  │◄──────────────────────────────│
```

**請求範例：**

```
!v2.local.FcG...
```

**回覆範例：**

```
0
```

**錯誤代碼表：**

| 代碼 | 含義 | 說明 |
|------|------|------|
| `0` | 令牌有效 | 令牌解密成功且未過期、未竄改 |
| `1` | 請求格式無效 | 保留代碼 |
| `2` | 令牌為空 | 發送的令牌字串為空或找不到模式標記 |
| `3` | 令牌無法解密 | 金鑰不符、令牌格式錯誤、或被竄改 |
| `4` | 令牌時效驗證失敗 | 令牌已過期或尚未生效 |

---

#### 詳細模式（`?`）— 回傳 JSON

需要取得令牌內含資訊（使用者名稱、app、時效等）時使用。

```
其他微服務                     UserValidator
  │                               │
  │  NATS Request                 │
  │  subject: auth.token.verify   │
  │  ?v2.local.FcG...             │
  │──────────────────────────────►│
  │                               │  解密並驗證令牌
  │  NATS Reply                   │
  │  {"success":true,             │
  │   "username":"user",          │
  │   "app":"app",                │
  │   "sub":"user",               │
  │   "iat":"...",                │
  │   "exp":"..."}                │
  │◄──────────────────────────────│
```

**請求範例：**

```
?v2.local.FcG...
```

**回覆 — 核實成功：**

```json
{"success":true,"username":"user","app":"app","sub":"user","iat":"...","exp":"..."}
```

**回覆 — 核實失敗：**

```json
{"success":false,"message":"token verification failed: <錯誤詳情>"}
```

---

#### 選填 tag — 請求匹配

可在 `!` 或 `?` 前附加任意 tag，回覆時以 `tag|` 前綴帶回，方便呼叫方匹配請求與回覆。

```
其他微服務                     UserValidator
  │                               │
  │  NATS Request                 │
  │  subject: auth.token.verify   │
  │  req-42?v2.local.FcG...       │
  │──────────────────────────────►│
  │                               │
  │  NATS Reply                   │
  │  req-42|{"success":true,...}  │
  │◄──────────────────────────────│
```

**請求範例：**

| 發送內容 | 模式 | tag | 回覆格式 |
|----------|------|-----|----------|
| `!v2.local...` | 精簡 | (無) | `0` |
| `?v2.local...` | 詳細 | (無) | `{"success":true,...}` |
| `req1!v2.local...` | 精簡 | `req1` | `req1\|0` |
| `req1?v2.local...` | 詳細 | `req1` | `req1\|{"success":true,...}` |
| `abc-123!v2.local...` | 精簡 | `abc-123` | `abc-123\|4` |

**其他微服務呼叫範例：**

```go
// Go 微服務：以 tag 匹配請求
tag := fmt.Sprintf("req-%d", time.Now().UnixNano())
resp, err := natsClient.Request("auth.token.verify",
    tag+"?v2.local.FcG...", 5*time.Second)
if err != nil {
    return
}
// 回覆格式：tag|JSON，分割取出內容
parts := strings.SplitN(resp, "|", 2)
if parts[0] == tag {
    var result verifyResponse
    json.Unmarshal([]byte(parts[1]), &result)
    // ...
}
```

```python
# Python 微服務：使用 nats-py
import asyncio, json
from nats.aio.client import Client as NATS

async def main():
    nc = NATS()
    await nc.connect("nats://127.0.0.1:4222")

    # 精簡模式
    resp = await nc.request("auth.token.verify", b"!v2.local.FcG...", timeout=5)
    code = int(resp.data)
    print("有效" if code == 0 else f"無效，代碼: {code}")

    # 詳細模式
    resp = await nc.request("auth.token.verify", b"?v2.local.FcG...", timeout=5)
    result = json.loads(resp.data)
    print(f"使用者: {result['username']}")

    # 詳細模式 + tag
    resp = await nc.request("auth.token.verify", b"my-req-1?v2.local.FcG...", timeout=5)
    tag, data = resp.data.decode().split("|", 1)
    result = json.loads(data)
    print(f"[{tag}] 使用者: {result['username']}")

    await nc.close()
```

**詳細模式回覆欄位說明：**

| 欄位 | 成功時存在 | 說明 |
|------|-----------|------|
| `success` | 是 | 核實是否成功（`true` / `false`） |
| `username` | 是 | 令牌所屬使用者名稱 |
| `app` | 是 | 令牌對應的應用程式識別名稱 |
| `sub` | 是 | 令牌主體（標準 subject claim） |
| `iat` | 是 | 令牌簽發時間，ISO 8601 格式 |
| `exp` | 是 | 令牌到期時間，ISO 8601 格式 |
| `message` | 失敗時 | 錯誤原因說明 |

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
| `nats_subjects` | []string | HTTP 橋接層主題列表，主題名稱即為路由路徑，如 `["/auth/login", "/auth/verify"]`。訊息格式為 `bridgeRequest` |
| `nats_subjects_direct` | []string | 直接 NATS 主題列表，訊息格式為純 JSON（無 `bridgeRequest` 包裝），如 `["auth.token.verify"]`。供其他微服務直接使用 NATS Request/Reply 呼叫 |

### `paseto_secret_key` — PASETO 對稱金鑰環

| 欄位 | 型別 | 說明 |
|------|------|------|
| `paseto_secret_key` | string 或 map | 64 字元十六進位字串（舊格式，單一金鑰），或 `{UNIX時間戳: 金鑰字串}` 字典（新格式，金鑰輪替） |

**舊格式（向後相容）：**
```yaml
paseto_secret_key: "404142434445464748494a4b4c4d4e4f505152535455565758595a5b5c5d5e5f"
```

**新格式（金鑰輪替，推薦）：**
```yaml
paseto_secret_key:
  1748736000: "404142434445464748494a4b4c4d4e4f505152535455565758595a5b5c5d5e5f"
  1779840000: "606162636465666768696a6b6c6d6e6f707172737475767778797a7b7c7d7e7f"
```

金鑰輪替行為：
- **簽發**：永遠使用時間戳最大（最新）的金鑰
- **驗證**：依時間戳降序遍歷所有金鑰，任一解密成功即接受（舊令牌在輪替後仍可驗證）
- **清理**：當舊令牌全部過期後，應從設定中移除對應的舊金鑰以縮小攻擊面

### `paseto_config` — PASETO 令牌參數

所有欄位皆為選填。

| 欄位 | YAML 鍵 | 型別 | 預設值 | 說明 |
|------|---------|------|--------|------|
| 協定版本 | `paseto_version` | string | `v2` | PASETO 協定版本（`v1` / `v2`） |
| 有效時長 | `token_ttl` | string | `24h` | 令牌預設存活時間（Go duration 格式），生產環境推薦 `"2h"` |
| 時長上限 | `max_token_ttl` | string | (以 token_ttl 為上限) | 令牌最大允許時長，限制登入請求中的 `expires` 不得超過此值 |

已移除的廢棄欄位：`issuer`、`audience`、`not_before`、`enable_jti`、`footer`。
這些值現在由登入請求或系統自動決定。

### `token_claims_mapping` — 令牌 Claims 與上游欄位映射

設定令牌中標準 claims 對應到上游服務（gateway-db）回傳資料中的哪個欄位。
所有欄位皆為選填，未設定或設為空字串時沿用預設行為。

| 欄位 | YAML 鍵 | 型別 | 預設行為 | 說明 |
|------|---------|------|----------|------|
| 主體 | `sub` | string | 使用登入請求 username | sub claim 對應的上游資料欄位名 |
| 簽發者 | `iss` | string | 使用請求路徑（如 `/auth/login`） | iss claim 對應的上游資料欄位名 |
| 受眾 | `aud` | string | 使用登入請求 app | aud claim 對應的上游資料欄位名 |

**僅 sub / iss / aud 三個 claims 可透過上游資料覆蓋。** 以下欄位為系統自動計算，不可映射：
- `iat`（簽發時間）- 系統時間
- `nbf`（生效時間）- 系統時間
- `exp`（過期時間）- 系統時間 + TTL
- `jti`（唯一識別碼）- 永遠自動生成 32 字元高熵英數字隨機字串
- `kid`（金鑰識別碼）- 永遠為簽發金鑰的 UNIX 時間戳

**已移除：** 自訂 claims（`custom`）不再支援，所有自訂 claims 已從令牌中移除。

設定範例：
```yaml
token_claims_mapping:
  sub: "id"                          # 上游的 "id" 欄位 → token sub claim
  iss: ""                            # 空字串：不從上游映射，使用請求路徑
  aud: ""                            # 空字串：不從上游映射，使用登入請求的 app
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

### `auth.token.verify` — 令牌核實（直接 NATS 介面）

此介面**不經過 ApiNatsBridge**，其他微服務可直接透過 NATS Request/Reply 模式進行令牌核實。
使用 `!` 指定精簡模式，`?` 指定詳細模式，可在模式標記前附加選填 tag。

**NATS 發送端設定：**

| 設定項 | 值 |
|--------|-----|
| 主題 (subject) | `auth.token.verify` |
| 通訊模式 | NATS Request/Reply |
| 訊息格式 | 純文字：`[tag][?!]令牌` |
| 加密 | 由主題專用金鑰自動處理（`nats_theme_keys["auth.token.verify"]`） |

#### 格式一覽

| 發送 | 模式 | tag | 回覆示例 |
|------|------|-----|----------|
| `!v2.local...` | 精簡 | 無 | `0` |
| `?v2.local...` | 詳細 | 無 | `{"success":true,"username":"user",...}` |
| `req1!v2.local...` | 精簡 | `req1` | `req1\|0` |
| `req1?v2.local...` | 詳細 | `req1` | `req1\|{"success":true,...}` |

#### 精簡模式（`!`）— 回傳整數錯誤代碼

僅需判斷令牌是否有效時使用，回覆為單一十進位整數字串。

**請求 (NATS Request)**

```
!v2.local.FcG...
```

**回覆 — 令牌有效**

```
0
```

**回覆 — 令牌無法解密**

```
3
```

**回覆 — 令牌已過期**

```
4
```

**錯誤代碼對照表：**

| 代碼 | 含義 | 說明 |
|------|------|------|
| `0` | 令牌有效 | 解密成功且各項時效檢查通過 |
| `1` | 請求格式無效 | （保留） |
| `2` | 令牌為空 | 發送的令牌字串為空或找不到 `!` / `?` |
| `3` | 令牌解密失敗 | 金鑰不符、令牌格式錯誤、或被竄改 |
| `4` | 令牌驗證失敗 | 令牌已過期或尚未生效 |

#### 詳細模式（`?`）— 回傳 JSON

需要取得令牌內含資訊時使用。

**請求 (NATS Request)**

```
?v2.local.FcG...
```

**回覆 — 核實成功**

```json
{"success":true,"username":"user","app":"app","sub":"user","iat":"...","exp":"..."}
```

**回覆 — 令牌無法解密**

```json
{"success":false,"message":"token verification failed: <錯誤詳情>"}
```

**回覆 — 令牌時效驗證失敗**

```json
{"success":false,"message":"token validation failed: <錯誤詳情>"}
```

**回覆 — 令牌為空**

```json
{"success":false,"message":"token is required"}
```

#### 選填 tag

在 `!` 或 `?` 前附加任意 tag，回覆時以 `tag|` 前綴帶回。

```
發送:  req-42!v2.local...   →   回覆:  req-42|0
發送:  req-42?v2.local...   →   回覆:  req-42|{"success":true,...}
```

> **注意：** 精簡模式回傳整數代碼字串（非 JSON）。詳細模式回傳完整 `verifyResponse` JSON。均無 HTTP 狀態碼。

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

### `auth.token.verify`（直接 NATS）

此介面為直接 NATS Request/Reply，**無 HTTP 狀態碼**。模式由發送時的 `!` / `?` 決定。

**精簡模式（`!`）— 回傳整數錯誤代碼：**

| 回覆值 | 說明 |
|--------|------|
| `0` | 令牌有效 |
| `2` | 令牌為空或格式錯誤 |
| `3` | 令牌無法解密 |
| `4` | 令牌時效驗證失敗（過期／尚未生效） |

**詳細模式（`?`）— 回傳 JSON：**

| success | message | 其他欄位 |
|---------|---------|----------|
| `true` | (無) | `username`, `app`, `sub`, `iat`, `exp` |
| `false` | `token is required` | (無) |
| `false` | `token verification failed: ...` | (無) |
| `false` | `token validation failed: ...` | (無) |

**選填 tag：** 若有 tag，回覆前綴為 `tag|`（如 `req-42|0` 或 `req-42|{"success":true,...}`）。

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

以下列出令牌簽發時所有包含的 Claim，依資料來源排序：

#### 標準 Claims

| Claim | 來源 | 說明 |
|-------|------|------|
| `iss` | 請求路徑 (如 `/auth/login`)，可由上游映射覆蓋 | 簽發者（Issuer），標識簽發此令牌的路由端點 |
| `sub` | 登入請求 `username`，可由上游映射覆蓋 | 主體（Subject），識別令牌所屬的使用者 |
| `aud` | 登入請求 `app`，可由上游映射覆蓋 | 受眾（Audience），標識應用程式名稱 |
| `iat` | 系統時間 (`now`) | 簽發時間（Issued At） |
| `nbf` | 系統時間 (`now`) | 生效時間（Not Before），立即生效 |
| `exp` | 系統時間 + 有效時長 | 過期時間（Expiration） |
| `jti` | 32 字元高熵英數字隨機字串 | 唯一識別碼（JWT ID），用於防重放攻擊與撤銷 |
| `kid` | 簽發金鑰的 UNIX 時間戳 | 金鑰識別碼（Key ID），用於金鑰輪替時定位正確解密金鑰 |

> **覆蓋規則：** 僅 `iss`、`sub`、`aud` 可透過 `token_claims_mapping` 從上游資料覆蓋。`iat`、`nbf`、`exp`、`jti`、`kid` 為系統計算，不可覆蓋。

#### 自訂 Claims

（已全部移除，不再包含任何自訂 claims）

令牌格式範例：

```
v2.local.<payload_base64url>
```

## 關聯外部設定

本服務的正確運作依賴以下外部服務設定，若修改 NATS 連線方式或主題，
需同步更新這些設定檔後手動重啟對應服務：

| 服務 | 設定檔 | 說明 |
|------|--------|------|
| NATS Server | `ExampleConfiguration/nats-server.conf` | 需為 `webapi` 使用者（ApiNatsBridge）設定 `/auth/login`、`/auth/verify` 的 publish 權限，並為 `userauth` 使用者（UserValidator）設定對應的 subscribe 權限。此外需允許其他微服務對 `auth.token.verify` 的 publish 及 subscribe 權限 |
| ApiNatsBridge | `ExampleConfiguration/ApiNatsBridgeConfig.yaml` | 需在 `routes` 中為 `/auth/login` 與 `/auth/verify` 設定路由規則，`nats_subject` 應與本服務訂閱的主題名稱一致。`auth.token.verify` 主題不經過 ApiNatsBridge，無需在此設定 |
