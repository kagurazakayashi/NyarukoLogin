<?php
class nyasetting_db {
    //数据库连接设置
    var $db_host = "127.0.0.1";
    var $db_port = "3306";
    var $db_name = "userdb";
    var $db_user = "";
    var $db_password = "";
    //数据库表设置
    var $tb_user = "nyalogin_user";
    var $tb_jur = "nyalogin_jur";
    var $tb_safe = "nyalogin_safe";
    var $tb_activity = "nyalogin_activity";
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
?>