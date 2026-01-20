<?php
declare(strict_types=1);
/**
 * 使用者模糊搜尋端點 - 根據關鍵字模糊搜尋使用者，回傳符合條件的使用者清單。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
require_once "src/nyacore.class.php";
require_once "src/nyasearch.class.php";
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("fastsearch");
// 實現功能
$nyasearch = new nyasearch();
$returnClientData = $nyasearch->search($nlcore->sess->argReceived);
// 將資訊返回給客戶端
$returnClientData = $nlcore->sess->encryptargv($returnClientData);
exit($returnClientData);
?>