<?php
require_once "src/nyacore.class.php";
require_once 'src/nyaencryption.class.php';
$nyaencryption = new nyaencryption();
$argv = count($_POST) > 0 ? $_POST : $_GET;


$ipinfo = $nlcore->safe->decryptargv("encryption", PHP_INT_MAX, PHP_INT_MAX, true);
// echo json_encode([$time,$stime,$ipid]);
$nyaencryption->newDeviceKey($argv,$ipinfo);
// echo $nlcore->safe->privateKey;
// echo $nlcore->safe->publicKey;
