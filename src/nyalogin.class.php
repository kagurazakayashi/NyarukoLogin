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

        //检查是否需要两步验证
        $fa = $userinfoarr["2fa"];
        if ($fa || $fa != "") {
            $faarr = explode(",", $fa);
            if (!isset($jsonarr["2famode"]) || !isset($jsonarr["2fa"])) {
                $returnjson = $nlcore->msg->m(0,2040300);
                $returnjson["supported2fa"] = $faarr;
                if (in_array("qa", $faarr)) {
                    $returnjson["question"] = getqa($userhash,$totpsecret);
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
                if (!is_numeric($faval) || strlen($faval) != 6) $nlcore->msg->stopmsg(2040303,$totpsecret);
                //TODO: 检查TOTP
            } else if ($jsonarr["2famode"] == "qa") {
                //密码提示问题
                // $nlcore->msg->stopmsg(2040304,$totpsecret);
                //TODO: 检查密码提示问题
            } else if ($jsonarr["2famode"] == "rc") {
                //恢复代码
                if (strlen($faval) != 25) $nlcore->msg->stopmsg(2040305,$totpsecret);
                //TODO: 检查恢复代码，没问题则删除代码
            }
        }
    }

    /**
     * @description: 取出密码提示问题
     * @param String userhash 用户哈希
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @param Boolean all : true=顺序全部取出 false=乱序取出随机两个
     * @return Array<String> 密码提示问题数组
     */
    function getqa($userhash,$totpsecret,$all=false) {
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
}

?>
