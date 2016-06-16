<?php
/**
* 用户对象
*/
class YaloginUserInfo
{
	public $id; //int11 用户ID
	public $userversion; //int5 用户记录版本
	public $hash; //用户唯一哈希码
	public $username, $usernickname, $useremail; //text 用户名, 昵称, 用户邮箱
	public $userpassword, $userpassword2; //textMD5 密码, 二级密码
	public $userpasswordenabled; //tinyint-bool 密码是否有效
	public $userenable; //tinyint-bool 用户是否启用
	public $userjurisdiction; //int 权限等级（外
	public $userpasswordquestion1, $userpasswordquestion2, $userpasswordquestion3; //text 密码提示问题
	public $userpasswordanswer1, $userpasswordanswer2, $userpasswordanswer3; //text 密码提示答案
	public $usersex, $userbirthday; //int 性别 0=未知 1=男 2=女 3... , date 生日
	public $userpasserr; //int 密码尝试错误次数
	public $userregistertime, $userregisterip, $userregisterapp; //datetime 用户注册时间,  text 用户注册IP, text 用户注册应用
	public $userlogintime, $userloginip, $userloginapp; //datetime 用户上次登录时间, text 用户上次登录IP，text 上次登录使用的应用
	//权限
	public $jurisdictiontext; //text 权限描述
	public $jurisdictioninherit; //text 拥有其它权限
	public $jurisdictionenable; //tinyint-bool 是否允许
	public $verifymail, $verifymailcode; //datetime 邮箱验证截止时间,空为通过, text 邮件验证码
}

class YaloginLoginHistoryInfo
{
	public $userlogintime, $userloginip, $userloginapp; //datetime 用户上次登录时间, text 用户上次登录IP，text 上次登录使用的应用
	public $userlogininfo; //登录结果信息
}
?>