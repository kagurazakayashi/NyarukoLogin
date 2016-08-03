<?php
require 'yaloginInformation.php';
$c = new yaloginInformation();
$c->init();
$errid = 0;
$backurl = isset($_POST["backurl"]) ? $_POST["backurl"] : "";
$echomode = isset($_POST["echomode"]) ? $_POST["echomode"] : "";
$jsonarr = array ('result'=>"null",'backurl'=>$backurl);
$infoarr = $c->getInformation();
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
        $html = $c->echohtml($infoarr);
    }
}

$jsonarr['result'] = strval($errid);

if ($echomode == "json") {
    echo json_encode($jsonarr);
} else if ($echomode == "html") {
    echo $html;
}
?>