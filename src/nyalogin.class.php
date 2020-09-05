<?php
require_once "nyacaptcha.class.php";
class nyalogin {
    function login() {
        global $nlcore;
        //IP检查和解密客户端提交的信息
        $inputInformation = $nlcore->safe->decryptargv("signup");
        $argReceived = $inputInformation[0];
        $totpSecret = $inputInformation[1];
        $totpToken = $inputInformation[2];
        $ipid = $inputInformation[3];
        $returnJson = [];
        $process = "use=";
        //检查参数输入是否齐全
        $argReceivedKeys = ["password","user"];
        if ($nlcore->safe->keyinarray($argReceived,$argReceivedKeys) > 0) {
            $nlcore->msg->stopmsg(2000101,$totpSecret);
        }
        //检查是邮箱还是手机号
        $user = $argReceived["user"];
        $logintype = $nlcore->func->logintype($user,$totpSecret); //0:邮箱 1:手机号
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
        //检查用户是否存在
        if ($result[0] == 1010000) {
            //用户存在
        } else if ($result[0] == 1010001) {
            $nlcore->msg->stopmsg(2040201,$totpSecret);
        } else {
            $nlcore->msg->stopmsg(2040200,$totpSecret);
        }
        $userinfoarr = $result[2][0];
        //检查登录失败次数
        $loginfail = intval($userinfoarr["fail"]);
        $process .= ",fail=".$userinfoarr["fail"];
        //检查是否需要输入验证码
        $needcaptcha = $nlcore->func->needcaptcha($loginfail);
        if ($needcaptcha == "") {
            //不需要验证码
            $process .= ",usecaptcha=no";
        } else if ($needcaptcha == "captcha") { //需要图形验证码
            $process .= ",usecaptcha=yes";
            //没有验证码
            if (!isset($userinfoarr["captcha"])) {
                $this->getcaptcha(2040202,$totpSecret); //发放一个新的验证码
            }
            //有验证码，检查验证码是否正确，不正确重新发放一个
            $nyacaptcha = new nyacaptcha();
            if (!$nyacaptcha->verifycaptcha($totpToken,$totpSecret,$argReceived["captcha"])) die();
        } else {
            $nlcore->msg->stopmsg(2040203,$totpSecret);
        }
        //检查用户名和密码
        $userHash = $userinfoarr["hash"];
        $userid = $userinfoarr["id"];
        $userfail = $userinfoarr["fail"];
        $password = $nlcore->safe->passwordhash($argReceived["password"],$userinfoarr["pwdend"]);
        if ($password != $userinfoarr["pwd"]) {
            //密码错误。记录历史记录。
            $this->loginfailuretimes($userid,$totpSecret,$userfail);
            $nlcore->func->writehistory("USER_SIGN_IN",2040204,$userHash,$totpToken,$totpSecret,$ipid,$user,$process);
            //预估下次是否会被要求验证码
            $needcaptcha = $nlcore->func->needcaptcha($loginfail + 1);
            if ($needcaptcha == "captcha") { //需要图形验证码
                $this->getcaptcha(2040208,$totpSecret); //发放一个新的验证码
            }
            $nlcore->msg->stopmsg(2040204,$totpSecret);
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
            $this->loginfailuretimes($userid,$totpSecret,$userfail);
            $nlcore->func->writehistory("USER_SIGN_IN",2040205,$userHash,$totpToken,$totpSecret,$ipid,$user,$process);
            $returnJson = $nlcore->msg->m(0,2040205,$alertinfo[1]);
            $returnJson["enabletime"] = $userinfoarr["enabletime"];
            echo $nlcore->safe->encryptargv($returnJson,$totpSecret);
            die();
        }
        //检查登录是否封顶，如果封顶，同设备最早的登录踢下线，并推送邮件
        $overflowsession = $this->chkoverflowsession($userHash,$totpToken,$totpSecret);
        if ($overflowsession) {
            $overprog = ",overflowsession=".json_encode($overflowsession);
            $process .= $overprog;
            //TODO: 发送顶掉通知邮件
        }
        //检查是否需要两步验证
        $fa = $userinfoarr["2fa"];
        if ($fa || $fa != "") {
            $process .= ",2fa=".$userinfoarr["2fa"];
            $faarr = explode(",", $fa);
            if (!isset($argReceived["2famode"]) || !isset($argReceived["2fa"])) {
                //没有提供两步验证信息则返回都开通了那些两步验证方式
                $returnJson = $nlcore->msg->m(0,2040300);
                $returnJson["supported2fa"] = $faarr;
                if (in_array("qa", $faarr)) {
                    $returnJson["question"] = $nlcore->func->getquestion($userHash,$totpSecret);
                }
                echo $nlcore->safe->encryptargv($returnJson,$totpSecret);
                die();
            }
            if (!in_array($argReceived["2famode"], $faarr)) {
                $nlcore->msg->stopmsg(2040302,$totpSecret);
            }
            $faval = $argReceived["2fa"];
            if ($argReceived["2famode"] == "ga") {
                //TOTP码
                if (!is_numeric($faval) || strlen($faval) != 6) {
                    //TOTP代码错误。记录历史记录。
                    $this->loginfailuretimes($userid,$totpSecret,$userfail);
                    $nlcore->func->writehistory("USER_SIGN_IN",2040303,$userHash,$totpToken,$totpSecret,$ipid,$user,$process);
                    $nlcore->msg->stopmsg(2040303,$totpSecret);
                }
                //TODO: 检查TOTP
            } else if ($argReceived["2famode"] == "qa") {
                //密码提示问题
                // $this->loginfailuretimes($userid,$totpSecret,$userfail);
                // $nlcore->func->writehistory("USER_SIGN_IN",2040304,$userHash,$totpToken,$totpSecret,$ipid,$user,$process);
                // $nlcore->msg->stopmsg(2040304,$totpSecret);
                //TODO: 检查密码提示问题
            } else if ($argReceived["2famode"] == "rc") {
                //一次性恢复代码
                if (strlen($faval) != 25) {
                    //恢复代码错误。记录历史记录。
                    $this->loginfailuretimes($userid,$totpSecret,$userfail);
                    $nlcore->func->writehistory("USER_SIGN_IN",2040305,$userHash,$totpToken,$totpSecret,$ipid,$user,$process);
                    $nlcore->msg->stopmsg(2040305,$totpSecret);
                }
                //TODO: 检查恢复代码，没问题则删除恢复代码
            } else if ($argReceived["2famode"] == "sm") {
                //TODO: 检查短信验证码，如果没有则发送一条
            } else if ($argReceived["2famode"] == "ma") {
                //TODO: 检查邮件验证码，如果没有则发送一条
            }
        } else {
            $process .= ",2fa=no";
        }

        //分配 token
        $token = $nlcore->safe->rhash64($userHash.$timestamp);
        $tokentimeout = 0;
        if (isset($argReceived["timeout"])) {
            $tokentimeout = intval($argReceived["timeout"]);
        } else {
            $tokentimeout = $nlcore->cfg->verify->tokentimeout;
        }
        $tokentimeout += $timestamp;
        $tokentimeoutstr = $nlcore->safe->getdatetime(null,$tokentimeout)[1];
        $deviceid = $nlcore->func->getdeviceid($totpToken,$totpSecret);
        //获取 UA
        $ua = null;
        if (isset($argReceived["ua"]) && strlen($argReceived["ua"]) > 0) {
            $ua = $argReceived["ua"];
        } else if (isset($_SERVER["HTTP_USER_AGENT"]) && strlen($_SERVER["HTTP_USER_AGENT"]) > 0) {
            $ua = $_SERVER["HTTP_USER_AGENT"];
        }
        //获取设备类型
        $devicetype = $nlcore->func->getdeviceinfo($deviceid,$totpSecret,true);
        $insertDic = [
            "token" => $token,
            "apptoken" => $totpToken,
            "userhash" => $userHash,
            "ipid" => $ipid,
            "devid" => $deviceid,
            "devtype" => $devicetype,
            "time" => $timestr,
            "endtime" => $tokentimeoutstr
        ];
        $tableStr = $nlcore->cfg->db->tables["session"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040113,$totpSecret);

        //查询用户具体资料
        $userexinfoarr = $nlcore->func->getuserinfo($userHash,$totpSecret);

        //写入成功历史记录
        $nlcore->func->writehistory("USER_SIGN_IN",1020100,$userHash,$totpToken,$totpSecret,$ipid,$user,$process,$token);

        //返回到客户端
        $returnJson = [];
        if ($alertinfo[0] == 3000000) {
            $returnJson = $nlcore->msg->m(0,1020102);
        } else if ($alertinfo[0] != null) {
            $returnJson = $nlcore->msg->m(0,1020101);
            $returnJson["msg"] = $returnJson["msg"].$alertinfo[1];
        } else {
            $returnJson = $nlcore->msg->m(0,1020100);
        }

        $returnJson = array_merge($returnJson,[
            "token" => $token,
            "timestamp" => $timestamp,
            "endtime" => $tokentimeout,
            "mail" => $userinfoarr["mail"],
            "telarea" => $userinfoarr["telarea"],
            "tel" => $userinfoarr["tel"],
            "userinfo" => $userexinfoarr
        ]);
        if ($overflowsession) $returnJson["logout"] = $overflowsession;
        echo $nlcore->safe->encryptargv($returnJson,$totpSecret);
    }
    /**
     * @description: 修改当前用户的登录失败计数
     * @param Int users 数据表 ID
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @param Int/String fail 当前登录失败次数，-1 则清除失败次数
     */
    function loginfailuretimes($id,$totpSecret,$fail=-1) {
        global $nlcore;
        $f = intval($fail) + 1;
        $updateDic = ["fail" => $f];
        $tableStr = $nlcore->cfg->db->tables["users"];
        $whereDic = ["id" => $id];
        $result = $nlcore->db->update($updateDic,$tableStr,$whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040112,$totpSecret);
    }
    /**
     * @description: 创建一份新的验证码并返回客户端
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     */
    function getcaptcha($code,$totpSecret) {
        global $nlcore;
        $nyacaptcha = new nyacaptcha();
        $newcaptcha = $nyacaptcha->getcaptcha(false,false,false);
        $returnJson = $nlcore->msg->m(0,$code);
        $returnJson["img"] = $newcaptcha["img"];
        $returnJson["timestamp"] = $newcaptcha["timestamp"];
        echo $nlcore->safe->encryptargv($returnJson,$totpSecret);
        die();
    }
    /**
     * @description: 检查当前设备类型和总共的同时登录数是否超出限制
     * @param String userhash 用户哈希
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @return Array 被登出的设备的信息（手机型号等）
     */
    function chkoverflowsession($userHash,$totpToken,$totpSecret) {
        global $nlcore;
        //在 totp 表取 devid 获得当前设备信息
        $tableStr = $nlcore->cfg->db->tables["encryption"];
        $columnArr = ["devid"];
        $whereDic = ["apptoken" => $totpToken];
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        if ($result[0] >= 2000000 || !isset($result[2][0]["devid"])) $nlcore->msg->stopmsg(2040213,$totpSecret);
        $thisdevid = $result[2][0]["devid"];
        //取出所有 session 中的处于有效期内的会话
        $tableStr = $nlcore->cfg->db->tables["session"];
        $columnArr = ["id","apptoken","devtype","time"];
        $whereDic = ["userhash" => $userHash];
        $customWhere = "`endtime` > CURRENT_TIME";
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic,$customWhere);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040209,$totpSecret);
        if (!isset($result[2]) || count($result[2]) == 0) return null;
        //取出所有 session
        $sessionarr = $result[2];
        $maxlogin = $nlcore->cfg->app->maxlogin;
        //检查有没有超过总数限制
        if (count($sessionarr) >= $maxlogin["all"]) {
            return $this->removeoverflowsession($sessionarr,$totpSecret);
        }
        //查session表获取当前设备类型
        $resultdev = $nlcore->func->getdeviceinfo($thisdevid,$totpSecret);
        if (!isset($resultdev["type"])) $nlcore->msg->stopmsg(2040213,$totpSecret);
        $devtype = $resultdev["type"];
        //取会话数组中用这个设备型号的数据
        $thisdevsession = [];
        foreach ($sessionarr as $sessioninfo) {
            if ($devtype == $sessioninfo["devtype"]) {
                array_push($thisdevsession,$sessioninfo);
            }
        }
        //检查有没有超过额定限制
        if (!isset($maxlogin[$devtype])) $nlcore->msg->stopmsg(2040214,$totpSecret);
        if (count($thisdevsession) >= $maxlogin[$devtype]) {
            //删除本设备的旧登录状态
            return $this->removeoverflowsession($thisdevsession,$totpSecret);
        }
        return null;
    }
    /**
     * @description: 将较早的设备登出
     * @param Array sessionarr 用户已有有效会话的数组
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @return Array 被登出设备的相关设备信息，键均以 logout_ 为前缀
     */
    function removeoverflowsession($sessionarr,$totpSecret) {
        global $nlcore;
        //超过总数限制，登出最早的终端。取最小的时间戳对应的id
        $ttime = PHP_INT_MAX;
        $tid = -1;
        foreach ($sessionarr as $apptoken) {
            $ttimen = strtotime($apptoken["time"]);
            if ($ttimen < $ttime) {
                $ttime = $ttimen;
                $tid = $apptoken["id"];
            }
        }
        if ($tid == -1) $nlcore->msg->stopmsg(2040211,$totpSecret);
        //取出要删除会话的设备型号
        $tableStr = $nlcore->cfg->db->tables["session"];
        $columnArr = ["devid"];
        $whereDic = ["id" => $tid];
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        if ($result[0] >= 2000000 || !isset($result[2][0]["devid"])) $nlcore->msg->stopmsg(2040212,$totpSecret);
        $devid = $result[2][0]["devid"];
        //删除最旧的会话
        $delwheredic = ["id" => $tid];
        $delresult = $nlcore->db->delete($tableStr,$delwheredic);
        if ($delresult[0] >= 2000000) $nlcore->msg->stopmsg(2040211,$totpSecret);
        //查设备表来返回被登出的设备型号
        $logoutdevinfo = $nlcore->func->getdeviceinfo($devid,$totpSecret);
        return $logoutdevinfo;
    }
}
?>