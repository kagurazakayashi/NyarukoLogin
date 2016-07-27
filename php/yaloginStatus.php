<?php
/**
* 登录状态检查类
*/
require 'yaloginUserInfo.php';
require 'YaloginSQLSetting.php';
class YaloginStatus
{
	private $user;
	private $sqlset;

	function init()
	{
		$this->user = new YaloginUserInfo();
		$this->sqlset = new YaloginSQLSetting();
	}

	//登录状态
	function loginuser() {
		session_start();
		//检查登录 SESSION
		$cookiejson = isset($_SESSION["logininfo"]) ? $_SESSION["logininfo"] : null;
		$sesinfoarr = array('autologinby'=>"fail");
		if ($cookiejson == null) {
			//检查登录 COOKIE
			$cookiename = $this->cookiename();
			$cookiejson = isset($_COOKIE[$cookiename]) ? $_COOKIE[$cookiename] : null;
			if ($cookiejson == null) {
				//return null;
			} else {
				$sesinfoarr["autologinby"] = "cookie";
				$_SESSION["logininfo"] = $cookiejson;
			}
		} else {
			$sesinfoarr["autologinby"] = "session";
		}
		//取登录信息
		$cookiejsonarr = json_decode($cookiejson);
		if (isset($_SESSION["sessiontoken"]) && 
		isset($_SESSION["sessionname"]) && 
		isset($_SESSION["sessionid"]) && 
		isset($_SESSION["username"]) && 
		isset($_SESSION["userhash"]) && 
		isset($_SESSION["lifetime"])) {
			return array_merge($sesinfoarr,$cookiejsonarr);
		}
		return array_merge($sesinfoarr);
	}

	function cookiename($key = "") {
		if (strlen($key) > 0) {
			$key = "_".$key;
		}
		return "yalogin_".$this->sqlset->db_app.$key;
	}
}
?>