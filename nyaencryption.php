<?php
declare(strict_types=1);
/**
 * 裝置金鑰交換端點 - 處理客戶端與伺服器之間的加密金鑰交換，建立安全通訊通道。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
require_once "src/nyacore.class.php";
require_once "src/nyaencryption.class.php";
$nyaencryption = new nyaencryption();
$nlcore->sess->decryptargv("encryption", PHP_INT_MAX, PHP_INT_MAX, false);
$returnClientData = $nyaencryption->newDeviceKey($nlcore->sess->argReceived,$nlcore->sess->ipId);
$returnClientData = $nlcore->sess->encryptargv($returnClientData);
exit($returnClientData);