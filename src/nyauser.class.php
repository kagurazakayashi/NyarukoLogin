<?php
class nyauser {
    /**
     * @description: 检查登录凭据是邮箱还是手机号
     * @param String loginstr 要检查的登录凭据字符串
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @return Int 0:直接将错误返回给客户端 0:邮箱 1:手机号
     */
    function logintype($loginstr,$totpsecret=null) {
        global $nlcore;
        $telareaarr = $nlcore->safe->telarea($loginstr);
        if ($nlcore->safe->isPhoneNumCN($telareaarr[1])) {
            return 1;
        } else if ($nlcore->safe->isEmail($loginstr)) {
            return 0;
        } else {
            $nlcore->msg->stopmsg(2020206,$totpsecret);
            return -1;
        }
    }
    /**
     * @description: 检查指定信息地址是否已经存在于数据库
     * @param Int logintype 要检查的凭据类型 0:邮箱 1:手机号 2:哈希
     * @param String loginstr 要检查的登录凭据字符串
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @return Bool 是否已经存在。如果出现多个结果则直接将错误返回客户端
     */
    function isalreadyexists($logintype,$loginstr,$totpsecret=null) {
        global $nlcore;
        $logintypearr = ["mail","tel","hash"];
        $logintypestr = $logintypearr[$logintype];
        $whereDic = [$logintypearr[$logintype] => $loginstr];
        $result = $nlcore->db->scount($nlcore->cfg->db->tables["users"],$whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040100,$totpsecret);
        $datacount = $result[2][0][0];
        if ($datacount == 0) {
            return false;
        } else if ($datacount == 1) {
            // $nlcore->msg->stopmsg(2040102,$totpsecret);
            return true;
        } else {
            $nlcore->msg->stopmsg(2040101,$totpsecret);
        }
    }
    /**
     * @description: 检查该用户是否已存在
     * @param String mergename 昵称#四位代码
     * 或使用：
     * @param String name 昵称
     * @param String nameid 四位代码
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @return Bool 是否有此用户
     */
    function useralreadyexists($mergename=null,$name=null,$nameid=null,$totpsecret=null) {
        global $nlcore;
        if ($mergename) {
            $namearr = explode("#", $mergename);
            $nameid = end($namearr);
            if (count($namearr) > 2) {
                array_pop($namearr);
                $name = implode("#", $namearr);
            } else {
                $name = $namearr[0];
            }
        }
        $whereDic = [
            "name" => $name,
            "nameid" => $nameid
        ];
        $result = $nlcore->db->scount($nlcore->cfg->db->tables["info"],$whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040200,$totpsecret);
        $datacount = $result[2][0][0];
        if ($datacount > 0) return true;
        return false;
    }
    /**
     * @description: 对明文密码进行加密以便存储到数据库
     * 原文+自定义盐+注册时间戳 的 MD6
     * @param String password 明文密码
     * @param Int timestamp 密码到期时间时间戳
     * @param String timestamp 密码到期时间字符串（将自动转时间戳）
     * @return 加密后的密码
     */
    function passwordhash($password,$timestamp) {
        global $nlcore;
        if (!is_int($timestamp)) $timestamp = strtotime($timestamp);
        $passwordhash = $password.$nlcore->cfg->app->passwordsalt.strval($timestamp);
        $passwordhash = $nlcore->safe->md6($passwordhash);
        return $passwordhash;
    }
    /**
     * @description: 检查是否需要输入验证码，并根据配置决定显示哪种验证码
     * @param Int 失败次数计数
     * @return String 需要的验证方式
     */
    function needcaptcha($loginfail) {
        global $nlcore;
        $needcaptcha = $nlcore->cfg->verify->needcaptcha;
        $nowmode = "";
        $nownum = 0;
        foreach($needcaptcha as $key => $value){
            if ($loginfail >= $value && $value > $nownum) {
                $nowmode = $key;
                $nownum = $value;
            }
        }
        return $nowmode;
    }
    /**
     * @description: 写入历史记录
     * @param String userhash 用户哈希
     * @param String totptoken 应用识别码
     * @param String code 错误代码
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @param String process 过程记录
     * @param String session 当前会话
     */
    function writehistory($operation,$code,$userhash,$totptoken,$totpsecret,$ipid,$sender,$process=null,$session=null) {
        global $nlcore;
        $insertDic = [
            "userhash" => $userhash,
            "apptoken" => $totptoken,
            "operation" => $operation,
            "sender" => $sender,
            "ipid" => $ipid,
            "process" => $process,
            "result" => $code
        ];
        $tableStr = $nlcore->cfg->db->tables["history"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040112,$totpsecret);
    }

