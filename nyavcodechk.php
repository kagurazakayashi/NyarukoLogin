<?php
declare(strict_types=1);
/**
 * 驗證碼檢查端點 - 驗證使用者提交的一次性驗證碼是否正確。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
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
