<?php
require_once "nyacaptcha.class.php";
require_once "nyaverification.class.php";
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
        $user = $jsonarr["user"];
        $logintype = 0; //1:手机号 2:邮箱
        if ($nlcore->safe->isPhoneNumCN($user)) {
            $logintype = 1;
        } else if ($nlcore->safe->isEmail($user)) {
            $logintype = 2;
        } else {
            die($nlcore->msg->m(1,2020206));
        }
        






        if ($nlcore->safe->isPhoneNumCN($user)) {
            //注册 users 表
            //TODO: 短信注册流程
        } else if ($nlcore->safe->isEmail($user)) {
            //注册 users 表
            $nyaverification = new nyaverification();
            $mailinfo = $nyaverification->sendmail(); //[$mailhtml,$vcode]
        } else {
            die($nlcore->msg->m(1,2020206));
        }

        //注册 password_protection 表

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