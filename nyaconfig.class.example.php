<?php
class nyasetting_db {
    //数据库连接设置
    //MySQL编码
    var $charset = "utf8mb4";
    //只读库，可指定多个数据库
    var $read_dbs = [
        [
            "db_host" => "127.0.0.1",
            "db_port" => "3306",
            "db_name" => "nyarukologin",
            "db_user" => "nyarukologinuser",
            "db_password" => "djdme8wEF9UOPfBa4Vvfx482ncfa4aSNWI5BX4ptpGVAol2iVocY3byGKIJjYt9qGWjftibIJ0ovVUk0nLS6OmwbJddJdIpmGKbzxjqaMareRbfo3H9oBIR2tD2wUEaZZWU9gkh00jAK3rbOhfJjTlhXFuozbH73aCHPGXcifNHtCSvOK0CbKiQPMPAka9ruXZmQF5uKlTHYoWacS0YVo8YWDnA9vVyj7bZVUFzAI6FMBB15nXITbfxrgDZBxOrW"
        ]
    ];
    //写入库，可指定多个数据库
    var $write_dbs = [
        [
            "db_host" => "127.0.0.1",
            "db_port" => "3306",
            "db_name" => "nyarukologin",
            "db_user" => "nyarukologinuser",
            "db_password" => "djdme8wEF9UOPfBa4Vvfx482ncfa4aSNWI5BX4ptpGVAol2iVocY3byGKIJjYt9qGWjftibIJ0ovVUk0nLS6OmwbJddJdIpmGKbzxjqaMareRbfo3H9oBIR2tD2wUEaZZWU9gkh00jAK3rbOhfJjTlhXFuozbH73aCHPGXcifNHtCSvOK0CbKiQPMPAka9ruXZmQF5uKlTHYoWacS0YVo8YWDnA9vVyj7bZVUFzAI6FMBB15nXITbfxrgDZBxOrW"
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
        "usergroup" => "u1_usergroup", //用户组表
        "history" => "u1_history", //日志
        "totp" => "u1_totp", //通信动态密码
        "device" => "u1_device" //设备信息表
    ];
    //Redis数据库设定
    var $redis = [
        "rdb_enable" => true,
        "rdb_host" => "127.0.0.1",
        "rdb_port" => 6379,
        "rdb_password" => "uHJBJd0ZQNh47C9KKlCFBO8y1LXALbUTyZzRakIlTxmy5ja2scR8w3xKpb7s78jA9FwQseFCAO3sz9U0h6jI8IZ9NL1q5XdErsGmyMrjh2XAjai10oboWPYeGx5MrqJ93Hs1IYSsgWTEDTRcLpEazdBNGV32ETmd7ePX78PqgguxkBhHb9p1D9N2Gd6EPz6X5KhrFKilr2rbQTWd1oPexJYSjGLgybjn3UnSUKovXSQkJADihDgpc7MKnXEaBjKuX4ogQrjcJGbxwaMAdYYDdCL0lSggQx7jkVnBEeqxPkk4QyIRbkj1PCEgJIAVv0eauQ88rgUdSlwxYWabw5Dy5kgdjMwkWmD3jeJXRnP5ApHDvgSAhh4JPk3jGsXfn60tkjQPiIkJwsPMLj8nSmyQtDzyOBAZlVvxwCI40DXnc13oAchhoNr5VMLDdG7oSwqyu0BCiYNzleIIQTQc5dBSWMekYhCcLUoeAyZLoHlIRi1nooUYcJUODIOD0gb9MvX3",
        "rdb_id" => 0
    ];
    //Redis数据库表设置
    var $redis_tables = [
        "frequencylimitation" => "u1_fl"
    ];
    //调试用：将每条SQL语句和返回内容记录在日志文件中,设置日志文件路径或null(不记录)
    var $logfile_db = "B:\\db.log";
    //调试用：将每条收到的数据和返回内容记录在日志文件中,设置日志文件路径或null(不记录)
    var $logfile_ud = "B:\\submit.log";
}

//应用相关设置
class nyasetting_app {
    var $debug = 1; //是否输出所有PHP错误,1显示,0禁止,其他数字:按照php.ini中的设定
    var $app = "nyalogin_dev"; //应用名称（纯字母）（预留未实装）
    var $appname = "测试应用1"; //应用描述
    var $appurl = "http://192.168.2.100/NyarukoLogin/"; //访问网址（/结尾）
    var $maxlen_get = 2000; //使用 get 方式提交时允许长度（字节）
    var $maxlen_post = 1000000; //使用 post 方式提交时允许长度（字节）
    var $timezone = "Asia/Shanghai"; //时区，留空则按照 php.ini
    var $alwayencrypt = false; //强制进行 TOTP/XXTEA 加密
    var $timestamplimit = 15; //允许客户端与服务端的时间差异（秒；如果客户端报告的话）
    var $totpcompensate = 0; //TOTP 补偿，需要客户端匹配
    var $totptimeslice = 1; //TOTP 宽限次数，尝试用之前 x 个验证码尝试解密
    var $frequency = false; //启动接口访问频率限制
    //各功能时长设定（每个IP地址）：[多少秒内,最多允许访问多少次]
    var $limittime = [
        "encrypttest" => [60,30], //测试接口
        "getlinktotp" => [60,30], //限制 连接加密TOTP申请 接口的访问频率
        "signup" => [60,30], //提交用户名密码进行注册
        "captcha" => [60,30], //获取图形验证码
    ];
    //多语言（应和template中的文件名对应），在第一位的为默认语言
    var $language = ["zh-cn"];
    //允许使用哪种方式注册，至少开其中一种[邮箱,手机号]
    var $logintype = [true,true];
    var $login_mail = true;
    var $login_phone = true;
    //默认新用户的
    var $newuser = [
        "group" => 1, //用户组 (group_list 表中的 ID)
        "nickname" => "无名氏", //昵称
        "nicknamelen" => 30, //昵称长度限制
        "emaillen" => 50, //邮件地址长度限制
        "pwdexpiration" => 94608000 //密码有效期（秒）
    ];
    //每个端可登录的设备数，key 和 device 表 type 的 enum 相对应
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
        "enable" => 1, //0:禁用, 1:从 Redis 读入, 2:从 $wordfilterjsonfile 读入
        "rediskey" => "wordfilter", //从 Redis 读入，指定 Redis Key
        "jsonfile" => "filter_zh_cn.json", //从文件读入，指定文件路径和名
        "wildcardchar" => '&', //分隔符，用于同时满足多个条件词。
        "replacechar" => '*', //如果返回和谐后的文字，已屏蔽的字符用此字符替代。
        "maxlength" => 5, //最大分析长度，指定多个条件时，两个条件词之间间隔超过此长度则不判为违规
        "punctuations" => "\t\n!@#$%^*()-=_+|\\/?<>,.'\";:{}[]" //特殊符号字符过滤器,不包括&，因为上面将&作为了通配符
    ];
    var $passwordsalt = "6yJv1R2TxyBVKOToumDbfBmqlDWr3PMK"; //密码盐
}

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
}

//初始化，不要修改
class nyasetting {
    var $db;
    var $app;
    var $verify;
    function __construct() {
        $this->db = new nyasetting_db();
        $this->app = new nyasetting_app();
        $this->verify = new nyasetting_verify();
    }
    function __destruct() {
        $this->db = null; unset($this->db);
        $this->app = null; unset($this->app);
        $this->verify = null; unset($this->verify);
    }
}
?>
