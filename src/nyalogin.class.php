<?php
require_once "nyauser.class.php";
require_once "nyacaptcha.class.php";
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
        //检查是邮箱还是手机号
        $nyauser = new nyauser();
        $user = $jsonarr["user"];
        $logintype = $nyauser->logintype($user,$totpsecret); //0:邮箱 1:手机号
        //取出基础资料
        $tableStr = $nlcore->cfg->db->tables["users"];
        $columnArr = ["id","hash","pwd","mail","telarea","tel","pwdend","2fa","fail","enabletime","errorcode"];
        if ($logintype == 0) {
            $whereDic = ["mail" => $user];
        } else if ($logintype == 1) {
            $tel = $nlcore->safe->telarea($user);
            $whereDic = [
                "telarea" => $tel[0],
                "tel" => $tel[1]
            ];
        }
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        //print_r($result);
        //检查用户是否存在
        if ($result[0] == 1010000) {
            //用户存在
        } else if ($result[0] == 1010001) {
            $nlcore->msg->stopmsg(2040201,$totpsecret);
        } else {
            $nlcore->msg->stopmsg(2040200,$totpsecret);
        }
        $userinfoarr = $result[2][0];
        //检查登录失败次数
        $loginfail = intval($userinfoarr["fail"]);
        //检查是否需要输入验证码
        $needcaptcha = $nyauser->needcaptcha($loginfail);
        if ($needcaptcha == "") {
            //不需要验证码
        } else if ($needcaptcha == "captcha") { //需要图形验证码
            if (!isset($userinfoarr["captcha"])) $nlcore->msg->stopmsg(2040202,$totpsecret);
            //检查验证码是否正确
            $nyacaptcha = new nyacaptcha();
            if (!$nyacaptcha->verifycaptcha($totptoken,$totpsecret,$userinfoarr["captcha"])) die();
        } else {
            $nlcore->msg->stopmsg(2040203,$totpsecret);
        }
        //检查用户名和密码
        $userhash = $userinfoarr["hash"];
        $password = $nyauser->passwordhash($jsonarr["password"],$userinfoarr["pwdend"]);
        if ($password != $userinfoarr["pwd"]) {
            $nlcore->msg->stopmsg(2040204,$totpsecret);
        }
        //检查账户是否异常
        $alertinfo = null;
        if ($userinfoarr["errorcode"] != 0) {
            $alertinfo = $nlcore->msg->imsg[$userinfoarr["errorcode"]];
        }
        //检查账户是否被封禁
        $datetime = $nlcore->safe->getdatetime();
        $timestamp = $datetime[0];
        $timestr = $datetime[1];
        if (strtotime($userinfoarr["enabletime"]) > $timestamp) {
            $baninfo = $userinfoarr["enabletime"];
            if ($alertinfo) $baninfo .= "#".$alertinfo;
            //同时返回：封禁到日期和原因
            $nlcore->msg->stopmsg(2040205,$totpsecret,$baninfo);
        }
    }
}

?>
