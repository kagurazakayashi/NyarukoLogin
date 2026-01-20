<?php
declare(strict_types=1);
/**
 * 帳戶註冊端點 - 處理新使用者註冊請求，建立帳戶並進行必要的驗證。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
require_once "src/nyacore.class.php";
require_once "src/nyacaptcha.class.php";
require_once "src/nyaverification.class.php";
require_once "src/nyasignup.class.php";
require_once "src/nyauserinfoedit.class.php";
// IP檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("signup");
// 實現功能
$nyasignup = new nyasignup();
$returnClientData = $nyasignup->adduser($nlcore->sess->argReceived,$nlcore->sess->appToken,$nlcore->sess->ipId);
// 測試密碼生成器用，不用時遮蔽
// if (isset($_GET["passwordhashtest"])) {
//     $nyasignup->passwordhashtest($_GET["p"],$_GET["t"]);
// }
// 將資訊返回給客戶端
exit($nlcore->sess->encryptargv($returnClientData));
