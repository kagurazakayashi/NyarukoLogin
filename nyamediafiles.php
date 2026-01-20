<?php
declare(strict_types=1);
/**
 * 媒體檔案查詢端點 - 根據請求查詢並回傳指定媒體檔案的路徑資訊。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
require_once "src/nyacore.class.php";
require_once "src/nyamediafiles.class.php";
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("default");
// 實現功能
$mediafiles = new nyamediafiles();
$returnClientData = $mediafiles->mediafiles($nlcore->sess->argReceived);
// 將資訊返回給客戶端
exit($nlcore->sess->encryptargv($returnClientData));