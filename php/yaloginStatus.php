<?php
/**
* 登录状态检查类
*/
class YaloginStatus
{
	function loaduserstatus()
	{
		require 'yaloginSQLSetting.php';
		$sqlSetting = new YaloginSQLSetting();
		
	}
}
$loginStatus = new YaloginStatus();
$loginStatus->loaduserstatus();
?>