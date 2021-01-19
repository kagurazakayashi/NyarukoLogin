<?php
// 发送或获取站内信
require_once "src/nyacore.class.php";
require_once "src/nyamessage.class.php";
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("userinfo");
// 檢查用戶是否登入
$nlcore->sess->userLogged();
// 實現功能
$nyamessage = new nyamessage();
$returnClientData = [];
if (isset($nlcore->sess->argReceived["text"]) && isset($nlcore->sess->argReceived["to"])) {
    $returnClientData = $nyamessage->newMessageFromUser();
} else {
    $returnClientData = $nyamessage->getMessageFromUser();
}
// 將資訊返回給客戶端
exit($nlcore->sess->encryptargv($returnClientData));