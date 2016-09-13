<?php
require 'yaloginInformation.php';
require 'yaloginSafe.php';
$c = new yaloginInformation();
$c->init();
$errid = 1006;
$backurl = isset($_POST["backurl"]) ? $_POST["backurl"] : "";
$echomode = isset($_POST["echomode"]) ? $_POST["echomode"] : "";
$jsonarr = array ('result'=>"null",'backurl'=>$backurl);
$column = isset($_POST["column"]) ? $_POST["column"] : "";
$table = isset($_POST["table"]) ? $_POST["table"] : null;
$db = isset($_POST["db"]) ? $_POST["db"] : null;

$c->$limitfrom = isset($_POST["limitform"]) ? intval($_POST["limitform"]) : 0;
$c->$limitnum = isset($_POST["limitnum"]) ? intval($_POST["limitnum"]) : 1;
//$c->$specificuserhash = isset($_POST["specificuserhash"]) ? intval($_POST["specificuserhash"]) : "";

$infoarr = $c->getInformation($column,$table,$db);

if ($infoarr == null || count($infoarr) == 0) {
    $errid = 13006;
    if ($echomode == "html") {
        $html = '<meta http-equiv="refresh" content="1;url=../YashiUser-Alert.php?errid='.strval($errid).'&backurl='.$backurl.'">';
    }
} else if (is_int($infoarr) == true) {
    $errid = $infoarr;
    if ($echomode == "html") {
        $html = '<meta http-equiv="refresh" content="1;url=../YashiUser-Alert.php?errid='.strval($errid).'&backurl='.$backurl.'">';
    }
} else {
    $infoarr = $c->deleteautokey($infoarr);
    $jsonarr = array_merge($jsonarr,$infoarr);
    if ($echomode == "html") {
        $safe = new yaloginSafe();
        $html = '<meta http-equiv="refresh" content="1;url=../YashiUser-Alert.php?errid='.strval($errid).'&backurl='.$backurl.'&data='.$safe->base_encode(json_encode($infoarr)).'">';
    }
}

$jsonarr['result'] = strval($errid);

if ($echomode == "json") {
    echo json_encode($jsonarr);
} else if ($echomode == "html") {
    echo $html;
}
?>