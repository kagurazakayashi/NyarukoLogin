<?php

/**
 * @description: 系統配置檔案
 * @package NyarukoLogin
 */

//資料庫連線設定
class nyasetting_db {
    //MySQL编码
    var $charset = "utf8mb4";
    //只读库，可指定多个数据库。所需权限：SELECT
    var $read_dbs = [
        [
            "db_host" => "127.0.0.1",
            "db_port" => "3306",
            "db_name" => "nyarukologin",
            "db_user" => "nyarukologin_r",
            "db_password" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
        ]
    ];
    //写入库，可指定多个数据库。所需权限：INSERT,UPDATE,DELETE
    var $write_dbs = [
        [
            "db_host" => "127.0.0.1",
            "db_port" => "3306",
            "db_name" => "nyarukologin",
            "db_user" => "nyarukologin_w",
            "db_password" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
        ]
    ];
    //数据库表设置
    var $tables = [
        "business" => "u1_business", //业务表
        "app" => "u1_app", //外部程序表
        "integral" => "u1_integral", //积分表
        "ip" => "u1_ip", //IP地址表
        "permission" => "u1_permission", //权限表
        "protection" => "u1_protection", //密保表
        "session" => "u1_session", //会话令牌表
        "users" => "u1_users", //用户表
        "info" => "u1_info", //用户信息表
        "gender" => "u1_gender", //性别表
        "usergroup" => "u1_usergroup", //用户组表
        "history" => "u1_history", //日志
        "encryption" => "u1_encryption", //加密信息表
        "device" => "u1_device", //设备信息表
        "stopword" => "u1_stopword", //敏感词表
        "messages" => "u1_messages" //站內信
    ];
    //Redis数据库设定
    var $redis = [
        "rdb_enable" => true,
        "rdb_host" => "127.0.0.1",
        "rdb_port" => 6379,
        "rdb_password" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "rdb_id" => 1
    ];
    //Redis数据库表设置
    var $redis_tables = [
        "frequencylimitation" => "f_", //访问频率限制库
        "sqldb" => "d_", //SQL数据库选择器
        "session" => "s_", //登录状态
        "convertimage" => "i_", //转换图片，需要和go代码同时修改
        "convertvideo" => "v_", //转换视频，需要和go代码同时修改
        "rsa" => "r_", //RSA密钥缓存
        "vcode1" => "c1_", //图形验证码
        "vcode2" => "c2_" //短信或邮件验证码
    ];
    //调试用：将每条SQL语句和返回内容记录在日志文件中,设置日志文件路径或null(不记录)（请先创建好并设置好权限）
    var $logfile_db = "/mnt/wwwroot/zyz/log/db.log";
    //调试用：将每条收到的数据和返回内容记录在日志文件中,设置日志文件路径或null(不记录)（请先创建好并设置好权限）
    var $logfile_ud = "/mnt/wwwroot/zyz/log/submit.log";
    //调试用：将异步执行的命令行结果记录在日志文件中,设置日志文件路径或null(不记录)（请先创建好并设置好权限）
    var $logfile_sh = "/mnt/wwwroot/zyz/log/exec.log";
    //调试用：将警告信息记录在日志文件中,设置日志文件路径或null(不记录)（请先创建好并设置好权限）
    var $logfile_nl = "/mnt/wwwroot/zyz/log/nya.log";
}

