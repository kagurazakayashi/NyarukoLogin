<?php
    //数据库连接设置
    $kylg_db_host = "";
    $kylg_db_port = "";
    $kylg_db_name = "";
    $kylg_db_user = "";
    $kylg_db_password = "";
    //数据库表设置
    $kylg_db_user = "kylogin_user";
    $kylg_db_jur = "kylogin_jur";
    $kylg_db_safe = "kylogin_safe";
    $kylg_db_activity = "kylogin_activity";
    //应用名称设置
    $kylg_app = "kylogin_dev";
    $kylg_appname = "雅诗用户登录系统开发测试";
    $kylg_wwwroot = "";
    //邮件发送设置
    $kylg_mail_Enable = false;
    $kylg_mail_FromEmail = "";
    $kylg_mail_FromName = "";
    $kylg_mail_SMTPHost = "";
    $kylg_mail_SMTPPort = "";
    $kylg_mail_FromMail = "";
    $kylg_mail_Username = "";
    $kylg_mail_Password = "";
    $kylg_mail_CharSet = "UTF-8";
    $kylg_mail_Encoding = "base64";
	//验证码设置
    $kylg_vcode_verification = true;//进行验证码验证
	//基于php/内字体路径，可从多个字体中抽选
    $kylg_vcode_font = array("../font/SourceCodePro-Regular.ttf");
    $kylg_vcode_fontsize = 20;//字体大小
    $kylg_vcode_bgchar = "poi";//背景干扰字符
    $kylg_vcode_charset = "abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789";//可抽选字符
    $kylg_vcode_codelen = 4;//验证码长度
    $kylg_vcode_width = 130;//宽度
    $kylg_vcode_height = 50;//高度
    $kylg_vcode_imageformat = "jpg";// png / jpg / gif / wbmp(不推荐) / xbm(不推荐)
    $kylg_vcode_line_density = 6;//背景线条密度
    $kylg_vcode_bgchar_density = 33;//背景干扰字符密度
?>