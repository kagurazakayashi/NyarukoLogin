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
            "db_password" => "kVN68LfRN6Y0QMF7Zww8e9ydVZm1UUraD5pzKd34Odnp4FsHh3nZWOwEYx5LzmHc8dY4iDHWytdVnppq6wYjKfVbrTJXgX9MCKIesOmmEw0Ut74IXdM86ayAo745m1BgdSrfercnlh9Fhqo8mFputMt4kbm88SL13aNcSpBtIXjUevPtI6rygjN1m5JA6ltj8GegR4xZxmAYyUmOBCoeVe0I5PWhcZxLv6qzHyVUvIkXaEcaHL0mlDpkjcA15uNM"
        ]
    ];
    //写入库，可指定多个数据库。所需权限：INSERT,UPDATE,DELETE
    var $write_dbs = [
        [
            "db_host" => "127.0.0.1",
            "db_port" => "3306",
            "db_name" => "nyarukologin",
            "db_user" => "nyarukologin_w",
            "db_password" => "pgfeaKtAYChjegoXlNq1r2sKrN4ucgrMFE8cypB6p4cgwdBYHYDLn2KTT3MAOuxIRq5wLjiM1pqgutqQIhBZIZZy85DXjQKB8ss5bpSQ0Em2bDSZs5xqfW8bMkNqwNcryJNsJpeXZrDihmJH1xOb4DZQo4kH0rI84O1jtajDUX2BX2jHp7DZp0aTfDpihXkcAZQYn9sGPsopO6CahX1UhP568GuqteRQSKa8B8KtrPKpUSotFuQGNujQRnjj1rFz"
        ]
    ];
    //数据库表设置
    var $tables = [
        "business" => "u1_business", //业务表
        "app" => "u1_app", //外部程序表
        "integral" => "u1_integral", //积分表
        "ip" => "u1_ip", //IP地址表
        "jurisdiction" => "u1_jurisdiction", //权限表
        "protection" => "u1_protection", //密保表
        "session" => "u1_session", //会话令牌表
        "users" => "u1_users", //用户表
        "info" => "u1_info", //用户信息表
        "gender" => "u1_gender", //性别表
        "usergroup" => "u1_usergroup", //用户组表
        "history" => "u1_history", //日志
        "encryption" => "u1_encryption", //加密信息表
        "device" => "u1_device", //设备信息表
    ];
    //Redis数据库设定
    var $redis = [
        "rdb_enable" => true,
        "rdb_host" => "127.0.0.1",
        "rdb_port" => 6379,
        "rdb_password" => "RfryNXkEUG3OnyDI06zK1mqWA7oQslqvc8IEgHh78BpACCaUZIN44zrlUyDIq8xL3unaZJpWd592DrJifvymOdLHCAIN0ycg1TzvatE2tJiu40kr06Aub1vfjYGIWadevBm70UDTClutBxWTjInt3fsZomDXQvYjrRktguqJeGT0RgfJA95XgTDQGqp2Eo7D33EhIU8zSQpjy3e97Bbl5yFvoqERz3wUBvcFd7K95Eas4DZpld3NV7fuk1tdh7Xa",
        "rdb_id" => 1
    ];
    //Redis数据库表设置
    var $redis_tables = [
        "frequencylimitation" => "f_", //访问频率限制库
        "sqldb" => "d_", //SQL数据库选择器
        "session" => "s_", //登录状态
        "convertimage" => "i_", //转换图片，需要和go代码同时修改
        "convertvideo" => "v_", //转换视频，需要和go代码同时修改
        "rsa" => "r_" //RSA密钥缓存
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
    var $privateKeyPassword = "qrCbuxCymh1BCMTDRGJvXt4I6AkOl9ozJVdAdMJ4y8wblCb0EhlXWumHauswoZYUYnNFpXLOcKP5O53EnKwuBW0YIH1u8RU88NADU0ljln5fbKaLD8Gh3EaAEG6Kbyo8gqEtSD7PbAlYsRGGsvTBKgwsS4pRI7NzmRmkj8P55UVNaEjPWaHhuZjSZmBNEUcHeuLlfIekDyZApNWmD3ohqpfTnBLg8IHDp9v1KEKHEXYxrkF2NBIVSwTEd2ZDSJesvW9gfdKiGuNSYPSfJtaZiTGufwkFkTnSY683mi6w9BMByh847cy9OscFS6L9rCOeEVWvjPlBfwKNguSHn5Q6YzzLqiYEA5Sx2BK0aDr8JZpfBei2tKqN5PdfraOXWT7hH8FWms5ixtC1A5zcLjCvKNa7c9RIynDV9o7oSiHggJ6F6f7uMpdCfYVAciPBl9gOk2RgfY1itPMPuPNqDZGV3ndlTZeyk0SaFfpqtqruE2cNPIwcYN9OMg3B0lojhVVw";
    // 設定內建公鑰和私鑰，在本地應用還沒有和伺服器獲取金鑰對之前，使用此預共享金鑰對進行加密。與客戶端中的相對應，總計 2 個金鑰對，與伺服器分別持有對方的公鑰和私鑰。無需開頭結尾標記，無需修改 base64 內容。此處私鑰必須能夠被上邊的密碼所解密。可以為 null 。
    var $defaultPrivateKey = <<<'EOD'
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
/hs=
EOD;
}
// 應用相關設定
class nyasetting_app {
    var $debug = 1; //是否输出所有PHP错误,1显示,0禁止,其他数字:按照php.ini中的设定
    var $app = "nyalogin_dev"; //应用名称（纯字母）（预留未实装）
    var $appname = "测试应用1"; //应用描述
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
        "default" => [60,30,"DF"], //默认值
        "encrypttest" => [60,30,"ET"], //测试接口
        "encryption" => [60,300,"EN"], //限制密钥对生成接口的访问频率
        "signup" => [60,30,"SI"], //提交用户名密码进行注册
        "login" => [60,30,"LI"], //用户登录
        "captcha" => [60,30,"CP"], //获取图形验证码
        "session" => [60,30,"SE"], //登录状态检查接口
        "fastsearch" => [60,300,"FS"], //快速模糊用户名搜索
        "userinfo" => [60,300,"UI"], //用户信息
        "upload" => [60,300,"UL"], //上传文件
    ];
    //多语言（应和template中的文件名对应），在第一位的为默认语言
    var $language = ["zh-cn"];
    // 允許使用哪種方式註冊，至少開其中一種[郵箱,手機號,子賬號]
    var $registertype = [true,true,true];
    //TODO: 允許使用哪種方式登入，至少開其中一種[郵箱,手機號,子賬號]
    var $logintype = [true,true,true];
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
    var $passwordsalt = "6yJv1R2TxyBVKOToumDbfBmqlDWr3PMK"; //密码盐
    var $upload = [
        "tmpdir" => "../upload_tmp", //异步二压临时文件夹（先建立好文件夹设好权限）
        "uploaddir" => "../upload", //媒体上传文件夹（先建立好文件夹设好权限）
        "datedir" => true, //按日期创建子文件夹
        "chmod" => 0770, //新建文件的权限
        "maxsize" => [ //每种媒体类型的最大文件大小限制
            "all" => 314572800, //300M
            "image" => 10485760, //10M
            "gif" => 5242880, //5M
            "video" => 314572800 //300M
        ],
        "videoduration" => 600, //视频最大时长限制（秒）
    ];
    var $mserver = "http://127.0.0.1:1081/"; //"http://127.0.0.1:1081/"; //指定后台服务地址,""的话不会主动调取转码服务
    var $uploadtype = [ //允许上传的文件类型 和 MIME/扩展名 对应关系
        "image" => [
            ["image/jpeg","jpg"],
            ["image/png","png"],
            ["image/gif","gif"],
            ["image/webp","webp"]
        ],
        "video" => [
            ["video/mp4","mp4"],
            ["video/quicktime","mov"]
        ]
    ];
    //标识名=>图片压缩尺寸（宽高）和图片压缩清晰度百分比，会作为文件名.后缀
    //清晰度百分比不支持gif。可支持多种配置。
    //设置中宽需要大于高，如果媒体比例宽高翻转（高大于宽），计算宽高也翻转。
    var $imageresize = [
        "def" => [ //普通图片，默认值，自带设置不要删
            "R" => [0,0,0], //尺寸为 0 则使用原始尺寸，清晰度为 0 则使用原始清晰度
            "S" => [640,360,80],
            "M" => [1280,720,80],
            "L" => [1920,1080,80]
        ],
        "pfbg" => [ //个人资料背景，自带设置不要删
            "R" => [0,0,0],
            "S" => [640,360,80],
            "M" => [1280,720,80],
            "L" => [1920,1080,80]
        ],
        "pfimg" => [ //头像，自带设置不要删
            "R" => [0,0,0],
            "S" => [64,64,80],
            "M" => [128,128,80],
            "L" => [512,512,80]
        ]
    ];
    var $videoresize = [
        "def" => [ //普通图片，默认值，自带设置不要删
            "R" => [0,0,0], //尺寸为 0 则使用原始尺寸，清晰度为 0 则使用原始清晰度
            "S" => [640,360,500],
            "M" => [1280,720,1000],
            "L" => [1920,1080,2000]
        ]
    ];
    // 向前端按顺序推荐尺寸
    var $recommendsize = ["L","M","S","R"];
    // 向前端按顺序推荐扩展名
    var $recommendext = ["gif","webp","jpg","png","mp4","mov"];
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
}

// 驗證碼設定
class nyasetting_verify {
    //哪种验证码在登录失败几次后开始被需要
    var $needcaptcha = [
        "captcha" => 3
    ];
    //图形验证码设置
    var $captcha = [
        "captcha" => true, //进行验证码验证
        "charset" => "qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890", //可抽选字符
        "codelen" => 4, //验证码长度
        "imgdir" => "image", //图片缓存文件夹，可以是相对路径（从根文件夹开始）
        "imgname" => "captcha_" //验证码图片前缀
    ];
    //各种验证码的超时时间
    var $timeout = [
        "captcha" => 20, //图形验证码
        "mail" => 86400
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
    var $passwordlength = [6,1024];
    //默认 token 有效时间(秒)
    var $tokentimeout = 15552000;
    var $needlogin = [
        "userinfo" => false
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
        $this->db = null; unset($this->db);
        $this->enc = null; unset($this->enc);
        $this->app = null; unset($this->app);
        $this->verify = null; unset($this->verify);
    }
}
