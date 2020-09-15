<?php
// 使用者資訊獲取
require_once "src/nyacore.class.php";
require_once "src/nyauserinfo.class.php";
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("userinfo");
// 檢查用戶是否登入
$nlcore->sess->userLogged();
// 實現功能
$userinfo = new userinfo();
$returnClientData = $userinfo->getuserinfo($nlcore->sess->userHash);
// 將資訊返回給客戶端
$returnClientData = $nlcore->sess->encryptargv($returnClientData);
exit($returnClientData);
