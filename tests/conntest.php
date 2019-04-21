<?php
require_once "../src/nyacore.class.php";
global $nlcore;
$jsonarrTotpsecret = $nlcore->safe->decryptargv("encrypttest");
$jsonarr = $jsonarrTotpsecret[0];
$totpsecret = $jsonarrTotpsecret[1];
$jsonarr["status"] = 1000000;
echo $nlcore->safe->encryptargv($jsonarr,$totpsecret);
?>
