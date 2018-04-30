<?php
    //数据库连接设置
    $nya_db_host = "";
    $nya_db_port = "";
    $nya_db_name = "";
    $nya_db_user = "";
    $nya_db_password = "";
    //数据库表设置
    $nya_db_user = "nyalogin_user";
    $nya_db_jur = "nyalogin_jur";
    $nya_db_safe = "nyalogin_safe";
    $nya_db_activity = "nyalogin_activity";
    //应用名称设置
    $nya_app = "nyalogin_dev";
    $nya_appname = "雅诗用户登录系统开发测试";
    $nya_wwwroot = "";
    //邮件发送设置
    $nya_mail_Enable = false;
    $nya_mail_FromEmail = "";
    $nya_mail_FromName = "";
    $nya_mail_SMTPHost = "";
    $nya_mail_SMTPPort = "";
    $nya_mail_FromMail = "";
    $nya_mail_Username = "";
    $nya_mail_Password = "";
    $nya_mail_CharSet = "UTF-8";
    $nya_mail_Encoding = "base64";
	//验证码设置
    $nya_vcode_verification = true;//进行验证码验证
	//基于php/内字体路径，可从多个字体中抽选
    $nya_vcode_font = array("../font/SourceCodePro-Regular.ttf");
    $nya_vcode_fontsize = 20;//字体大小
    $nya_vcode_bgchar = "poi";//背景干扰字符
    $nya_vcode_charset = "abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789";//可抽选字符
    $nya_vcode_codelen = 4;//验证码长度
    $nya_vcode_width = 130;//宽度
    $nya_vcode_height = 50;//高度
    $nya_vcode_imageformat = "jpg";// png / jpg / gif / wbmp(不推荐) / xbm(不推荐)
    $nya_vcode_line_density = 6;//背景线条密度
    $nya_vcode_bgchar_density = 33;//背景干扰字符密度
?>