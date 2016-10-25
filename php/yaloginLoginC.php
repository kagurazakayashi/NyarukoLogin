<?php
require 'yaloginLogin.php';
if(class_exists('yaloginGlobal') != true) {
        require 'yaloginGlobal.php';
}
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

if ($echomode == "alert") {
    $globalsett = new YaloginGlobal();
    $errorarr = $globalsett->erroridArr;
    $erridstr = strval($errid);
    $errinfo = isset($errorarr[$erridstr]) ? $errorarr[$erridstr] : "其他错误。";
    $showinfo = "代码 ".$erridstr;
    $erridnum = intval($errid);
    if ($erridnum >= 1000 && $erridnum < 10000) {
        $alerttitle = "提示";
        $alertbtntxt = "确定";
    }
    $showinfo = $showinfo." : ".$errinfo;
    echo $showinfo;
} else if ($echomode == "json") {
    if (is_array($jsonarr2)) {
        echo json_encode(array_merge($jsonarr,$jsonarr2));
    } else {
        echo json_encode($jsonarr);
    }
} else if ($echomode == "html") {
    echo $html;
}
?>