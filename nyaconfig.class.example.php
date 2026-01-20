<?php

/**
 * 系統設定檔範本
 *
 * 使用前請複製為 nyaconfig.class.php 並修改設定值。
 * 請勿將真實密碼提交至版本控制系統。
 *
 * @package NyarukoLogin
 * @author  KagurazakaYashi
 * @license MIT
 */

/** 資料庫連線設定 */
class nyasetting_db {
    /** @var string MySQL 編碼 */
    public string $charset = "utf8mb4";
    /**
     * 唯讀資料庫列表（可指定多個）
     * 所需權限：SELECT
     * @var array<int, array<string, string>>
     */
    public array $read_dbs = [
        [
            "db_host" => "127.0.0.1",
            "db_port" => "3306",
            "db_name" => "nyarukologin",
            "db_user" => "nyarukologin_r",
            "db_password" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
        ]
    ];
    /**
     * 寫入資料庫列表（可指定多個）
     * 所需權限：INSERT, UPDATE, DELETE
     * @var array<int, array<string, string>>
     */
    public array $write_dbs = [
        [
            "db_host" => "127.0.0.1",
            "db_port" => "3306",
            "db_name" => "nyarukologin",
            "db_user" => "nyarukologin_w",
            "db_password" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
        ]
    ];
    /** @var array<string, string> 資料庫表名稱對應 */
    public array $tables = [
        "business" => "u1_business",   // 業務表
        "app" => "u1_app",             // 外部程式表
        "integral" => "u1_integral",   // 積分表
        "ip" => "u1_ip",               // IP 位址表
        "permission" => "u1_permission", // 權限表
        "protection" => "u1_protection", // 密保表
        "session" => "u1_session",     // 會話令牌表
        "users" => "u1_users",         // 使用者表
        "info" => "u1_info",           // 使用者資訊表
        "gender" => "u1_gender",       // 性別表
        "usergroup" => "u1_usergroup", // 使用者組表
        "history" => "u1_history",     // 操作歷史
        "encryption" => "u1_encryption", // 加密資訊表
        "device" => "u1_device",       // 裝置資訊表
        "stopword" => "u1_stopword",   // 敏感詞表
        "messages" => "u1_messages"    // 站內信
    ];
    /** @var array<string, mixed> Redis 資料庫設定 */
    public array $redis = [
        "rdb_enable" => true,
        "rdb_host" => "127.0.0.1",
        "rdb_port" => 6379,
        "rdb_password" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "rdb_id" => 1
    ];
    /** @var array<string, string> Redis 前綴對應表 */
    public array $redis_tables = [
        "frequencylimitation" => "f_",   // 存取頻率限制
        "sqldb" => "d_",                 // SQL 資料庫選擇器
        "session" => "s_",               // 登入狀態
        "convertimage" => "i_",          // 圖片轉換（需與 Go 程式碼同步修改）
        "convertvideo" => "v_",          // 影片轉換（需與 Go 程式碼同步修改）
        "rsa" => "r_",                   // RSA 金鑰快取
        "vcode1" => "c1_",               // 圖形驗證碼
        "vcode2" => "c2_"               // 簡訊或郵件驗證碼
    ];
    /** @var string|null SQL 查詢除錯日誌檔路徑（null 為不記錄） */
    public ?string $logfile_db = "/mnt/wwwroot/zyz/log/db.log";
    /** @var string|null 資料收發除錯日誌檔路徑（null 為不記錄） */
    public ?string $logfile_ud = "/mnt/wwwroot/zyz/log/submit.log";
    /** @var string|null 非同步執行命令日誌檔路徑（null 為不記錄） */
    public ?string $logfile_sh = "/mnt/wwwroot/zyz/log/exec.log";
    /** @var string|null 警告資訊日誌檔路徑（null 為不記錄） */
    public ?string $logfile_nl = "/mnt/wwwroot/zyz/log/nya.log";
}

