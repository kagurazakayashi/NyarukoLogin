<?php
require 'yaloginActivation.php';
$c = new yaloginRegistration();
$c->init();
$errid = $c->vaild();
$backurl = isset($_GET["backurl"]) ? $_GET["backurl"] : null;
$html = "";
$jsonarr = array ('result'=>"null",'backurl'=>$backurl);

$jsonarr['result'] = strval($errid);
$html = "<meta http-equiv=\"refresh\" content=\"1;url=../YashiUser-Alert.php?errid=".strval($errid)."&backurl=".$backurl."\">";
if ($errid >= 10000) {
    $saved = $c->savereg($errid);
}

if ($c->echomode == "json") {
    echo json_encode($jsonarr);
} else if ($c->echomode == "html") {
    echo $html;
}
?>