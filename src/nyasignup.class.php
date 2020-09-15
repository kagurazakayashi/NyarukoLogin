<?php
/**
 * @description: 賬戶註冊
 * @package NyarukoLogin
*/
require_once "nyacaptcha.class.php";
require_once "nyaverification.class.php";
class nyasignup {
    /**
     * @description: 功能入口：新增新使用者
     * @param Array argReceived 客戶端提交資訊陣列
     * @param String appToken 客戶端令牌
     * @param Int ipId IP地址ID
     * @param Array userHash 使用者雜湊
     * @return 準備返回到客戶端的資訊陣列
     */
    function adduser(array $argReceived, string $appToken,int  $ipId):array {
        global $nlcore;
        $returnClientData = [];
        //检查参数输入是否齐全
        $argReceivedKeys = ["captcha","password","user","nickname"];
        if ($nlcore->safe->keyinarray($argReceived,$argReceivedKeys) > 0) {
            $nlcore->msg->stopmsg(2000101);
        }
        //检查验证码是否正确
        $nyacaptcha = new nyacaptcha();
        if (!$nyacaptcha->verifycaptcha($appToken,$argReceived["captcha"])) die();
        //检查输入的是邮箱还是手机号
        $user = $argReceived["user"];
        $logintype = $nlcore->func->logintype($user); //0:邮箱 1:手机号
        //检查是否允许使用这种方式注册
        if (!$nlcore->cfg->app->logintype[$logintype]) $nlcore->msg->stopmsg(2040103);
        //检查输入格式是否正确
        $newuserconf = $nlcore->cfg->app->newuser;
        $maxLen = $nlcore->cfg->app->maxLen;
        $userstrlen = strlen($user);
        if ($logintype == 0 && ($userstrlen < 5 || $userstrlen > $maxLen["email"] || !$nlcore->safe->isEmail($user))) {
            $nlcore->msg->stopmsg(2020207,$user);
        } else if ($logintype == 1 && $userstrlen != 11) {
            $nlcore->msg->stopmsg(2020205,$user);
        }
        //检查密码强度是否符合规则
        $password = $argReceived["password"];
        $nlcore->safe->strongpassword($password);
        //检查昵称
        $nickname = $argReceived["nickname"];
        $nicknamelen = mb_strlen($nickname,"utf-8");
        //如果没有昵称使用电子邮件前缀，如果不是电子邮件使用默认昵称加随机数
        if ($nicknamelen < 1) {
            if ($logintype == 0) {
                $nickname = explode("@", $user)[0];
            } else {
                $nickname = $newuserconf["nickname"].rand(100, 999);
            }
        } else if ($nicknamelen > $maxLen["name"]) {
            //昵称太长
            $nlcore->msg->stopmsg(2040105,$nickname);
        }
        // 檢查異常符號
        $nlcore->safe->safestr($nickname,true,false);
        // 檢查敏感詞
        $nlcore->safe->wordfilter($nickname,true);
        //检查邮箱或者手机号是否已经重复
        $isalreadyexists = $nlcore->func->isalreadyexists($logintype,$user);
        if ($isalreadyexists == 1) $nlcore->msg->stopmsg(2040102,$user);
        //生成账户代码
        $nameid = $this->nlcore->func->genuserid($nickname);
        //生成唯一哈希，遇到重复的重试100次
        $hash = null;
        for ($i=0; $i < 10; $i++) {
            $hash = $nlcore->safe->randstr(64);
            // $hash = $nlcore->safe->rhash64$datetime[0]);
            // 检查哈希是否存在
            $exists = $nlcore->func->isalreadyexists(2,$hash);
            if ($exists) $hash = null;
            else break;
        }
        if ($hash == null) $nlcore->msg->stopmsg(2040107);
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
        if (isset($argReceived["type"])) $insertDic["type"] = $argReceived["type"];
        $returnClientData["code"] = 1020000;
        if ($logintype == 0) {
            $insertDic["mail"] = $user; //邮件注册流程
            // $nyaverification = new nyaverification();
            // $mailinfo = $nyaverification->sendmail(); //[$mailhtml,$vcode]
            $returnClientData["code"] = 1020001;
        } else if ($logintype == 1) {
            //短信注册流程
            $nlcore->safe->telarea = $user;
            $insertDic["telarea"] = $user[0];
            $insertDic["tel"] = $user[1];
            //TODO: 短信注册流程
            $returnClientData["code"] = 1020002;
        }
        $tableStr = $nlcore->cfg->db->tables["users"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040108);
        // 註冊 usergroup 表
        $insertDic = [
            "userhash" => $hash,
            "groupid" => $usergroup
        ];
        $tableStr = $nlcore->cfg->db->tables["usergroup"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040109);
        // 註冊 protection 表
        $insertDic = [
            "userhash" => $hash
        ];
        $tableStr = $nlcore->cfg->db->tables["protection"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040110);
        // 註冊 info 表
        $insertDic = [
            "userhash" => $hash,
            "name" => $nickname,
            "nameid" => $nameid
        ];
        $tableStr = $nlcore->cfg->db->tables["info"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040111);
        // 記錄 history 表
        $insertDic = [
            "userhash" => $hash,
            "apptoken" => $appToken,
            "operation" => "USER_SIGN_UP",
            "sender" => $user,
            "ipid" => $ipId,
            "result" => $returnClientData["code"]
        ];
        $tableStr = $nlcore->cfg->db->tables["history"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040112);
        // 返回到客戶端
        $returnClientData["userhash"] = $hash;
        $returnClientData["msg"] = $nlcore->msg->imsg[$returnClientData["code"]];
        $returnClientData["username"] = $nickname."#".$nameid;
        $returnClientData["timestamp"] = $timestamp;
        return $returnClientData;
    }
    /**
     * @description: 仅做测试用，生成加密后密码
     * @param String password 明文密码
     * @param String timestr 密码到期时间的时间文本
     * @return 直接返回加密后的内容到客户端
     */
    function passwordhashtest(string $password,string $timestr):void {
        global $nlcore;
        echo $nlcore->safe->passwordhash($password,$timestr);
    }
}
?>
