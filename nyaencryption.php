<?php
require_once "src/nyacore.class.php";
require_once 'src/nyaencryption.class.php';
$nyaencryption = new nyaencryption();
$argv = count($_POST) > 0 ? $_POST : $_GET;

// 完整收發內含變數演示
$inputInformation = $nlcore->sess->decryptargv("encryption", PHP_INT_MAX, PHP_INT_MAX, false);
// 檢查用戶是否登入
// $sessionInformation = $nlcore->sess->userLogged($inputInformation);


// echo json_encode([$time,$stime,$ipid]);
$returnClientData = $nyaencryption->newDeviceKey($nlcore->sess->argReceived,$nlcore->sess->ipId);
// echo $nlcore->sess->privateKey;
// echo $nlcore->sess->publicKey;
header('Content-Type:application/json;charset=utf-8');
$returnClientData = $nlcore->sess->encryptargv($returnClientData);
exit($returnClientData);