<?php
require 'yaloginStatus.php';
require 'yaloginSafe.php';
$c = new YaloginStatus();
$c->init();
$errid = 0;
$backurl = isset($_GET["backurl"]) ? $_GET["backurl"] : "";
$echomode = isset($_GET["echomode"]) ? $_GET["echomode"] : "";

$cookiejsonarr = $c->loginuser();
if ($cookiejsonarr == null) {
    $cookiejsonarr = array ();
    $errid = 90901;
} else if (isset($cookiejsonarr["autologinby"]) == false || $cookiejsonarr["autologinby"] == "fail") {
    $errid = 90901;
} else {
    $errid = 1005;
}

$html = "";
$jsonarr = array ('result'=>"null",'backurl'=>$backurl);

$jsonarr['result'] = strval($errid);
$jsoninfo = array_merge($jsonarr,$cookiejsonarr);
if ($echomode == "json") {
    echo json_encode($jsoninfo);
} else if ($echomode == "html") {
    if ($errid == 1005) {
        $safe = new yaloginSafe();
        $html = '<meta http-equiv="refresh" content="1;url=../YashiUser-Alert.php?errid='.strval($errid).'&backurl='.$backurl.'&data='.$safe->base_encode(json_encode($cookiejsonarr)).'">';
    } else {
        $html = '<meta http-equiv="refresh" content="1;url=../YashiUser-Alert.php?errid='.strval($errid).'&backurl='.$backurl.'">';
    }
    echo $html;
}
?>