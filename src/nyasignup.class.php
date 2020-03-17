<?php
require_once "nyacaptcha.class.php";
require_once "nyaverification.class.php";
class nyasignup {
    function adduser() {
        global $nlcore;
        //IP检查和解密客户端提交的信息
        $jsonarrTotpsecret = $nlcore->safe->decryptargv("signup");
        $jsonarr = $jsonarrTotpsecret[0];
        $totpsecret = $jsonarrTotpsecret[1];
        $totptoken = $jsonarrTotpsecret[2];
        $ipid = $jsonarrTotpsecret[3];
        $returnjson = [];
        //检查参数输入是否齐全
        $getkeys = ["captcha","password","user","nickname"];
        if ($nlcore->safe->keyinarray($jsonarr,$getkeys) > 0) {
            $nlcore->msg->stopmsg(2000101,$totpsecret);
        }
        //检查验证码是否正确
        $nyacaptcha = new nyacaptcha();
        if (!$nyacaptcha->verifycaptcha($totptoken,$totpsecret,$jsonarr["captcha"])) die();
        //检查输入的是邮箱还是手机号
        $user = $jsonarr["user"];
        $logintype = $nlcore->func->logintype($user,$totpsecret); //0:邮箱 1:手机号
        //检查是否允许使用这种方式注册
        if (!$nlcore->cfg->app->logintype[$logintype]) $nlcore->msg->stopmsg(2040103,$totpsecret);
        //检查输入格式是否正确
        $newuserconf = $nlcore->cfg->app->newuser;
        $userstrlen = strlen($user);
        if ($logintype == 0 && ($userstrlen < 5 || $userstrlen > $newuserconf["emaillen"] || !$nlcore->safe->isEmail($user))) {
            $nlcore->msg->stopmsg(2020207,$totpsecret,$user);
        } else if ($logintype == 1 && $userstrlen != 11) {
            $nlcore->msg->stopmsg(2020205,$totpsecret,$user);
        }
        //检查密码强度是否符合规则
        $password = $jsonarr["password"];
        $nlcore->safe->strongpassword($password);
        //检查昵称
        $nickname = $jsonarr["nickname"];
        $nicknamelen = mb_strlen($nickname,"utf-8");
        //如果没有昵称使用电子邮件前缀，如果不是电子邮件使用默认昵称加随机数
        if ($nicknamelen < 1) {
            if ($logintype == 0) {
                $nickname = explode("@", $user)[0];
            } else {
                $nickname = $newuserconf["nickname"].rand(100, 999);
            }
        } else if ($nicknamelen > $newuserconf["nicknamelen"]) {
            //昵称太长
            $nlcore->msg->stopmsg(2040105,$totpsecret,$nickname);
        }
        // 檢查異常符號
        $nlcore->safe->safestr($nickname,true,false,$totpsecret);
        // 檢查敏感詞
        $nlcore->safe->wordfilter($nickname,true,$totpsecret);
        //检查邮箱或者手机号是否已经重复
        $isalreadyexists = $nlcore->func->isalreadyexists($logintype,$user,$totpsecret);
        if ($isalreadyexists == 1) $nlcore->msg->stopmsg(2040102,$totpsecret,$user);
        //生成账户代码，遇到重复的重试100次
        $nameid = null;
        for ($i=0; $i < 100; $i++) {
            $nameid = rand(1000, 9999);
            //检查昵称和状态代码是否重复
            $exists = $nlcore->func->useralreadyexists(null,$nickname,$nameid,$totpsecret);
            if ($exists) $nameid = null;
            else break;
        }
        if ($nameid == null) $nlcore->msg->stopmsg(2040200,$totpsecret,$nickname."#".$nameid);
        //生成唯一哈希，遇到重复的重试10次
        $hash = null;
        for ($i=0; $i < 10; $i++) {
            $hash = $nlcore->safe->randstr(64);
            // $hash = $nlcore->safe->rhash64$datetime[0]);
            // 检查哈希是否存在
            $exists = $nlcore->func->isalreadyexists(2,$hash,$totpsecret);
            if ($exists) $hash = null;
            else break;
        }
        if ($hash == null) $nlcore->msg->stopmsg(2040107,$totpsecret);
        //分配预设的用户组
        $usergroup = $newuserconf["group"];
        //生成密码到期时间
        $datetime = $nlcore->safe->getdatetime();
        $timestamp = $datetime[0];
        $pwdend = $timestamp + $newuserconf["pwdexpiration"];
        $pwdend = $nlcore->safe->getdatetime(null,$pwdend)[1];
        $timestr = $datetime[1];
        //加密密码
        $passwordhash = $nlcore->safe->passwordhash($password,$pwdend);
        // 註冊 users 表
        $insertDic = [
            "hash" => $hash,
            "pwd" => $passwordhash,
            "pwdend" => $pwdend,
            "regtime" => $timestr,
            "enabletime" => $timestr
        ];
        if (isset($jsonarr["type"])) $insertDic["type"] = $jsonarr["type"];
        $returnjson["code"] = 1020000;
        if ($logintype == 0) {
            $insertDic["mail"] = $user; //邮件注册流程
            // $nyaverification = new nyaverification();
            // $mailinfo = $nyaverification->sendmail(); //[$mailhtml,$vcode]
            $returnjson["code"] = 1020001;
        } else if ($logintype == 1) {
            //短信注册流程
            $nlcore->safe->telarea = $user;
            $insertDic["telarea"] = $user[0];
            $insertDic["tel"] = $user[1];
            //TODO: 短信注册流程
            $returnjson["code"] = 1020002;
        }
        $tableStr = $nlcore->cfg->db->tables["users"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040108,$totpsecret);
        // 註冊 usergroup 表
        $insertDic = [
            "userhash" => $hash,
            "groupid" => $usergroup
        ];
        $tableStr = $nlcore->cfg->db->tables["usergroup"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040109,$totpsecret);
        // 註冊 protection 表
        $insertDic = [
            "userhash" => $hash
        ];
        $tableStr = $nlcore->cfg->db->tables["protection"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040110,$totpsecret);
        // 註冊 info 表
        $insertDic = [
            "userhash" => $hash,
            "name" => $nickname,
            "nameid" => $nameid
        ];
        $tableStr = $nlcore->cfg->db->tables["info"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040111,$totpsecret);
        // 記錄 history 表
        $insertDic = [
            "userhash" => $hash,
            "apptoken" => $totptoken,
            "operation" => "USER_SIGN_UP",
            "sender" => $user,
            "ipid" => $ipid,
            "result" => $returnjson["code"]
        ];
        $tableStr = $nlcore->cfg->db->tables["history"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040112,$totpsecret);
        // 返回到客戶端
        $returnjson["msg"] = $nlcore->msg->imsg[$returnjson["code"]];
        $returnjson["username"] = $nickname."#".$nameid;
        $returnjson["timestamp"] = $timestamp;
        echo $nlcore->safe->encryptargv($returnjson,$totpsecret);
    }
    /**
     * @description: 仅做测试用，生成加密后密码
     * @param String password 明文密码
     * @param String timestr 密码到期时间的时间文本
     * @return 直接返回加密后的内容到客户端
     */
    function passwordhashtest($password,$timestr) {
        global $nlcore;
        echo $nlcore->safe->passwordhash($password,$timestr);
    }
}
?>