/** 通訊保護設定（詳見「加密通訊處理流程.md」） */
class nyasetting_encrypt {
    /** @var bool 是否啟用 RSA 加密通訊 */
    public bool $enable = true;
    /** @var int Redis 快取時間（秒），0=禁用，-1=不限制 */
    public int $redisCacheTimeout = 3600;
    /** @var array<string, mixed> OpenSSL 金鑰設定 */
    public array $pkeyConfig = [
        "config" => "/www/server/php/73/src/ext/openssl/tests/openssl.cnf",
        "digest_alg" => "sha512",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA
    ];
    /** @var string|null 私鑰密碼（null 為不加密私鑰） */
    public ?string $privateKeyPassword = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
    /**
     * 預共享私鑰（Base64 內容，不含 PEM 標記）
     *
     * 在用戶端尚未與伺服器交換金鑰對之前使用。
     * 必須能被上方密碼解密。可設為 null。
     * @var string|null
     */
    public ?string $defaultPrivateKey = <<<'EOD'
xxxxxxx
xxxxxxx
xxxxxxx
EOD;
    /** @var string|null 預共享公鑰 */
    public ?string $defaultPublicKey = null;
}
/** 應用相關設定 */
class nyasetting_app {
    /** @var int 除錯模式：1=顯示錯誤，0=隱藏，其他=依 php.ini */
    public int $debug = 1;
    /** @var string 應用名稱（純字母，預留未實裝） */
    public string $app = "nyalogin_dev";
    /** @var string 應用顯示名稱 */
    public string $appname = "择与择";
    /** @var string 存取網址（以 / 結尾） */
    public string $appurl = "http://dev.zeyuze.com/user/";
    /** @var int GET 提交允許最大長度（位元組） */
    public int $maxlen_get = 2000;
    /** @var int POST 提交允許最大長度（位元組） */
    public int $maxlen_post = 1000000;
    /** @var string 時區（留空則依 php.ini） */
    public string $timezone = "Asia/Shanghai";
    /** @var bool 強制加密傳輸（已棄用，改用 RSA 模式） */
    public bool $alwayencrypt = false;
    /** @var int 允許用戶端與伺服器的時間差異（秒） */
    public int $timestamplimit = 60;
    /** @var int TOTP 時間補償 */
    public int $totpcompensate = 0;
    /** @var int TOTP 時間回溯次數（1=僅當前時間，每次回溯 30 秒） */
    public int $totptimeslice = 3;
    /** @var int 會話快取到 Redis 的最大時長（秒，已棄用） */
    public int $sessioncachemaxtime = 86400;
    /** @var bool 允許使用 quick 模式直接查詢 Redis 快取 */
    public bool $sessioncachefirst = true;
    /** @var bool 啟用介面存取頻率限制 */
    public bool $frequency = false;
    /**
     * 各功能時長設定（每個 IP 位址）
     * 格式：[多少秒內, 最多允許存取多少次, 簡寫]
     * @var array<string, array{0: int, 1: int, 2: string}>
     */
    public array $limittime = [
        "default" => [60, 30, "DF"],
        "encrypttest" => [60, 30, "ET"],
        "encryption" => [60, 300, "EN"],
        "signup" => [60, 30, "SI"],
        "login" => [60, 30, "LI"],
        "captcha" => [60, 30, "CP"],
        "vcode" => [60, 30, "VC"],
        "session" => [60, 30, "SE"],
        "fastsearch" => [60, 300, "FS"],
        "userinfo" => [60, 300, "UI"],
        "upload" => [60, 300, "UL"],
    ];
    /** @var string[] 多語言支援列表（第一位為預設語言，需與 template/ 中的檔名對應） */
    public array $language = ["zh-cn"];
    /** @var bool[] 允許的登入方式 [郵箱, 手機號, 密碼] */
    public array $logintype = [true, true, true];
    /** @var bool[] 註冊時允許的驗證碼方式 [郵箱, 手機號, 圖形驗證碼] */
    public array $logincaptcha = [true, true, false];
    /** @var bool 註冊時必須提供密碼 */
    public bool $needpassword = true;
    /** @var array<string, int|string> 預設新使用者設定 */
    public array $newuser = [
        "group" => 1,
        "subgroup" => 1,
        "nickname" => "无名氏",
        "pwdexpiration" => 315360000 // 密碼有效期（秒，預設 10 年）
    ];
    /** @var array<string, int> 長度限制 */
    public array $maxLen = [
        "name" => 30,
        "email" => 50,
        "address" => 200,
        "profile" => 100,
        "description" => 1000,
    ];
    /** @var int 性別列表 ID：0=標準雙性別，1=LGBT，2=Facebook 版 */
    public int $genderlist = 0;
    /** @var array<string, int> 每個裝置類型可登入的裝置數上限 */
    public array $maxlogin = [
        "all" => 3, "phone" => 1, "phone_emu" => 1,
        "pad" => 1, "pad_emu" => 1, "pc" => 1,
        "web" => 1, "debug" => 255, "other" => 1
    ];
    /** @var array<string, mixed> 關鍵詞過濾設定 */
    public array $wordfilter = [
        "enable" => 0,         // 0=禁用，1=從 Redis 讀入，2=從檔案讀入
        "rediskey" => "wordfilter",
        "jsonfile" => "filter_zh_cn.json",
        "wildcardchar" => '&', // 分隔符，用於同時滿足多個條件詞
        "replacechar" => '*',  // 和諧後取代字元
        "maxlength" => 5,      // 最大分析長度
        "punctuations" => "\t\n!@#$%^*()-=_+|\\/?<>,.'\";:{}[]"
    ];
    /** @var string 密碼鹽值 */
    public string $passwordsalt = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
    /** @var array<string, mixed> 檔案上傳設定 */
    public array $upload = [
        "tmpdir" => "../upload_tmp",
        "uploaddir" => "../upload",
        "datedir" => true,
        "chmod" => 0770,
        "maxsize" => ["all" => 314572800, "image" => 10485760, "gif" => 31457280, "video" => 314572800],
        "videoduration" => 600,
    ];
    /** @var string 後台媒體轉碼服務位址（空字串則不主動呼叫） */
    public string $mserver = "http://127.0.0.1:1081/";
    /** @var array<string, array<int, array{string, string}>> 允許上傳的檔案類型 */
    public array $uploadtype = [
        "image" => [["image/jpeg", "jpg"], ["image/png", "png"], ["image/gif", "gif"], ["image/webp", "webp"]],
        "video" => [["video/mp4", "mp4"], ["video/3gpp", "3gp"], ["video/quicktime", "mov"]]
    ];
    /** @var array<string, array<string, array{int, int, int}>> 圖片壓縮尺寸設定 */
    public array $imageresize = [
        "def" => ["R" => [0, 0, 0], "S" => [640, 360, 80], "M" => [1280, 720, 80], "L" => [1920, 1080, 80]],
        "pfbg" => ["R" => [0, 0, 0], "S" => [640, 360, 80], "M" => [1280, 720, 80], "L" => [1920, 1080, 80]],
        "pfimg" => ["R" => [0, 0, 0], "S" => [64, 64, 80], "M" => [128, 128, 80], "L" => [512, 512, 80]]
    ];
    /** @var array<string, array<string, array{int, int, int}>> 影片壓縮尺寸設定 */
    public array $videoresize = [
        "def" => ["R" => [0, 0, 0], "S" => [640, 360, 500], "M" => [1280, 720, 1000], "L" => [1920, 1080, 2000]]
    ];
    /** @var string[] 向前端按順序推薦的尺寸 */
    public array $recommendsize = ["L", "M", "S", "R"];
    /** @var string[] 向前端按順序推薦的副檔名 */
    public array $recommendext = ["gif", "webp", "jpg", "png", "mp4", "mov", "3gp"];
    /** @var array<string, mixed> FFmpeg / FFprobe 執行設定 */
    public array $ffconf = [
        "ffmpeg.binaries" => "/mnt/wwwroot/zyz/user/bin/ffmpeg/ffmpeg",
        "ffprobe.binaries" => "/mnt/wwwroot/zyz/user/bin/ffmpeg/ffprobe",
        "timeout" => 3600,
        "ffmpeg.threads" => 12
    ];
    /** @var array<string, string> 圖片/影片後台轉換指令碼路徑 */
    public array $convertconf = [
        "image" => "/mnt/wwwroot/zyz/user/bin/convertimage",
        "video" => "/mnt/wwwroot/zyz/user/bin/convertvideo"
    ];
    /** @var string[] 資訊模板：%1=傳送者1，%2=傳送者2，%3=更多傳送者數量 */
    public array $messageNum = ["%1 ", "%1 和 %2 ", "%1 和 %2 等 %3 位用户"];
    /** @var array<string, string> 資訊型別和對應的提示語 */
    public array $messageTmp = [
        "PAT" => "在贴文中提及了你", "CAT" => "在评论中提及了你",
        "CIP" => "对你的贴文发表了评论", "CIC" => "对你评论过的贴文发表了评论",
        "PLI" => "为你的贴文点赞", "CLI" => "为你的评论点赞", "FOL" => "关注了你"
    ];
    /** @var array<string, string> 功能性符號定義 */
    public array $separator = [
        "namelink" => "#",  // 使用者暱稱和 ID 的連接符
        "mention" => "@",   // 提及某人起始符
        "hashtag" => "#"   // 話題起始符
    ];
}