// 通訊保護設定，詳見文件「加密通信处理流程.md」
class nyasetting_encrypt {
    var $enable = true;
    var $redisCacheTimeout = 3600; // Redis 快取時間（秒），0.禁用此功能 -1.不限制時間（通常不要這樣做）
    var $pkeyConfig = [
        "config" => "/www/server/php/73/src/ext/openssl/tests/openssl.cnf",
        "digest_alg" => "sha512",
        "private_key_bits" => 4096, // 位元組
        "private_key_type" => OPENSSL_KEYTYPE_RSA // 加密型別
    ];
    // 為私鑰設定密碼， 設定為 null 則不加密
    var $privateKeyPassword = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
    // 設定內建公鑰和私鑰，在本地應用還沒有和伺服器獲取金鑰對之前，使用此預共享金鑰對進行加密。與客戶端中的相對應，總計 2 個金鑰對，與伺服器分別持有對方的公鑰和私鑰。無需開頭結尾標記，無需修改 base64 內容。此處私鑰必須能夠被上邊的密碼所解密。可以為 null 。
    var $defaultPrivateKey = <<<'EOD'
xxxxxxx
xxxxxxx
xxxxxxx
EOD;
}
// 應用相關設定
class nyasetting_app {
    var $debug = 1; //是否输出所有PHP错误,1显示,0禁止,其他数字:按照php.ini中的设定
    var $app = "nyalogin_dev"; //应用名称（纯字母）（预留未实装）
    var $appname = "择与择"; //应用描述
    var $appurl = "http://dev.zeyuze.com/user/"; //访问网址（/结尾）
    var $maxlen_get = 2000; //使用 get 方式提交时允许长度（字节）
    var $maxlen_post = 1000000; //使用 post 方式提交时允许长度（字节）
    var $timezone = "Asia/Shanghai"; //时区，留空则按照 php.ini
    var $alwayencrypt = false; //强制进行 TOTP/XXTEA 加密
    var $timestamplimit = 60; //允许客户端与服务端的时间差异（秒；如果客户端报告的话）
    var $totpcompensate = 0; //TOTP 补偿，需要客户端匹配
    var $totptimeslice = 3; //尝试用之前 x 个验证码尝试解密次数，1为当前时间（至少为1），每次回溯时间为30秒。
    var $sessioncachemaxtime = 86400; //会话 token 缓存到 Redis 的最大时长（秒）
    var $sessioncachefirst = true; //允许使用 quick 来直接访问 Redis token 缓存加速
    var $frequency = false; //启动接口访问频率限制
    //各功能时长设定（每个IP地址）：[多少秒内,最多允许访问多少次,简写]
    var $limittime = [
        "default" => [60, 30, "DF"], //默认值
        "encrypttest" => [60, 30, "ET"], //测试接口
        "encryption" => [60, 300, "EN"], //限制密钥对生成接口的访问频率
        "signup" => [60, 30, "SI"], //提交用户名密码进行注册
        "login" => [60, 30, "LI"], //用户登录
        "captcha" => [60, 30, "CP"], //获取图形验证码
        "vcode" => [60, 30, "VC"], //获取短信或邮件验证码
        "session" => [60, 30, "SE"], //登录状态检查接口
        "fastsearch" => [60, 300, "FS"], //快速模糊用户名搜索
        "userinfo" => [60, 300, "UI"], //用户信息
        "upload" => [60, 300, "UL"], //上传文件
    ];
    //多语言（应和template中的文件名对应），在第一位的为默认语言
    var $language = ["zh-cn"];
    // 允許使用哪種方式登入，至少開其中一種[郵箱,手機號,密碼]
    var $logintype = [true, true, true];
    // 註冊時允許用哪種驗證碼進行驗證，至少開其中一種[郵箱,手機號,验证码]
    var $logincaptcha = [true, true, false];
    // 註冊時必須提供密碼
    var $needpassword = true;
    // 預設新使用者的：
    var $newuser = [
        "group" => 1, // 使用者組 (group_list 表中的 ID)
        "subgroup" => 1, // 子賬戶使用者組
        "nickname" => "无名氏", // 暱稱
        "pwdexpiration" => 315360000 // 密碼有效期（秒）
    ];
    // 長度限制
    var $maxLen = [
        "name" => 30, // 暱稱
        "email" => 50, // 電郵地址
        "address" => 200, // 地址
        "profile" => 100, // 签名
        "description" => 1000, // 资料
    ];
    // 🏳️‍⚧️ 性别列表ID  0 标准双性别 1 LGBT机构版 2 Facebook版 https://www.guokr.com/article/438003/
    var $genderlist = 0;
    //每个端可登录的设备数，key 和 device/session 表 type/devtype 的 enum 相对应
    var $maxlogin = [
        "all" => 3,
        "phone" => 1,
        "phone_emu" => 1,
        "pad" => 1,
        "pad_emu" => 1,
        "pc" => 1,
        "web" => 1,
        "debug" => 255,
        "other" => 1
    ];
    //'phone','phone_emu','pad','pad_emu','pc','web','debug','other'
    //关键词过滤设置，数据应全转换为小写，将&作为通配符的 json
    //违禁词列表为 JSON 一维数组，每个字符串中可以加「wildcardchar」分隔以同时满足两个条件词。
    var $wordfilter = [
        "enable" => 0, //0:禁用, 1:从 Redis 读入, 2:从 $wordfilterjsonfile 读入
        "rediskey" => "wordfilter", //从 Redis 读入，指定 Redis Key
        "jsonfile" => "filter_zh_cn.json", //从文件读入，指定文件路径和名
        "wildcardchar" => '&', //分隔符，用于同时满足多个条件词。
        "replacechar" => '*', //如果返回和谐后的文字，已屏蔽的字符用此字符替代。
        "maxlength" => 5, //最大分析长度，指定多个条件时，两个条件词之间间隔超过此长度则不判为违规
        "punctuations" => "\t\n!@#$%^*()-=_+|\\/?<>,.'\";:{}[]" //特殊符号字符过滤器,不包括&，因为上面将&作为了通配符
    ];
    var $passwordsalt = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"; //密码盐
    var $upload = [
        "tmpdir" => "../upload_tmp", //异步二压临时文件夹（先建立好文件夹设好权限）
        "uploaddir" => "../upload", //媒体上传文件夹（先建立好文件夹设好权限）
        "datedir" => true, //按日期创建子文件夹
        "chmod" => 0770, //新建文件的权限
        "maxsize" => [ //每种媒体类型的最大文件大小限制(B)
            "all" => 314572800, //300M
            "image" => 10485760, //10M
            "gif" => 31457280, //30M
            "video" => 314572800 //300M
        ],
        "videoduration" => 600, //视频最大时长限制（秒）
    ];
    var $mserver = "http://127.0.0.1:1081/"; //"http://127.0.0.1:1081/"; //指定后台服务地址,""的话不会主动调取转码服务
    var $uploadtype = [ //允许上传的文件类型 和 MIME/扩展名 对应关系
        "image" => [
            ["image/jpeg", "jpg"],
            ["image/png", "png"],
            ["image/gif", "gif"],
            ["image/webp", "webp"]
        ],
        "video" => [
            ["video/mp4", "mp4"],
            ["video/3gpp", "3gp"],
            ["video/quicktime", "mov"]
        ]
    ];
    //标识名=>图片压缩尺寸（宽高）和图片压缩清晰度百分比，会作为文件名.后缀
    //清晰度百分比不支持gif。可支持多种配置。
    //设置中宽需要大于高，如果媒体比例宽高翻转（高大于宽），计算宽高也翻转。
    var $imageresize = [
        "def" => [ //普通图片，默认值，自带设置不要删
            "R" => [0, 0, 0], //尺寸为 0 则使用原始尺寸，清晰度为 0 则使用原始清晰度
            "S" => [640, 360, 80],
            "M" => [1280, 720, 80],
            "L" => [1920, 1080, 80]
        ],
        "pfbg" => [ //个人资料背景，自带设置不要删
            "R" => [0, 0, 0],
            "S" => [640, 360, 80],
            "M" => [1280, 720, 80],
            "L" => [1920, 1080, 80]
        ],
        "pfimg" => [ //头像，自带设置不要删
            "R" => [0, 0, 0],
            "S" => [64, 64, 80],
            "M" => [128, 128, 80],
            "L" => [512, 512, 80]
        ]
    ];
    var $videoresize = [
        "def" => [ //普通图片，默认值，自带设置不要删
            "R" => [0, 0, 0], //尺寸为 0 则使用原始尺寸，清晰度为 0 则使用原始清晰度
            "S" => [640, 360, 500],
            "M" => [1280, 720, 1000],
            "L" => [1920, 1080, 2000]
        ]
    ];
    // 向前端按顺序推荐尺寸
    var $recommendsize = ["L", "M", "S", "R"];
    // 向前端按顺序推荐扩展名
    var $recommendext = ["gif", "webp", "jpg", "png", "mp4", "mov", "3gp"];
    // 路径要求：① php.ini 的 open_basedir 中允许该路径，或拷贝执行文件到网站目录。 ② 不要出现空格和非英文。 ③ 尽量用绝对路径。
    //ffmpeg 和 ffprobe 执行文件路径。
    var $ffconf = [
        "ffmpeg.binaries" => "/mnt/wwwroot/zyz/user/bin/ffmpeg/ffmpeg",
        "ffprobe.binaries" => "/mnt/wwwroot/zyz/user/bin/ffmpeg/ffprobe",
        "timeout" => 3600, //超时
        "ffmpeg.threads" => 12 //进程数
    ];
    //设置图片视频后台转换脚本路径。
    var $convertconf = [
        "image" => "/mnt/wwwroot/zyz/user/bin/convertimage",
        "video" => "/mnt/wwwroot/zyz/user/bin/convertvideo"
    ];
    // 資訊模板
    // 資訊中內容:  %0=接收者  %1=傳送者1  %2=傳送者2  %3=還有更多傳送者數量
    var $messageNum = ["%1 ", "%1 和 %2 ", "%1 和 %2 等 %3 位用户"];
    // 資訊型別和對應的提示語，三個字母。为空值时视为不合成任何文字
    var $messageTmp = [
        "PAT" => "在贴文中提及了你",
        "CAT" => "在评论中提及了你",
        "CIP" => "对你的贴文发表了评论",
        "CIC" => "对你评论过的贴文发表了评论",
        "PLI" => "为你的贴文点赞",
        "CLI" => "为你的评论点赞",
        "FOL" => "关注了你"
    ];
}

