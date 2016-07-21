<?php
require 'yaloginLogin.php';
$c = new yaloginLogin();
$c->init();
$errid = $c->vaild();
$backurl = isset($_POST["backurl"]) ? $_POST["backurl"] : "";
$echomode = isset($_POST["echomode"]) ? $_POST["echomode"] : "";
$html = "";
$jsonarr = array ('result'=>"null",'backurl'=>$backurl);

$jsonarr['result'] = strval($errid);
$html = "<meta http-equiv=\"refresh\" content=\"1;url=../YashiUser-Alert.php?errid=".strval($errid)."&backurl=".$backurl."\">";
if ($errid >= 0) {
    $saved = $c->savereg($errid);
}

if ($echomode == "json") {
    echo json_encode(array_merge($jsonarr,$c->cookiejsonarr));
} else if ($echomode == "html") {
    echo $html;
}
?>