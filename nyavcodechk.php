<?php
// 使用者資訊獲取
require_once "src/nyacore.class.php";
require_once "src/nyavcode.class.php";
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("userinfo");
// 實現功能
$nyavcode = new nyavcode();
$returnClientData = $nyavcode->chkVCode($nlcore->sess->argReceived);
// 將資訊返回給客戶端
$returnClientData = $nlcore->sess->encryptargv($returnClientData);
exit($returnClientData);
