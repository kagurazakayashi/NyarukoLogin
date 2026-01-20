<?php
declare(strict_types=1);
/**
 * 驗證碼產生端點 - 產生圖形驗證碼供客戶端使用，防止自動化攻擊。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
require_once "src/nyacore.class.php";
require_once "src/nyacaptcha.class.php";
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("captcha");
// 實現功能
$nyacaptcha = new nyacaptcha();
$nyacaptcha->getcaptcha();
// 检查验证码是否正确
// $nyacaptcha = new nyacaptcha();
// $nyacaptcha->verifycaptcha("00d0d6ff9d3f0cea5ca869e09f493e25","nre9j2");
?>
