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
	public $db_user_table = "";
	public $db_jurisdiction_table = "";
	public $db_loginhistory_table = "";

	//应用名称设置
	public $db_app = "";
	public $db_appname = "";
	public $www_root = "";

	//邮件发送设置
	public $mail_FromEmail = "";
	public $mail_FromName = "服务器管理姬（请勿回复）";
	public $mail_SMTPHost = "";
	public $mail_SMTPPort = "465";
	public $mail_FromMail = "";
	public $mail_Username = "";
	public $mail_Password = "";
	public $mail_CharSet = "UTF-8";
	public $mail_Encoding = "base64";
}

?>