    /**
     * @description: 取出密码提示问题
     * @param String userhash 用户哈希
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @param Boolean all : true=顺序全部取出 false=乱序取出随机两个
     * @return Array<String> 密码提示问题数组
     */
    function getquestion($userhash,$totpsecret,$all=false) {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["protection"];
        $columnArr = ["question1","question2","question3"];
        $whereDic = ["userhash" => $userhash];
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        if ($result[0] != 1010000) $nlcore->msg->stopmsg(2040301,$totpsecret);
        $questions = $result[2][0];
        if (!isset($questions["question1"]) || $questions["question1"] == "" || !isset($questions["question2"]) || $questions["question2"] == "" || !isset($questions["question3"]) || $questions["question3"] == "") {
            $nlcore->msg->stopmsg(2040301,$totpsecret);
        }
        $returnarr = [$questions["question1"],$questions["question2"],$questions["question3"]];
        shuffle($returnarr);
        array_pop($returnarr);
        return $returnarr;
    }
    /**
     * @description: 取得用户个性化信息
     * @param String userhash 用户哈希
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @return Array<Array> 用户信息数组（一个用户可以关联多条信息，但唯一的主信息一直在数组第一位）
     */
    function getuserinfo($userhash,$totpsecret) {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["info"];
        $columnArr = ["infotype","name","nameid","gender","address","profile","description","image","background"];
        $whereDic = ["userhash" => $userhash];
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        if ($result[0] != 1010000) $nlcore->msg->stopmsg(2040206,$totpsecret);
        $userinfos = $result[2];
        $newuserinfos = [];
        $maininfo = [];
        for ($i = 0; $i < count($userinfos); $i++) {
            $nowuserinfo = $userinfos[$i];
            if ($nowuserinfo["infotype"] == 0) {
                array_push($maininfo,$nowuserinfo);
            } else {
                array_push($newuserinfos,$nowuserinfo);
            }
        }
        if (count($maininfo) != 1) $nlcore->msg->stopmsg(2040207,$totpsecret);
        return array_merge($maininfo,$newuserinfos);
    }

    function chklogin($userhash,$totpsecret) {
        global $nlcore;
        //取出所有 session 中的处于有效期内的 apptoken
        $tableStr = $nlcore->cfg->db->tables["session"];
        $columnArr = ["id","apptoken","devid","time"];
        $whereDic = ["userhash" => $userhash];
        $customWhere = "`endtime` > CURRENT_TIME";
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic,$customWhere);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040209,$totpsecret);
        //如果有
        if (isset($result[2]) && count($result[2]) > 0) {
            $sessionarr = $result[2];
            $sessionarrc = count($sessionarr);
            $maxlogin = $nlcore->cfg->app->maxlogin;
            $alltype = array_keys($maxlogin);
            //检查有没有超过总数限制
            if ($sessionarrc >= $maxlogin["all"]) {
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
                if ($tid == -1) $nlcore->msg->stopmsg(2040211,$totpsecret);
                //删除最旧的会话
                $delwheredic = ["id" => $tid];
                $delresult = $nlcore->db->delete($tableStr,$delwheredic);
                if ($delresult[0] >= 2000000) $nlcore->msg->stopmsg(2040211,$totpsecret);
                //查设备表来返回被登出的设备型号
                return $this->getdeviceinfo($apptoken["devid"],$totpsecret);
            }
            //从会话加密码获得当前设备型号ID
            $tableStr = $nlcore->cfg->db->tables["totp"];
            $columnArr = ["devid"];
            $whereDic = ["secret" => $totpsecret];
            $devidresult = $nlcore->db->select($columnArr,$tableStr,$whereDic);
            if ($devidresult[0] >= 2000000 || !isset($devidresult[2][0]["devid"])) $nlcore->msg->stopmsg(2040213,$totpsecret);
            $thisdevid = $devidresult[2][0]["devid"];
            //取会话数组中用这个设备型号ID的数据
            $thisdevsession = [];
            foreach ($sessionarr as $sessioninfo) {
                if ($thisdevid == $sessioninfo["devid"]) {
                    array_push($thisdevsession,$sessioninfo);
                }
            }
            //取出设备型号ID所对应的设备type文本
            $tableStr = $nlcore->cfg->db->tables["device"];
            $columnArr = ["type"];
            $whereDic = ["id" => $thisdevid];
            $thisdevtyperesult = $nlcore->db->select($columnArr,$tableStr,$whereDic);
            if ($thisdevtyperesult[0] >= 2000000 || !isset($thisdevtyperesult[2][0]["type"])) $nlcore->msg->stopmsg(2040213,$totpsecret);
            $thisdevtype = $thisdevtyperesult[2][0]["type"];
            //检查有没有超过额定限制
            if (!isset($maxlogin[$thisdevtype])) $nlcore->msg->stopmsg(2040214,$totpsecret);
            if (count($thisdevsession) >= $maxlogin[$thisdevtype]) {
                //删除本设备的旧登录状态
            }

            //获取当前 deviceid
            //为数组补充 devicetype 字段

        }

        //获取每个 session 中的 apptoken 去 totp 表查
        //totp 表中查到 deviceid，去 device 表查 os 字段
    }

    function getdeviceid($totpsecret) {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["totp"];
        $columnArr = ["devid"];
        $whereDic = ["secret" => $totpsecret];
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        if ($result[0] >= 2000000 || !isset($result[2][0]["devid"])) $nlcore->msg->stopmsg(2040210,$totpsecret);
        return $result[2][0]["devid"];
    }

    function getdeviceinfo($deviceid,$totpsecret) {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["device"];
        $columnArr = ["type","os","device","osvar"];
        $whereDic = ["id" => $deviceid];
        $resultdev = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        if (!isset($resultdev[2][0])) $nlcore->msg->stopmsg(2040212,$totpsecret);
        return $resultdev[2][0];
    }
}
?>
