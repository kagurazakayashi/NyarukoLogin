<?php
// 使用者登入
require_once "src/nyacore.class.php";
require_once "src/nyalogin.class.php";
require_once "src/nyacaptcha.class.php";
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("login");
// 實現功能
$nyalogin = new nyalogin();
$returnClientData = $nyalogin->login($nlcore->sess->argReceived,$nlcore->sess->appToken,$nlcore->sess->ipId);
// 將資訊返回給客戶端
$returnClientData = $nlcore->sess->encryptargv($returnClientData);
exit($returnClientData);
?>
