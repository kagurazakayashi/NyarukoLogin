<?php
require 'yaloginRegistration.php';
$registration = new yaloginRegistration();
$registration->init();
$errid = $registration->vaild();
$backurl = isset($_POST["backurl"]) ? $_POST["backurl"] : null;
$html = "";
$jsonarr = array ('result'=>"null",'backurl'=>$backurl);

if ($errid > 0) {
    //<script type='text/javascript'>alert('请返回首页登陆');window.location='index';</script>
    //<meta http-equiv=\"refresh\" content=\"5;url=hello.html\">
    $jsonarr['result'] = strval($errid);
    $html = "<meta http-equiv=\"refresh\" content=\"1;url=../YashiUser-Alert.php?errid=".strval($errid)."&backurl=".$backurl."\">";
    if ($errid < 11200 || $errid > 11299) {
        $saved = $registration->savereg($errid);
    }
} else {
    $errid2 = $registration->gensql();
    if ($errid2 > 0) {
        $jsonarr['result'] = strval($errid);
        $html = "<meta http-equiv=\"refresh\" content=\"1;url=../YashiUser-Alert.php?errid=".strval($errid2)."&backurl=".$backurl."\">";
    } else {
        $jsonarr['result'] = "1001";
        $html = "<meta http-equiv=\"refresh\" content=\"1;url=../YashiUser-Alert.php?errid=1001&backurl=".$backurl."\">";
        $registration->sendvcodemail();
    }
    $saved = $registration->savereg($errid2);
}
if ($registration->echomode == "json") {
    echo json_encode($jsonarr);
} else if ($registration->echomode == "html") {
    echo $html;
}
?>