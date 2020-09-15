<?php
require_once "src/nyacore.class.php";
require_once 'src/nyaencryption.class.php';
$nyaencryption = new nyaencryption();
$inputInformation = $nlcore->sess->decryptargv("encryption", PHP_INT_MAX, PHP_INT_MAX, false);
$returnClientData = $nyaencryption->newDeviceKey($nlcore->sess->argReceived,$nlcore->sess->ipId);
$returnClientData = $nlcore->sess->encryptargv($returnClientData);
exit($returnClientData);