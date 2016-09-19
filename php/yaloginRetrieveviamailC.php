<?php
require 'yaloginRetrieveviamail.php';
$c = new yaloginRetrieveviamail();
$c->init();

$backurl = isset($_GET["backurl"]) ? $_GET["backurl"] : "";
$echomode = isset($_GET["echomode"]) ? $_GET["echomode"] : "";
$vcode = isset($_GET["vcode"]) ? $_GET["vcode"] : null;

$useremail = isset($_GET["useremail"]) ? $_GET["useremail"] : null;

$mode = isset($_GET["mode"]) ? $_GET["mode"] : null;

$html = "";
$jsonarr = array ('result'=>"null",'backurl'=>$backurl);

$errid = 0;
$logerrid = 0;
if ($mode == "smail") {
    $errid = $c->retrievemail($vcode,$useremail);
    $logerrid = $c->savetryreg($errid,4);
} else if ($mode == "cpwd") {
    $c->userobj->mailaddress = $useremail;
    $c->userobj->userpassword = isset($_GET["userpassword"]) ? $_GET["userpassword"] : null;
    $c->userobj->userpassword2 = isset($_GET["userpassword2"]) ? $_GET["userpassword2"] : null;
    $c->userobj->userpasswordquestion1 = isset($_GET["userpasswordquestion1"]) ? $_GET["userpasswordquestion1"] : null;
    $c->userobj->userpasswordanswer1 = isset($_GET["userpasswordanswer1"]) ? $_GET["userpasswordanswer1"] : null;
    $c->userobj->userpasswordquestion2 = isset($_GET["userpasswordquestion2"]) ? $_GET["userpasswordquestion2"] : null;
    $c->userobj->userpasswordanswer2 = isset($_GET["userpasswordanswer2"]) ? $_GET["userpasswordanswer2"] : null;
    $c->userobj->userpasswordquestion3 = isset($_GET["userpasswordquestion3"]) ? $_GET["userpasswordquestion3"] : null;
    $c->userobj->userpasswordanswer3 = isset($_GET["userpasswordanswer3"]) ? $_GET["userpasswordanswer3"] : null;
    $mcode = isset($_GET["mcode"]) ? $_GET["mcode"] : null;
    $errid = $c->vaild($mcode);
    $logerrid = $c->savetryreg($errid,5);
}
// if ($errid == 1008) {}
$html = '<meta http-equiv="refresh" content="1;url=../YashiUser-Alert.php?errid='.strval($errid).'&backurl='.$backurl.'">';
$jsonarr['result'] = strval($errid);
if ($echomode == "json") {
    echo json_encode($jsonarr);
} else if ($echomode == "html") {
    echo $html;
}
?>