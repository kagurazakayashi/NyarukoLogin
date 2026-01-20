<?php
declare(strict_types=1);
/**
 * 使用者資訊查詢端點 - 回傳目前已登入使用者的個人資料資訊。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
require_once "src/nyacore.class.php";
require_once "src/nyauserinfo.class.php";
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("userinfo");
// 檢查用戶是否登入
$nlcore->sess->userLogged();
// 實現功能
$userinfo = new userinfo();
$returnClientData = $userinfo->getuserinfo($nlcore->sess->userHash);
// 將資訊返回給客戶端
$returnClientData = $nlcore->sess->encryptargv($returnClientData);
exit($returnClientData);