/** 驗證碼和密碼安全設定 */
class nyasetting_verify {
    /** @var bool 驗證碼除錯模式：請勿在正式環境啟用 */
    public bool $debug = true;
    /** @var string 除錯郵箱（填寫後會強制將驗證訊息傳送至此郵箱以進行測試） */
    public string $debugmail = "";
    /** @var array<string, int> 登入失敗幾次後開始需要驗證碼 */
    public array $needcaptcha = ["captcha" => 3];
    /** @var array<string, mixed> 圖形驗證碼設定 */
    public array $captcha = [
        "captcha" => true,
        "charset" => "qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890",
        "codelen" => 4,
        "imgdir" => "img",
        "imgname" => "captcha_"
    ];
    /** @var array<string, int> 各種驗證碼的超時時間（秒） */
    public array $timeout = ["captcha" => 600, "mail" => 300, "sms" => 300];
    /** @var array<string, int> 最大嘗試次數，超過則需重新獲取 */
    public array $maxtry = ["mail" => 3, "sms" => 3];
    /** @var array<string, int> 重新獲取驗證碼的冷卻時間（秒） */
    public array $cd = ["mail" => 60, "sms" => 60];
    /** @var array<string, int> 密碼強度要求 */
    public array $strongpassword = ["upper" => 0, "lower" => 0, "num" => 0, "symbol" => 0];
    /** @var string 密碼允許的特殊符號 */
    public string $passwordsymbol = "!@#$%^&*()_+-=[]{};':\\\"<>?,./";
    /** @var int[] 密碼長度要求 [最少, 最多] */
    public array $passwordlength = [6, 1024];
    /** @var int 會話令牌有效時間（秒，預設 180 天） */
    public int $tokentimeout = 15552000;
    /** @var int 預分配令牌有效時間（秒，預設 10 分鐘） */
    public int $pretokentimeout = 600;
    /** @var array<string, bool> 查詢使用者資料是否需要登入 */
    public array $needlogin = ["userinfo" => false];
    /** @var string 簡訊驗證碼模板（可用變數：{appname} {code} {time}） */
    public string $vcodetext_sns = "【{appname}】驗證碼：{code}（{time}分鐘內有效）。您正在操作{appname}通行證，請勿將驗證碼告訴他人，如果不是本人進行的操作請無視。";
    /** @var array<string, string> 郵件驗證碼模板 */
    public array $vcodetext_mail = [
        'Subject' => '{appname} 帳戶驗證郵件',
        'Body' => '<!doctype html><html xmlns=http://www.w3.org/1999/xhtml><head><meta content="text/html; charset=utf-8"http-equiv=Content-Type><title>verification code</title><meta content="width=device-width,initial-scale=1"name=viewport></head><body style=text-align:center><table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td width=20%><td align=left><h1>{appname}</h1><span>&emsp;&emsp;&emsp;&emsp;您好，您正在操作 {appname} 通行證。</span><br><br><span>&emsp;&emsp;&emsp;&emsp;如果不是您本人操作，請忽略此郵件。</span><br><br><span>&emsp;&emsp;&emsp;&emsp;您的郵件驗證碼為：</span><br><h2>&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;{code}</h2><span>&emsp;&emsp;&emsp;&emsp;驗證碼 {time} 分鐘內有效，</span><br><br><span>&emsp;&emsp;&emsp;&emsp;請勿將驗證碼告訴他人。</span><td width=20%></table></body></html>',
        'AltBody' => '您好，您正在操作 {appname} 通行證。如果不是您本人操作，請忽略此郵件。您的郵件驗證碼為： {code} 。驗證碼 {time} 分鐘內有效，請勿將驗證碼告訴他人。'
    ];
    /** @var array<string, int> 傳送引擎選擇：mail: 0=PHPMailer, 1=阿里雲；sms: 1=阿里雲 */
    public array $engine = ['mail' => 1, 'sms' => 1];
    /** @var array<string, mixed> SMTP 郵件伺服器設定 */
    public array $smtp = [
        'CharSet' => 'UTF-8', 'SMTPDebug' => 0,
        'Host' => '192.168.2.115', 'Port' => 25,
        'SMTPAuth' => true, 'Username' => 'noreply@mail.zeyuze.com',
        'Password' => 'server', 'SMTPSecure' => '',
        'FromAddr' => 'server@dev.uuu.moe', 'FromName' => '應用',
        'ReplyToAddr' => '', 'ReplyToName' => '', 'isHTML' => true
    ];
    /** @var array<string, array<string, mixed>> 阿里雲服務設定 */
    public array $aliyun = [
        'mail' => [
            'accessKeyIdSecret' => ['xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'],
            'RegionId' => 'cn-hangzhou', 'AddressType' => '1', 'ClickTrace' => '0'
        ],
        'sms' => [
            'accessKeyIdSecret' => ['xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'],
            'RegionId' => 'cn-hangzhou', 'SignName' => 'ApplicationName',
            'TemplateCode' => 'SMS_203675038', 'SmsUpExtendCode' => '', 'OutId' => ''
        ],
    ];
}

/**
 * 系統設定聚合類別
 *
 * 初始化所有子設定類別。請勿修改此類別結構。
 * @package NyarukoLogin
 */
class nyasetting {
    /** @var nyasetting_db 資料庫設定 */
    public nyasetting_db $db;
    /** @var nyasetting_encrypt 加密通訊設定 */
    public nyasetting_encrypt $enc;
    /** @var nyasetting_app 應用設定 */
    public nyasetting_app $app;
    /** @var nyasetting_verify 驗證和安全設定 */
    public nyasetting_verify $verify;

    function __construct() {
        $this->db = new nyasetting_db();
        $this->enc = new nyasetting_encrypt();
        $this->app = new nyasetting_app();
        $this->verify = new nyasetting_verify();
    }

    function __destruct() {
        $this->db = null;
        unset($this->db);
        $this->enc = null;
        unset($this->enc);
        $this->app = null;
        unset($this->app);
        $this->verify = null;
        unset($this->verify);
    }
}
