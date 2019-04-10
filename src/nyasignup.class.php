<?php
require_once "nyacaptcha.class.php";
require_once "nyaverification.class.php";
require_once "nyauser.class.php";
class nyasignup {
    var $nyadbconnect;
    function __construct() {

        //TODO: 判断输入的是邮箱还是手机号
        //TODO: 检查验证码是否正确
        //TODO: 检查邮箱或者手机号、昵称格式、长度是否正确
        //TODO: 检查昵称是否包括敏感词
        //TODO: 检查密码强度是否符合规则
        //TODO: 检查邮箱或者手机号是否已经重复
        //TODO: 分配预设的用户组
        //TODO: 获取IP等环境信息
        //TODO: 标记邮箱或者手机号码处于未激活状态
        //TODO: 对密码进行加盐哈希
        //TODO: 发送邮件或手机验证码
        //TODO: 注册该用户，提示查收验证码
    }

    function adduser() {
        global $nlcore;
        //IP检查和解密客户端提交的信息
        $jsonarrTotpsecret = $nlcore->safe->decryptargv("signup");
        $jsonarr = $jsonarrTotpsecret[0];
        $totpsecret = $jsonarrTotpsecret[1];
        $totptoken = $jsonarrTotpsecret[2];
        //检查参数输入是否齐全
        $getkeys = ["captcha","password","user","nickname"];
        if ($nlcore->safe->keyinarray($jsonarr,$getkeys) > 0) {
            die($nlcore->msg->m(1,2000101));
        }
        //检查验证码是否正确
        $nyacaptcha = new nyacaptcha();
        // if (!$nyacaptcha->verifycaptcha($totptoken,$totpsecret,$jsonarr["captcha"])) die();
        //检查输入的是邮箱还是手机号
        $nyauser = new nyauser();
        $user = $jsonarr["user"];
        $logintype = $nyauser->logintype($user,$totpsecret); //0:邮箱 1:手机号
        //检查是否允许使用这种方式注册
        if (!$nlcore->cfg->app->logintype[$logintype]) $nlcore->msg->stopmsg(2040103,$totpsecret);
        //检查输入格式是否正确
        $newuserconf = $nlcore->cfg->app->newuser;
        if ($logintype == 0 && !$nlcore->safe->isNumberOrEnglishChar(5,$newuserconf["emaillen"],$user)) {
            $nlcore->msg->stopmsg(2020207,$totpsecret,$user);
        } else if ($logintype == 1 && !$nlcore->safe->isNumberOrEnglishChar(11,11,$user)) {
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
        } else if ($nicknamelen > $newuserconf["nicknamelen"]) { //检查昵称是否太长
            $nlcore->msg->stopmsg(2040105,$totpsecret,$nickname);
        } else {
            $nlcore->safe->wordfilter($nickname); //检查敏感词
        }
        //检查邮箱或者手机号是否已经重复
        $isalreadyexists = $nyauser->isalreadyexists($logintype,$user,$totpsecret);
        if ($isalreadyexists == 1) $nlcore->msg->stopmsg(2040102,$totpsecret,$user);
        //生成账户代码，遇到重复的重试10次
        $nameid = null;
        for ($i=0; $i < 10; $i++) {
            $nameid = rand(0, 9999);
            //检查昵称和状态代码是否重复
            $exists = $nyauser->useralreadyexists(null,$nickname,$nameid,$totpsecret);
            if ($exists) $nameid = null;
            else break;
        }
        if ($nameid == null) $nlcore->msg->stopmsg(2040200,$totpsecret,$nickname."#".$nameid);
        //生成唯一哈希，遇到重复的重试10次
        $hash = null;
        for ($i=0; $i < 10; $i++) {
            $hash = $nlcore->safe->randstr(64);
            // $hash = $nlcore->safe->md6($datetime[0]);
            // 检查哈希是否存在
            $exists = $nyauser->isalreadyexists(2,$hash,$totpsecret);
            if ($exists) $hash = null;
            else break;
        }
        if ($hash == null) $nlcore->msg->stopmsg(2040107,$totpsecret);
        //分配预设的用户组
        $usergroup = $newuserconf["group"];
        //生成密码到期时间
        $datetime = $nlcore->safe->getdatetime();
        $pwdend = $datetime[0] + $newuserconf["pwdexpiration"];
        $pwdend = $nyauser->getdatetime(null,$pwdend)[1];
        $timestr = $datetime[1];
        //加密密码: 原文+自定义盐+注册时间戳 的 MD6
        $passwordhash = $password.$nlcore->cfg->app->passwordsalt.strval($datetime[0]);
        $passwordhash = $nlcore->safe->md6($passwordhash);
        //注册 users 表
        $insertDic = [
            "hash" => $hash,
            "pwd" => $passwordhash,
            "pwdend" => $pwdend,
            "regtime" => $timestr,
            "enabletime" => $timestr
        ];
        if ($logintype == 0) {
            $insertDic["mail"] = $user; //邮件注册流程
            // $nyaverification = new nyaverification();
            // $mailinfo = $nyaverification->sendmail(); //[$mailhtml,$vcode]
        } else if ($logintype == 1) {
            $insertDic["tel"] = $user; //短信注册流程
            //TODO: 短信注册流程
        }
        $result = $nlcore->db->insert($nlcore->cfg->db->tables["users"],$insertDic);

        //注册 usergroup 表
        $insertDic = [
            "userhash" => $hash,
            "groupid" => $usergroup
        ];
        $result = $nlcore->db->insert($nlcore->cfg->db->tables["usergroup"],$insertDic);

        //注册 protection 表
        $insertDic = [
            "userhash" => $hash
        ];
        $result = $nlcore->db->insert($nlcore->cfg->db->tables["protection"],$insertDic);

        //注册 info 表
        $insertDic = [
            "userhash" => $hash,
            "name" => $nickname,
            "nameid" => $nameid
        ];
        $result = $nlcore->db->insert($nlcore->cfg->db->tables["info"],$insertDic);

        //记录 history 表
        $insertDic = [
            "userhash" => $hash,
            "name" => $nickname,
            "nameid" => $nameid
        ];
        $result = $nlcore->db->insert($nlcore->cfg->db->tables["history"],$insertDic);

        // if ($nlcore->safe->isPhoneNumCN($user)) {

        //
        // } else if ($nlcore->safe->isEmail($user)) {

        // } else {
        //     die($nlcore->msg->m(1,2020206));
        // }



        //注册该用户，设置有效时长
        echo $nlcore->safe->encryptargv($jsonarr,$totpsecret);
    }

    function dbw_users() {

    }

    function isuserempty() {
        global $nlcore;
        $sdata = $nlcore->db->scount($nlcore->cfg->db->tb_user);
        print_r($sdata);
    }
}
?>
