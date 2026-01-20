# ![](icon/icon.png) NyarukoLogin 2

一個通用的使用者登入系統，本身作為服務為外部業務系統提供認證授權，支援多開與負載均衡。

> **⚠ 注意：** 本程式尚未完全完成，請勿用於正式生產環境。  
> **舊版本：** 如需查看舊版程式碼，請前往 [v2016_expired](https://github.com/kagurazakayashi/NyarukoLogin/tree/v2016_expired) 分支。  
> **系統需求：** PHP 8.1+，MySQL 5.7+ / MariaDB 10.3+，Redis 5.0+，Linux 作業系統（不支援 Windows）。

---

## 技術架構

```
┌─────────────┐     ┌──────────────────────────────────┐
│  用戶端      │────▶│  入口端點 (.php)                  │
│  (APP/WEB)  │     │  - 解密請求                       │
│             │◀────│  - 呼叫業務類別                   │
└─────────────┘     │  - 加密回傳                       │
                    └──────────┬───────────────────────┘
                               │
                    ┌──────────▼───────────────────────┐
                    │  業務邏輯層 (src/*.class.php)     │
                    │  - nyalogin    - nyasignup       │
                    │  - nyavcode    - nyacaptcha      │
                    │  - nyamessage  - nyasearch       │
                    │  - nyaupload   - nyauserinfo     │
                    └──────────┬───────────────────────┘
                               │
       ┌───────────────────────┼───────────────────────┐
       │                       │                       │
┌──────▼──────┐     ┌──────────▼──────────┐    ┌──────▼──────┐
│  MySQL      │     │  核心層              │    │  Redis      │
│  讀寫分離    │◀───▶│  nyacore - 核心引導  │◀───▶│  快取/佇列  │
│  負載均衡    │     │  nyasafe - 安全加密  │    │  工作訊息    │
└─────────────┘     │  nyasess - 會話管理  │    └──────┬──────┘
                    │  nyafunc - 通用函式  │           │
                    └─────────────────────┘    ┌──────▼──────┐
                                               │  Go 服務     │
                                               │  圖片/影片   │
                                               │  非同步二壓   │
                                               └─────────────┘
```

### 三層架構

| 層級 | 說明 | 位置 |
|---|---|---|
| **入口端點** | 接收 HTTP 請求，解密參數，呼叫業務類別 | 根目錄 `*.php` |
| **業務邏輯** | 處理具體業務（登入、註冊、上傳等） | `src/*.class.php` |
| **核心設施** | 資料庫、加密、安全、會話等基礎服務 | `src/nyacore`, `nyasafe`, `nyasession` 等 |

### 加密通訊流程

1. 用戶端呼叫 `nyaencryption.php` 交換 RSA 金鑰對，獲取 `apptoken`
2. 用戶端使用伺服器公開金鑰（RSA）加密請求 JSON，以 URL-Safe Base64 編碼傳送
3. 伺服器使用對應私鑰解密，處理請求，使用用戶端公鑰加密回傳
4. 所有 API 共用 `apptoken` 標識會話，已登入使用者還需傳送 `token`（登入會話令牌）

---

## 快速開始

### 1. 環境需求

- **PHP** 8.1 或更高版本
- **MySQL** 5.7+ / MariaDB 10.3+（支援讀寫分離和負載均衡）
- **Redis** 5.0+（用於快取和工作佇列）
- **Go** 1.18+（非同步媒體轉碼服務，可選）
- **ImageMagick** + **FFmpeg**（媒體處理，可選）

### 2. 安裝

```bash
# 複製專案
git clone https://github.com/kagurazakayashi/NyarukoLogin.git
cd NyarukoLogin

# 安裝 PHP 依賴
composer install

# 建立設定檔
cp nyaconfig.class.example.php nyaconfig.class.php
# 編輯 nyaconfig.class.php 設定資料庫和其他選項

# 匯入資料庫結構
mysql -u root -p < file/nyarukologin.sql

# 編譯 Go 媒體轉碼服務（可選）
cd tools
./build.sh
```

### 3. 設定

編輯 `nyaconfig.class.php`，主要設定項：

| 設定項 | 說明 |
|---|---|
| `$db->read_dbs` | 讀取資料庫連線資訊（可設定多個以實現負載均衡） |
| `$db->write_dbs` | 寫入資料庫連線資訊 |
| `$db->redis` | Redis 連線資訊 |
| `$enc->pkeyConfig` | RSA 金鑰設定 |
| `$app->debug` | 除錯模式（正式環境請設為 0） |
| `$app->language` | 支援的語言列表 |
| `$verify->captcha` | 圖形驗證碼設定 |
| `$verify->smtp` | SMTP 郵件伺服器設定 |

---

## API 端點

| 檔案 | 功能 | 需要認證 |
|---|---|---|
| `nyaencryption.php` | RSA 金鑰交換，獲取應用令牌 | 否 |
| `nyacaptcha.php` | 獲取圖形驗證碼 | 否 |
| `nyavcode.php` | 獲取簡訊 / 郵件驗證碼 | 否 |
| `nyavcodechk.php` | 驗證簡訊 / 郵件驗證碼，獲取預分配令牌 | 否 |
| `nyasignup.php` | 註冊新使用者 | 否 |
| `nyalogin.php` | 使用者登入 | 否 |
| `nyalogout.php` | 使用者登出 | 是 |
| `chktoken.php` | 檢查登入會話狀態 | 是 |
| `nyauserinfo.php` | 查詢使用者資料 | 是 |
| `nyauserinfoedit.php` | 修改使用者資料 | 是 |
| `nyachangepassword.php` | 修改密碼（使用預分配令牌） | 否 |
| `nyastand.php` | 建立子帳戶 | 是 |
| `nyamessage.php` | 收發站內信、標記已讀 | 是 |
| `search.php` | 模糊搜尋使用者 | 否 |
| `nyaupload.php` | 上傳媒體檔案 | 是 |
| `nyamediafiles.php` | 查詢媒體檔案可用格式和尺寸 | 否 |
| `nyauploadsize.php` | 取得伺服器上傳大小限制 | 否 |
| `nyatransparent.php` | 生成純色 PNG 圖片 | 否 |

---

## 開發進度

| 模組 | 狀態 |
|---|---|
| **核心** — 資料庫類別、安全類別、資訊類別、設定類別 | ✅ 完成 |
| **資料庫** — MySQL / Redis 讀寫分離、負載均衡 | ✅ 完成 |
| **加密傳輸** — RSA 金鑰交換、分段加解密 | ✅ 完成 |
| **會話管理** — 登入令牌、Redis 快取、多裝置限制 | ✅ 完成 |
| **使用者註冊** — 圖形/簡訊/郵件驗證碼、預分配令牌 | ✅ 完成 |
| **使用者登入** — 驗證碼檢查、密碼驗證、裝置管理 | ✅ 完成 |
| **使用者資料** — 性別、人稱、頭像、背景、簡介 | ✅ 完成 |
| **子帳戶** — 建立、查詢、歸屬驗證 | ✅ 完成 |
| **站內信** — 收發訊息、合併通知、已讀標記 | ✅ 完成 |
| **檔案上傳** — 圖片/影片上傳、非同步二壓、多尺寸 | ✅ 完成 |
| **敏感詞過濾** — 輸入檢查、違禁詞庫 | ✅ 完成 |
| **模糊搜尋** — 使用者暱稱快速搜尋 | ✅ 完成 |
| **頻率限制** — 各端點獨立 IP 存取頻率限制 | ✅ 完成 |
| **兩步驗證** — Google Authenticator、密保問題、恢復碼 | ❌ 待完成 |
| **使用者權限** — 內建權限、外部自訂權限 | ❌ 待完成 |
| **積分系統** — 業務積分、等級稱號 | ❌ 待完成 |
| **實名認證** — 身分證驗證 | ❌ 待完成 |
| **內容稽核** — 人工審核後台 | ❌ 待完成 |
| **黑名單** — 手機、郵箱、身分證黑名單 | ❌ 待完成 |
| **OSS 整合** — 阿里雲 OSS / VOD 支援 | ❌ 待完成 |
| **單元測試** — PHPUnit 測試覆蓋 | ❌ 待完成 |

---

## 錯誤碼說明

錯誤碼採用 `ABBCCDD` 八位數字格式：

| 位 | 說明 | 範例 |
|---|---|---|
| `A` | 1=成功，2=錯誤，3=警告 | `1`=成功 |
| `BB` | 模組代碼 | `00`=通用，`01`=資料庫，`02`=安全，`04`=使用者 |
| `CC` | 錯誤類型 | `01`=參數，`02`=格式，`04`=加密 |
| `DD` | 詳細錯誤 | 具體錯誤編號 |

完整錯誤碼表請參見 [Wiki](https://github.com/kagurazakayashi/NyarukoLogin/wiki/錯誤代碼表)。

---

## 檔案說明

| 目錄/檔案 | 說明 |
|---|---|
| `/` | API 入口端點檔案 |
| `src/` | 業務邏輯和核心類別 |
| `template/` | 郵件範本（支援多語言） |
| `tools/` | Go 語言媒體轉碼服務及 Python 工具 |
| `tests/` | 測試指令碼（Python / Shell / PHP） |
| `file/` | 開發文件、SQL 結構 |
| `img/` | 驗證碼暫存目錄 |
| `icon/` | 應用程式圖示 |
| `composer.json` | PHP 依賴管理 |
| `nyaconfig.class.example.php` | 設定檔範本 |
| `.htaccess` | Apache 安全設定 |

---

## 授權條款

本專案採用 [MIT License](LICENSE) 授權。

Copyright (c) 2016-2025 KagurazakaYashi

---

## 文件

[完整 Wiki 文件](https://github.com/kagurazakayashi/NyarukoLogin/wiki)
