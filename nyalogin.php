<?php
declare(strict_types=1);
/**
 * 使用者登入端點 - 處理使用者登入請求，驗證使用者名稱與密碼，並回傳登入狀態與使用者資訊。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
require_once "src/nyacore.class.php";
require_once "src/nyalogin.class.php";
require_once "src/nyacaptcha.class.php";
require_once "src/nyavcode.class.php";
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("login");
// 實現功能
$nyalogin = new nyalogin();
$returnClientData = $nyalogin->login($nlcore->sess->argReceived,$nlcore->sess->appToken,$nlcore->sess->ipId);
// 將資訊返回給客戶端
$returnClientData = $nlcore->sess->encryptargv($returnClientData);
exit($returnClientData);
?>
