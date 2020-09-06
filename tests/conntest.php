<?php
require_once "../src/nyacore.class.php";
global $nlcore;
$inputInformation = $nlcore->sess->decryptargv("encrypttest");
$argReceived = $inputInformation[0];
$totpSecret = $inputInformation[1];
$argReceived["code"] = 1000000;
echo $nlcore->sess->encryptargv($argReceived,$totpSecret);
?>
