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
        $appid = $jsonarrTotpsecret[4];
        $returnjson = [];
        $process = "use=";
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
            $process .= "mail";
        } else if ($logintype == 1) {
            $tel = $nlcore->safe->telarea($user);
            $whereDic = [
                "telarea" => $tel[0],
                "tel" => $tel[1]
            ];
            $process .= "tel";
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
        $process .= ",fail=".$userinfoarr["fail"];
        //检查是否需要输入验证码
        $needcaptcha = $nyauser->needcaptcha($loginfail);
        if ($needcaptcha == "") {
            //不需要验证码
            $process .= ",usecaptcha=no";
        } else if ($needcaptcha == "captcha") { //需要图形验证码
            $process .= ",usecaptcha=yes";
            $nyacaptcha = new nyacaptcha();
            //没有验证码
            if (!isset($userinfoarr["captcha"])) {
                $returnjson = $nlcore->msg->m(0,2040202);
                //发放一个新的验证码
                $newcaptcha = $nyacaptcha->getcaptcha(false,false,false);
                $returnjson["img"] = $newcaptcha["img"];
                $returnjson["timestamp"] = $newcaptcha["timestamp"];
                echo $nlcore->safe->encryptargv($returnjson,$totpsecret);
                die();
            }
            //检查验证码是否正确，不正确重新发放一个
            $nyacaptcha = new nyacaptcha();
            if (!$nyacaptcha->verifycaptcha($totptoken,$totpsecret,$jsonarr["captcha"])) die();
        } else {
            $nlcore->msg->stopmsg(2040203,$totpsecret);
        }
        //检查用户名和密码
        $userhash = $userinfoarr["hash"];
        $userid = $userinfoarr["id"];
        $userfail = $userinfoarr["fail"];
        $password = $nyauser->passwordhash($jsonarr["password"],$userinfoarr["pwdend"]);
        if ($password != $userinfoarr["pwd"]) {
            //密码错误。记录历史记录。
            $this->loginfailuretimes($userid,$totpsecret,$userfail);
            $nyauser->writehistory("USER_SIGN_IN",2040204,$userhash,$totptoken,$totpsecret,$ipid,$user,$process);
            $nlcore->msg->stopmsg(2040204,$totpsecret);
        }
        $process .= ",password=ok";
        //检查账户是否异常
        $alertinfo = [null,null];
        $process .= ",alertcode=".$userinfoarr["errorcode"];
        if ($userinfoarr["errorcode"] != 0) {
            $alertinfo = [$userinfoarr["errorcode"],$nlcore->msg->imsg[$userinfoarr["errorcode"]]];
        }
        //检查账户是否被封禁
        $datetime = $nlcore->safe->getdatetime();
        $timestamp = $datetime[0];
        $timestr = $datetime[1];
        $process .= ",enabletime=".$userinfoarr["enabletime"];
        if (strtotime($userinfoarr["enabletime"]) > $timestamp) {
            //发现封禁。记录历史记录。同时返回：封禁到日期和原因。
            $this->loginfailuretimes($userid,$totpsecret,$userfail);
            $nyauser->writehistory("USER_SIGN_IN",2040205,$userhash,$totptoken,$totpsecret,$ipid,$user,$process);
            $returnjson = $nlcore->msg->m(0,2040205,$alertinfo[1]);
            $returnjson["enabletime"] = $userinfoarr["enabletime"];
            echo $nlcore->safe->encryptargv($returnjson,$totpsecret);
            die();
        }

        //检查是否需要两步验证
        $fa = $userinfoarr["2fa"];
        if ($fa || $fa != "") {
            $process .= ",2fa=".$userinfoarr["2fa"];
            $faarr = explode(",", $fa);
            if (!isset($jsonarr["2famode"]) || !isset($jsonarr["2fa"])) {
                //没有提供两步验证信息则返回都开通了那些两步验证方式
                $returnjson = $nlcore->msg->m(0,2040300);
                $returnjson["supported2fa"] = $faarr;
                if (in_array("qa", $faarr)) {
                    $returnjson["question"] = $nyauser->getquestion($userhash,$totpsecret);
                }
                echo $nlcore->safe->encryptargv($returnjson,$totpsecret);
                die();
            }
            if (!in_array($jsonarr["2famode"], $faarr)) {
                $nlcore->msg->stopmsg(2040302,$totpsecret);
            }
            $faval = $jsonarr["2fa"];
            if ($jsonarr["2famode"] == "ga") {
                //TOTP码
                if (!is_numeric($faval) || strlen($faval) != 6) {
                    //TOTP代码错误。记录历史记录。
                    $this->loginfailuretimes($userid,$totpsecret,$userfail);
                    $nyauser->writehistory("USER_SIGN_IN",2040303,$userhash,$totptoken,$totpsecret,$ipid,$user,$process);
                    $nlcore->msg->stopmsg(2040303,$totpsecret);
                }
                //TODO: 检查TOTP
            } else if ($jsonarr["2famode"] == "qa") {
                //密码提示问题
                // $this->loginfailuretimes($userid,$totpsecret,$userfail);
                // $nyauser->writehistory("USER_SIGN_IN",2040304,$userhash,$totptoken,$totpsecret,$ipid,$user,$process);
                // $nlcore->msg->stopmsg(2040304,$totpsecret);
                //TODO: 检查密码提示问题
            } else if ($jsonarr["2famode"] == "rc") {
                //一次性恢复代码
                if (strlen($faval) != 25) {
                    //恢复代码错误。记录历史记录。
                    $this->loginfailuretimes($userid,$totpsecret,$userfail);
                    $nyauser->writehistory("USER_SIGN_IN",2040305,$userhash,$totptoken,$totpsecret,$ipid,$user,$process);
                    $nlcore->msg->stopmsg(2040305,$totpsecret);
                }
                //TODO: 检查恢复代码，没问题则删除恢复代码
            } else if ($jsonarr["2famode"] == "sm") {
                //TODO: 检查短信验证码，如果没有则发送一条
            } else if ($jsonarr["2famode"] == "ma") {
                //TODO: 检查邮件验证码，如果没有则发送一条
            }
        } else {
            $process .= ",2fa=no";
        }

        //分配 token
        $token = $nlcore->safe->md6($userhash.$timestamp);
        $tokentimeout = 0;
        if (isset($jsonarr["timeout"])) {
            $tokentimeout = intval($jsonarr["timeout"]);
        } else {
            $tokentimeout = $nlcore->cfg->verify->tokentimeout;
        }
        $tokentimeout += $timestamp;
        $tokentimeoutstr = $nlcore->safe->getdatetime(null,$tokentimeout)[1];
        $insertDic = [
            "token" => $token,
            "apptoken" => $totptoken,
            "ipid" => $ipid,
            "appid" => $appid,
            "time" => $timestr,
            "endtime" => $tokentimeoutstr
        ];
        $tableStr = $nlcore->cfg->db->tables["session"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040113,$totpsecret);

        //查询用户具体资料
        $userexinfoarr = $nyauser->getuserinfo($userhash,$totpsecret);

        //写入成功历史记录
        $nyauser->writehistory("USER_SIGN_IN",1030000,$userhash,$totptoken,$totpsecret,$ipid,$user,$process,$token);

        //返回到客户端
        $returnjson = $nlcore->msg->m(0,1030000);
        $returnjson = [
            "token" => $token,
            "timestamp" => $timestamp,
            "endtime" => $tokentimeout,
            "mail" => $userinfoarr["mail"],
            "telarea" => $userinfoarr["telarea"],
            "tel" => $userinfoarr["tel"],
            "userinfo" => $userexinfoarr
        ];
        if ($alertinfo[0] == 3000000) {
            $insertDic["code"] = 1030001;
        } else if ($alertinfo[0] != null) {
            $insertDic["code"] = 1030002;
            $insertDic["msg"] = $insertDic["msg"].$alertinfo[1];
        }
        echo $nlcore->safe->encryptargv($returnjson,$totpsecret);
    }

    /**
     * @description: 修改当前用户的登录失败计数
     * @param Int users 数据表 ID
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @param Int/String fail 当前登录失败次数，-1 则清除失败次数
     */
    function loginfailuretimes($id,$totpsecret,$fail=-1) {
        $f = intval($fail) + 1;
        $updateDic = ["fail" => $f];
        $tableStr = $nlcore->cfg->db->tables["users"];
        $whereDic = ["id" => $id];
        $result = $nlcore->db->update($updateDic,$tableStr,$whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040112,$totpsecret);
    }
}

?>
