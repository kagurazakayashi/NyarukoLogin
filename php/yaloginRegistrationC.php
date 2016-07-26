<?php
require 'yaloginRegistration.php';
$c = new yaloginRegistration();
$c->init();
$errid = $c->vaild();
$backurl = isset($_POST["backurl"]) ? $_POST["backurl"] : "";
$echomode = isset($_POST["echomode"]) ? $_POST["echomode"] : "";
$html = "";
$jsonarr = array ('result'=>"null",'backurl'=>$backurl);

if ($errid > 0) {
    //<script type='text/javascript'>alert('请返回首页登陆');window.location='index';</script>
    //<meta http-equiv=\"refresh\" content=\"5;url=hello.html\">
    $jsonarr['result'] = strval($errid);
    $html = '<meta http-equiv="refresh" content="1;url=../YashiUser-Alert.php?errid='.strval($errid).'&backurl='.$backurl.'">';
    if ($errid >= 10000) {
        $saved = $c->savereg($errid);
    }
} else {
    $errid2 = $c->gensql();
    if ($errid2 > 0) {
        $jsonarr['result'] = strval($errid);
        $html = '<meta http-equiv="refresh" content="1;url=../YashiUser-Alert.php?errid='.strval($errid).'&backurl='.$backurl.'">';
    } else {
        if ($c->ysqlc->sqlset->mail_Enable == true) {
            $errid2 = 1002;
            $erridmail = $c->sendvcodemail();
            if ($erridmail != null && $erridmail != -2) {
                $errid2 = $erridmail; //90300;
            }
        } else {
            $errid2 = 1001;
        }
        $jsonarr['result'] = strval($errid2);
        $html = '<meta http-equiv="refresh" content="1;url=../YashiUser-Alert.php?errid='.strval($errid2).'&backurl='.$backurl.'">';
    }
    $saved = $c->savereg($errid2);
}
if ($echomode == "json") {
    echo json_encode($jsonarr);
} else if ($echomode == "html") {
    echo $html;
}
?>