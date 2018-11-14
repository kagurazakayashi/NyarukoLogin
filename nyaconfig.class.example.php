<?php
class nyasetting_db {
    //数据库连接设置
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
    //屏蔽词库，可指定多个数据库
    var $stopword_dbs = [
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
        "business" => "business", //业务表
        "change" => "change", //信息和积分变更日志
        "external_app" => "external_app", //外部程序表
        "integral" => "integral", //积分表
        "ip_address" => "ip_address", //IP地址表
        "jurisdiction" => "jurisdiction", //权限表
        "password_protection" => "password_protection", //密保表
        "session_token" => "session_token", //会话令牌表
        "users" => "users", //用户表
        "users_information" => "users_information", //用户信息表
        "user_group" => "user_group", //用户组表
        "verification_sending_log" => "verification_sending_log", //验证信息发送日志
        "stopword" => "stopword" //敏感词表
    ];
}
//应用名称设置
class nyasetting_app {
    var $app = "nyalogin_dev";
    var $appname = "nyalogin_test";
    var $wwwroot = "";
}
//邮件发送设置
class nyasetting_mail {
    var $enable = false;
    var $fromEmail = "";
    var $fromName = "";
    var $smtpHost = "";
    var $smtpPort = "";
    var $fromMail = "";
    var $username = "";
    var $password = "";
    var $charSet = "UTF-8";
    var $encoding = "base64";
}
//验证码设置
class nyasetting_vcode {
    var $verification = true;//进行验证码验证
	//基于php/内字体路径，可从多个字体中抽选
    var $font = array("../font/SourceCodePro-Regular.ttf");
    var $fontsize = 20;//字体大小
    var $bgchar = "poi";//背景干扰字符
    var $charset = "abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789"; //可抽选字符
    var $codelen = 4;//验证码长度
    var $width = 130;//宽度
    var $height = 50;//高度
    var $imageformat = "jpg";// png / jpg / gif / wbmp(不推荐) / xbm(不推荐)
    var $line_density = 6;//背景线条密度
    var $bgchar_density = 33;//背景干扰字符密度
}
//初始化，不要修改
class nyasetting {
    var $db;
    var $app;
    var $mail;
    var $vcode;
    function __construct() {
        $this->db = new nyasetting_db();
        $this->app = new nyasetting_app();
        $this->mail = new nyasetting_mail();
        $this->vcode = new nyasetting_vcode();
    }
    function __destruct() {
        $this->db = null; unset($this->db);
        $this->app = null; unset($this->app);
        $this->mail = null; unset($this->mail);
        $this->vcode = null; unset($this->vcode);
    }
}
?>