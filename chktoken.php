<?php
declare(strict_types=1);
/**
 * 登入狀態檢查端點 - 檢查客戶端登入階段是否有效，驗證令牌狀態。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
require_once "src/nyacore.class.php";
$nlcore->sess->sessionstatus();