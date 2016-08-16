<?php
/**
* 登录状态检查类
*/
if(class_exists('yaloginUserInfo') != true) {
	require 'yaloginUserInfo.php';
}
if(class_exists('YaloginSQLSetting') != true) {
    require 'yaloginSQLSetting.php';
}
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
		if(!isset($_SESSION)){ session_start(); }
		//检查登录 SESSION
		//echo "<br>[YaloginStatus]chk SESSION(logininfo)...";
		$cookiejson = isset($_SESSION["logininfo"]) ? $_SESSION["logininfo"] : null;
		$cookiename = $this->cookiename();
		$sesinfoarr = array('autologinby'=>"fail",'cookiename'=>$cookiename);
		if ($cookiejson == null) {
			//检查登录 COOKIE
			//echo "NO<br>[YaloginStatus]chk COOKIE($cookiename)...";
			$cookiejson = isset($_COOKIE[$cookiename]) ? $_COOKIE[$cookiename] : null;
			if ($cookiejson == null) {
				//echo "NO<br>[YaloginStatus]fail!<br>";
				//return null;
			} else {
				//echo "YES";
				$sesinfoarr["autologinby"] = "cookie";
				$_SESSION["logininfo"] = $cookiejson;
			}
		} else {
			//echo "YES<br>";
			$sesinfoarr["autologinby"] = "session";
		}
		//取登录信息
		if ($sesinfoarr["autologinby"] != "fail") {
			$cookiejsonarr = json_decode($cookiejson,true);

			// if (isset($_SESSION["sessiontoken"]) && 
			// isset($_SESSION["sessionname"]) && 
			// isset($_SESSION["sessionid"]) && 
			// isset($_SESSION["username"]) && 
			// isset($_SESSION["userhash"]) && 
			// isset($_SESSION["lifetime"])) {
				return array_merge($sesinfoarr,$cookiejsonarr);
			// }
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