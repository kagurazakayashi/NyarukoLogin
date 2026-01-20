<?php
declare(strict_types=1);
/**
 * 站內訊息端點 - 處理站內訊息的傳送、接收與狀態更新操作。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
require_once "src/nyacore.class.php";
require_once "src/nyamessage.class.php";
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("userinfo");
// 檢查用戶是否登入
$nlcore->sess->userLogged();
// 實現功能
$nyamessage = new nyamessage();
$returnClientData = [];
if (isset($nlcore->sess->argReceived["readstat"])) {
    $returnClientData = $nyamessage->setStatFromUser();
} else if (isset($nlcore->sess->argReceived["text"]) && isset($nlcore->sess->argReceived["to"])) {
    $returnClientData = $nyamessage->newMessageFromUser();
} else {
    $returnClientData = $nyamessage->getMessageFromUser();
}
// 將資訊返回給客戶端
exit($nlcore->sess->encryptargv($returnClientData));