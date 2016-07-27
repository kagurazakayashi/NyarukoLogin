<?php
require 'yaloginStatus.php';
$c = new YaloginStatus();
$c->init();
$errid = 0;
$backurl = isset($_GET["backurl"]) ? $_GET["backurl"] : "";
$echomode = isset($_GET["echomode"]) ? $_GET["echomode"] : "";

$cookiejsonarr = $c->loginuser();
if ($cookiejsonarr == null) {
    $cookiejsonarr = array ();
    $errid = 90901;
} else if ($cookiejsonarr["session"] == false && $cookiejsonarr["cookie"] == false) {
    $errid = 90901;
} else {
    $errid = 1005;
}

$html = "";
$jsonarr = array ('result'=>"null",'backurl'=>$backurl);

$jsonarr['result'] = strval($errid);
$html = '<meta http-equiv="refresh" content="1;url=../YashiUser-Alert.php?errid='.strval($errid).'&backurl='.$backurl.'">';

if ($echomode == "json") {
    echo json_encode(array_merge($jsonarr,$cookiejsonarr));
} else if ($echomode == "html") {
    echo $html;
}
?>