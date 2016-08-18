<?php
require 'yaloginLogin.php';
$c = new yaloginLogin();
$c->init();
$errid = 0;
$backurl = "";
$echomode = "";
$jsonarr2 = array();

if (isset($_GET["logout"])) {
    $backurl = isset($_GET["backurl"]) ? $_GET["backurl"] : "";
    $echomode = isset($_GET["echomode"]) ? $_GET["echomode"] : "";
    session_start();
    $c->logout();
    $errid = 1004;
} else if (isset($_POST["multipleverification"])) {
    $backurl = isset($_POST["backurl"]) ? $_POST["backurl"] : "";
    $echomode = isset($_POST["echomode"]) ? $_POST["echomode"] : "";
    $multipleverification = $_POST["multipleverification"];
    if (is_int($multipleverification)) {
        $errid = $multipleverification;
    } else if (count($multipleverification) > 0) {
        $errid = 90904;
        $jsonarr2 = $multipleverification;
    } else {
        $errid = 1007;
    }
} else {
    $backurl = isset($_POST["backurl"]) ? $_POST["backurl"] : "";
    $echomode = isset($_POST["echomode"]) ? $_POST["echomode"] : "";
    $errid = $c->vaild();
    $jsonarr2 = $c->cookiejsonarr;
}

$html = "";
$jsonarr = array ('result'=>"null",'backurl'=>$backurl);

$jsonarr['result'] = strval($errid);
$html = '<meta http-equiv="refresh" content="1;url=../YashiUser-Alert.php?errid='.strval($errid).'&backurl='.$backurl.'">';
if ($errid >= 0) {
    $saved = $c->savereg($errid);
}

if ($echomode == "json") {
    echo json_encode(array_merge($jsonarr,$jsonarr2));
} else if ($echomode == "html") {
    echo $html;
}
?>