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
        "totp" => "u1_totp", //通信动态密码
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

// 通訊保護設定
class nyasetting_encrypt {
    var $enable = true;
    var $redisCacheTimeout = 3600; // Redis 快取時間（秒），0.禁用此功能 -1.不限制時間（通常不要這樣做）
    var $pkeyConfig = [
        "config" => "/www/server/php/73/src/ext/openssl/tests/openssl.cnf",
        "digest_alg" => "sha512",
        "private_key_bits" => 2048, // 位元組
        "private_key_type" => OPENSSL_KEYTYPE_RSA // 加密型別
    ];
    var $privateKeyPassword = "4g66rOhqE6ldjxfagG0GcPMiMAWzzjvPzd1fcNXpTL9pdAJVc2gKJ8eLF3JerErgk67Bmc4YR9Dbdctft376zW8h3fsxoAC7OJsWVFr9RDNlYZPYKrP6lQVvBhMReahSQvM9AFTv5yEhuIAY4qii7ZntbQdnkD8j8yBpgFuWptjZqIuw5rENyH2cDF4wuenzrkoGnnSJnDPjCikurfjaPjJRVRYzDQQylnCK66ZPUDeiXMiAnGAqsQvC3KXG5u5Gpt63vX3mV8e3uz3orZbMrwsXMKkMQPBaSh54nrnAvKeUgPFR7nLyO2rxtdEn3moEnGX99OQwwBUqbT6spDidti6WZxao0Mj2ciSAKWa83UEr9NEETebnvDRC9A2JmDe9wPR4SyHqVGpGeyCS4xqHvT3oqhy0uNXKoENeooDNa736j1KESbVIGgWOaS3RsHDbRwnp169QRuHQEFeH3FMa6IqvyJAvzDzUxqegfchDa5NHuiu7tSu8mZOMxYagcTMP"; // null 则不加密
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
        "captcha" => [60,30,"CP"], //获取图形验证码
        "session" => [60,30,"SE"], //登录状态检查接口
        "fastsearch" => [60,300,"FS"], //快速模糊用户名搜索
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
?>
