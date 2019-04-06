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
            $nlcore->msg->stopmsg(2020207,$totpsecret);
        } else if ($logintype == 1 && !$nlcore->safe->isNumberOrEnglishChar(11,11,$user)) {
            $nlcore->msg->stopmsg(2020205,$totpsecret);
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
            $nlcore->msg->stopmsg(2040105,$totpsecret);
        } else {
            $nlcore->safe->wordfilter($nickname); //检查敏感词
        }
        //生成账户代码，遇到重复的重试10次
        $nameid = 0;
        for ($i=0; $i < 10; $i++) { 
            $nameid = rand(0, 9999);
            //检查昵称和状态代码是否重复
            if (!$nyauser->useralreadyexists(null,$nickname,$nameid,$totpsecret)) break;
            if ($i == 10) $nlcore->msg->stopmsg(2040200,$totpsecret);
        }
        //检查邮箱或者手机号是否已经重复
        $isalreadyexists = $nyauser->isalreadyexists($logintype,$user,$totpsecret);
        if ($isalreadyexists == 1) $nlcore->msg->stopmsg(2040102,$totpsecret);
        //分配预设的用户组
        $user_group = $newuserconf["group"];
        //生成密码到期时间
        $password_expiration = time() + $newuserconf["pwdexpiration"];
        $password_expiration = $nyauser->getdatetime(null,$password_expiration)[1];
        //注册 users 表
        if ($logintype == 0) {
            // $insertDic["mail"] = $user; //邮件注册流程
            // $nyaverification = new nyaverification();
            // $mailinfo = $nyaverification->sendmail(); //[$mailhtml,$vcode]
        } else if ($logintype == 1) {
            $insertDic["tel"] = $user; //短信注册流程
            //TODO: 短信注册流程
        }

        //注册 password_protection 表
        


        // $result = $nlcore->db->insert($nlcore->cfg->db->tables["users"],$insertDic);


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