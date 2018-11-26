<?php
require_once "../src/nyacore.class.php";
require_once '../src/nyatotp.class.php';
header('Content-Type:text/plain;charset=utf-8');
$testdata = array(
    "mode" => "test",
    "testdata1" => 1,
    "testdata2" => 2,
    "testdata3" => 3
);
$secret = "2OOLYKE76JOMU2WS";
$ga = new PHPGangsta_GoogleAuthenticator();
$numcode = $ga->getCode($secret);
$json = json_encode($testdata);
$encrypt_data = xxtea_encrypt($json, $numcode);
echo str_replace(['+','/','='],['-','_',''],base64_encode($encrypt_data));
?>