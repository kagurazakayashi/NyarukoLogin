<?php
// 使用者登出
require_once "src/nyacore.class.php";
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("login");
// 實現功能
$returnClientData = $nlcore->sess->logout();
// 將資訊返回給客戶端
$returnClientData = $nlcore->sess->encryptargv($returnClientData);
exit($returnClientData);
?>
