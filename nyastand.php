<?php
declare(strict_types=1);
/**
 * 子帳戶建立端點 - 處理建立子帳戶的請求，在主帳戶下建立附屬帳戶。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
require_once "src/nyacore.class.php";
require_once "src/nyastand.class.php";
require_once "src/nyauserinfoedit.class.php";
// IP檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("signup");
// 檢查用戶是否登入
$nlcore->sess->userLogged();
// 初始化類別
$stand = new stand();
// 實現功能
$returnClientData = $stand->addstand($nlcore->sess->argReceived,$nlcore->sess->appToken,$nlcore->sess->ipId,$nlcore->sess->userHash);
// 將資訊返回給客戶端
exit($nlcore->sess->encryptargv($returnClientData));
?>