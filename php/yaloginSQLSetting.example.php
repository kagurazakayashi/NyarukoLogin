<?php
/**
* 雅诗数据库设置存储类
*/
class YaloginSQLSetting
{
	//数据库连接设置
	public $db_host = "";
	public $db_port = "";
	public $db_name = "";
	public $db_user = "";
	public $db_password = ""; 

	//数据库表设置
	public $db_user_table = "yalogin_user";
	public $db_jurisdiction_table = "yalogin_jurisdiction";
	public $db_loginhistory_table = "yalogin_loginhistory";

	//数据库别名定义
	public $db_dbalias = array(
		"main" => "userdb"
	);

	//表别名定义
	public $db_tablealias = array(
		"users" => "yalogin_user"
		);

	//禁止直接查询
	public $db_safetable = array(
		"yalogin_jurisdiction",
		"yalogin_loginhistory"
		);
	public $db_safecolumn = array(
		"userpassword",
		"userpassword2",
		"userpasswordanswer1",
		"userpasswordanswer2",
		"userpasswordanswer3",
		"autologin"
		);

	//应用名称设置
	public $db_app = "yalogin_dev";
	public $db_appname = "雅诗用户登录系统开发测试";
	public $www_root = "https://www/user/";

	//邮件发送设置
	public $mail_Enable = true;
	public $mail_FromEmail = "";
	public $mail_FromName = "服务器管理姬（请勿回复）";
	public $mail_SMTPHost = "smtp.exmail.qq.com";
	public $mail_SMTPPort = "465";
	public $mail_FromMail = "";
	public $mail_Username = "";
	public $mail_Password = "";
	public $mail_CharSet = "UTF-8";
	public $mail_Encoding = "base64";

	//历史记录设置
	public $log_Registration_OK = true;
	public $log_Registration_Fail = true;
	public $log_Activation_OK = true;
	public $log_Activation_Fail = true;
	public $log_Login_OK = true;
	public $log_Login_Fail = true;
}

?>