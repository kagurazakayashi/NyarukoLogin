<?php
declare(strict_types=1);
/**
 * 密碼變更端點 - 處理使用者變更密碼的請求，驗證舊密碼後更新為新密碼。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
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
