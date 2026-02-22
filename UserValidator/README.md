# UserValidator - 登入狀態驗證器

NyarukoLogin 3 的登入狀態驗證服務模組。透過 NATS 接收由 ApiNatsBridge 轉發的 HTTP 請求，
提供使用者憑證驗證、PASETO 令牌簽發與令牌核實功能。

## 架構

```
用戶端 (HTTP POST)
    │
    ▼
ApiNatsBridge (HTTP → NATS 橋接，預設埠 9080)
    │  NATS Request-Reply (主題: user_valid_req)
    ▼
UserValidator (本服務)
    │  路由分發: /validate → 簽發令牌
    │            /verify   → 核實令牌
    ▼
ApiNatsBridge ← 回應 JSON
    │
    ▼
用戶端 (HTTP Response)
```

UserValidator 不直接監聽 HTTP 埠，而是作為 NATS 微服務執行。
對外 HTTP 存取由 ApiNatsBridge 統一代理，本服務僅需訂閱 NATS 主題即可。

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
| `nats_subject` | string | 本服務訂閱的 NATS 主題名稱，預設 `user_valid_req` |

### `paseto_secret_key` — PASETO 對稱金鑰

| 欄位 | 型別 | 說明 |
|------|------|------|
| `paseto_secret_key` | string | 64 字元十六進位字串（32 bytes / 256-bit），用於 PASETO local 令牌加解密 |

### `paseto_config` — PASETO 令牌參數

所有欄位皆為選填。

| 欄位 | YAML 鍵 | 型別 | 預設值 | 說明 |
|------|---------|------|--------|------|
| 協定版本 | `paseto_version` | string | `v2` | PASETO 協定版本（`v1` / `v2`） |
| 有效時長 | `token_ttl` | string | `24h` | 令牌存活時間（Go duration 格式） |
| 簽發者 | `issuer` | string | (空) | 令牌簽發者（iss claim） |
| 受眾 | `audience` | string | (空) | 令牌受眾（aud claim） |
| 生效偏移 | `not_before` | string | `0s` | 令牌生效時間偏移（Go duration 格式） |
| 唯一識別碼 | `enable_jti` | bool | `false` | 是否產生 jti claim（UUID v4） |
| 自訂 Footer | `footer` | string | (空) | 附加於令牌 Footer 的明文字串 |

---

## API 介面

本服務透過 NATS 主題 `user_valid_req` 接收請求，
ApiNatsBridge 依 `path` 欄位將 HTTP 請求路由至對應處理函式。

### `POST /validate` — 登入驗證

驗證使用者名稱、密碼與 APPKEY，成功時簽發 PASETO 令牌。

**請求 (HTTP → NATS bridgeRequest.Body)**

```json
{
  "username": "user",
  "password": "pass",
  "appkey": "appkey"
}
```

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

### `POST /verify` — 令牌核實

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
  "appkey": "appkey",
  "sub": "user",
  "iat": "2026-05-19T17:24:06+08:00",
  "exp": "2026-05-20T17:24:06+08:00"
}
```

| 欄位 | 說明 |
|------|------|
| `success` | 核實是否成功 |
| `username` | 令牌所屬使用者名稱（自訂 claim） |
| `appkey` | 令牌對應的應用程式金鑰（自訂 claim） |
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

### `/validate`

| HTTP 狀態碼 | success | message | token |
|-------------|---------|---------|-------|
| 200 | `true` | (無) | PASETO 令牌字串 |
| 400 | `false` | `invalid request body` | (無) |
| 401 | `false` | `invalid credentials` | (無) |
| 404 | `false` | `failed to generate token: ...` | (無) |

### `/verify`

| HTTP 狀態碼 | success | message | 其他欄位 |
|-------------|---------|---------|----------|
| 200 | `true` | (無) | `username`, `appkey`, `sub`, `iat`, `exp` |
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
| APPKEY | `appkey` |

## 測試

### 使用 Python 測試腳本

```bash
# 安裝依賴
pip install requests

# 執行完整測試（先 /validate 取得令牌，再 /verify 核實）
cd UserValidator
python test/validate.py

# 自訂驗證端點
python test/validate.py "http://127.0.0.1:9080/validate"
```

測試腳本會依序執行：

1. **`POST /validate`** — 發送測試憑證，取得 PASETO 令牌
2. **`POST /verify`** — 將取得的令牌送回核實端點，確認令牌有效
3. **令牌結構解析** — 使用 `parse.py` 解析令牌的版本、演算法、claims 等結構資訊

輸出範例：

```
============================================================
請求資訊
============================================================
  URL:     http://127.0.0.1:9080/validate
  Body:    {"username": "user", "password": "pass", "appkey": "appkey"}

============================================================
原始回應
============================================================
  HTTP Status: 200 OK
  ...

============================================================
令牌核實請求 (/verify)
============================================================
  URL:     http://127.0.0.1:9080/verify
  Body:    {"token": "v2.local...."}

============================================================
核實回應
============================================================
  HTTP Status: 200 OK
  ...

令牌核實成功:
  使用者名稱: user
  APPKEY:     appkey
  簽發時間:   2026-05-19T17:24:06+08:00
  到期時間:   2026-05-20T17:24:06+08:00
```

### 手動測試（curl）

```bash
# 取得令牌
curl -X POST http://127.0.0.1:9080/validate \
  -H "Content-Type: application/json" \
  -d '{"username":"user","password":"pass","appkey":"appkey"}'

# 核實令牌（將 <token> 替換為上一步取得的令牌）
curl -X POST http://127.0.0.1:9080/verify \
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
| 包含的標準 Claims | `sub`、`iat`、`exp`（必備）；`iss`、`aud`、`nbf`、`jti`（選填） |
| 包含的自訂 Claims | `username`、`appkey` |

令牌格式範例：

```
v2.local.<payload_base64url>
```

## 關聯外部設定

本服務的正確運作依賴以下外部服務設定，若修改 NATS 連線方式或主題，
需同步更新這些設定檔後手動重啟對應服務：

| 服務 | 設定檔 | 說明 |
|------|--------|------|
| NATS Server | `ExampleConfiguration/nats-server.conf` | 需在 `authorization.users[0].permissions` 中包含 `user_valid_req` 的 publish/subscribe 權限 |
| ApiNatsBridge | `ExampleConfiguration/ApiNatsBridgeConfig.yaml` | 需在 `routes` 中為 `/validate` 與 `/verify` 設定路由規則 |
