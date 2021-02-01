<?php
// 使用者登入
require_once "src/nyacore.class.php";
require_once "src/nyachangepassword.class.php";
require_once "src/nyacaptcha.class.php";
require_once "src/nyavcode.class.php";
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("default");
// 實現功能
$nyachangepassword = new nyachangepassword();
$returnClientData = $nyachangepassword->changepassword($nlcore->sess->argReceived);
// 將資訊返回給客戶端
$returnClientData = $nlcore->sess->encryptargv($returnClientData);
exit($returnClientData);
?>