// 驗證碼設定
class nyasetting_verify {
    // DEBUG 验证码调试模式：请勿在生产环境开启：
    var $debug = true;
    // DEBUG 如果填写邮件地址，会强制将短信和邮件发送到此测试邮箱
    var $debugmail = "";
    //哪种验证码在登录失败几次后开始被需要
    var $needcaptcha = [
        "captcha" => 3
    ];
    //图形验证码设置
    var $captcha = [
        "captcha" => true, //进行验证码验证
        "charset" => "qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890", //可抽选字符
        "codelen" => 4, //验证码长度
        "imgdir" => "img", //图片缓存文件夹，可以是相对路径（从根文件夹开始）
        "imgname" => "captcha_" //验证码图片前缀
    ];
    //各种验证码的超时时间（秒）
    var $timeout = [
        "captcha" => 600, //图形验证码
        "mail" => 300,
        "sms" => 300
    ];
    // 最大尝试次数，超过该次数需要重新生成
    var $maxtry = [ // captcha 强制为 1
        "mail" => 3,
        "sms" => 3
    ];
    // 多久可以再获取下一个验证码（秒）
    var $cd = [ // captcha 强制为 0
        "mail" => 60,
        "sms" => 60
    ];
    //密码强度设置，设置的密码必须要有 key 至少 val 个：
    var $strongpassword = [
        "upper" => 0,
        "lower" => 0,
        "num" => 0,
        "symbol" => 0
    ];
    //密码只能包括以下符号
    var $passwordsymbol = "!@#$%^&*()_+-=[]{};':\\\"<>?,./";
    //密码长度要求，[最少,最多]多少位
    var $passwordlength = [6, 1024];
    //默认 token 有效时间(秒)
    var $tokentimeout = 15552000;
    var $needlogin = [
        "userinfo" => false
    ];
    // 短信和邮件模板，可用插入变量 {appname} {code} {time}
    var $vcodetext_sns = "【{appname}】验证码：{code}（{time}分钟内有效）。您正在操作{appname}通行证，请勿将验证码告诉他人，如果不是本人进行的操作请无视。";
    var $vcodetext_mail = [
        'Subject' => '{appname} 账户验证邮件',
        'Body' => '<!doctype html><html xmlns=http://www.w3.org/1999/xhtml><head><meta content="text/html; charset=utf-8"http-equiv=Content-Type><title>verification code</title><meta content="width=device-width,initial-scale=1"name=viewport></head><body style=text-align:center><table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td width=20%><td align=left><h1>{appname}</h1><span>&emsp;&emsp;&emsp;&emsp;您好，您正在操作 {appname} 通行证。</span><br><br><span>&emsp;&emsp;&emsp;&emsp;如果不是您本人操作，请忽略此邮件。</span><br><br><span>&emsp;&emsp;&emsp;&emsp;您的邮件验证码为：</span><br><h2>&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;{code}</h2><span>&emsp;&emsp;&emsp;&emsp;验证码 {time} 分钟内有效，</span><br><br><span>&emsp;&emsp;&emsp;&emsp;请勿将验证码告诉他人。</span><td width=20%></table></body></html>',
        'AltBody' => '您好，您正在操作 {appname} 通行证。如果不是您本人操作，请忽略此邮件。您的邮件验证码为： {code} 。验证码 {time} 分钟内有效，请勿将验证码告诉他人。'
    ];
    var $engine = [ // 選擇傳送引擎
        'mail' => 1, // 0.PHPMailer, 1.阿里云邮件推送, 其他.关闭此验证
        'sms' => 1 // 1.阿里云短信服务, 其他.关闭此验证
    ];
    var $smtp = [
        'CharSet' => 'UTF-8',
        'SMTPDebug' => 0,
        'Host' => '192.168.2.115',
        'Port' => 25, //465
        'SMTPAuth' => true,
        'Username' => 'noreply@mail.zeyuze.com',
        'Password' => 'server',
        'SMTPSecure' => '', //ssl
        'FromAddr' => 'server@dev.uuu.moe',
        'FromName' => '应用',
        'ReplyToAddr' => '',
        'ReplyToName' => '',
        'isHTML' => true
    ];
    var $aliyun = [ // 阿里雲配置
        'mail' => [
            'accessKeyIdSecret' => ['xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'], // ['<accessKeyId>', '<accessSecret>']
            'RegionId' => 'cn-hangzhou',
            'AddressType' => '1', // *地址型別。取值： 0：為隨機賬號 1：為發信地址
            'ClickTrace' => '0', // 1：為開啟資料跟蹤功能 0（預設）：為關閉資料跟蹤功能。
            // 剩余配置从 $smtp 中读取： 'Username','ReplyToAddr','ReplyToAddr'，'ReplyToName'
        ],
        'sms' => [
            'accessKeyIdSecret' => ['xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'], // ['<accessKeyId>', '<accessSecret>']
            'RegionId' => 'cn-hangzhou',
            'SignName' => '择与择', // *簡訊簽名名稱。請在控制檯簽名管理頁面簽名名稱一列檢視。必須是已新增、並透過稽核的簡訊簽名。
            'TemplateCode' => 'SMS_203675038', // *簡訊模板ID。請在控制檯 模板管理 頁面 模板CODE 一列檢視。必須是已新增、並透過稽核的簡訊簽名；且傳送國際/港澳臺訊息時，請使用國際/港澳臺簡訊模版。
            'SmsUpExtendCode' => '', // 上行簡訊擴充套件碼，無特殊需要此欄位的使用者請忽略此欄位。
            'OutId' => '' // 外部流水擴充套件欄位
        ],
    ];
}

//初始化，不要修改
class nyasetting {
    var $db;
    var $enc;
    var $app;
    var $verify;
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
