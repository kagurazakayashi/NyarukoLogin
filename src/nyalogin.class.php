<?php
require_once "nyauser.class.php";
class nyalogin {
    function login() {
        global $nlcore;
        //IP检查和解密客户端提交的信息
        $jsonarrTotpsecret = $nlcore->safe->decryptargv("signup");
        $jsonarr = $jsonarrTotpsecret[0];
        $totpsecret = $jsonarrTotpsecret[1];
        $totptoken = $jsonarrTotpsecret[2];
        $ipid = $jsonarrTotpsecret[3];
        $returnjson = [];
        //检查参数输入是否齐全
        $getkeys = ["password","user"];
        if ($nlcore->safe->keyinarray($jsonarr,$getkeys) > 0) {
            $nlcore->msg->stopmsg(2000101,$totpsecret); //TODO: 验证代码
        }

        //检查用户名和密码

        // echo $nyauser->passwordhash($jsonarr["password"],$jsonarr["password"]);

        //检查是否需要输入验证码

    }
}

?>
