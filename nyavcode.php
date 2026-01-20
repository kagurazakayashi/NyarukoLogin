<?php
declare(strict_types=1);
/**
 * 驗證碼傳送端點 - 透過簡訊或電子郵件傳送一次性驗證碼給使用者。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
require_once "src/nyacore.class.php";
require_once "src/nyavcode.class.php";
require_once "src/nyacaptcha.class.php";
require_once "src/nyaaliyun.class.php"; // 需要使用阿里雲服務則匯入此檔案
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("userinfo");
// 检查验证码是否正确
$nyacaptcha = new nyacaptcha();
if (!$nyacaptcha->verifycaptcha($nlcore->sess->appToken, ($nlcore->sess->argReceived["captcha"] ?? ''))) die();
// 實現功能
$nyavcode = new nyavcode();
$returnClientData = $nyavcode->getvcode($nlcore->sess->argReceived);
// 將資訊返回給客戶端
$returnClientData = $nlcore->sess->encryptargv($returnClientData);
exit($returnClientData);