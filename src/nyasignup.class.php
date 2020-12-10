<?php

/**
 * @description: 賬戶註冊
 * @package NyarukoLogin
 */
require_once "nyavcode.class.php";
class nyasignup {
    /**
     * @description: 功能入口：新增新使用者
     * @param Array argReceived 客戶端提交資訊陣列
     * @param String appToken 客戶端令牌
     * @param Int ipId IP地址ID
     * @param Array userHash 使用者雜湊
     * @return 準備返回到客戶端的資訊陣列
     */
    function adduser(array $argReceived, string $appToken, int $ipId): array {
        global $nlcore;
        $returnClientData = [];
        // 檢查輸入的是郵箱還是手機號
        $user = $argReceived["user"] ?? $nlcore->msg->stopmsg(2000101);
        $logintype = $nlcore->func->logintype($user); //0:郵箱 1:手機號
        $logincaptcha = $nlcore->cfg->app->logincaptcha;
        // 密碼是否為可選項
        $noPassword = false;
        if (!isset($argReceived['password'])) {
            if ($nlcore->cfg->app->needpassword || !isset($argReceived["vcode"])) {
                $nlcore->msg->stopmsg(2000101);
            } else {
                $noPassword = true;
            }
        }
        // 檢查驗證碼是否正確
        if (isset($argReceived["captcha"])) {
            if ($logincaptcha[2] == false) $nlcore->msg->stopmsg(2020605);
            $nyacaptcha = new nyacaptcha();
            if (!$nyacaptcha->verifycaptcha($appToken, $argReceived["captcha"])) $nlcore->msg->stopmsg(2020600);
        } else if (isset($argReceived["vcode"])) {
            if (($logintype == 0 && $logincaptcha[0] == false) || ($logintype == 1 && $logincaptcha[1] == false)) {
                $nlcore->msg->stopmsg(2020605);
            }
            $nyavcode = new nyavcode();
            $logintypestr = ["mail", "sms"];
            $nyavcode->check($argReceived["vcode"], $logintypestr[$logintype]);
        }
        // 檢查是否允許使用這種方式註冊
        if (!$nlcore->cfg->app->logintype[$logintype]) $nlcore->msg->stopmsg(2040103);
        // 檢查輸入格式是否正確
        $newuserconf = $nlcore->cfg->app->newuser;
        $maxLen = $nlcore->cfg->app->maxLen;
        $userstrlen = strlen($user);
        if ($logintype == 0 && ($userstrlen < 5 || $userstrlen > $maxLen["email"] || !$nlcore->safe->isEmail($user))) {
            $nlcore->msg->stopmsg(2020207, $user);
        } else if ($logintype == 1 && $userstrlen != 11) {
            $nlcore->msg->stopmsg(2020205, $user);
        }
        // 檢查密碼強度是否符合規則
        if (!$noPassword) {
            $password = $argReceived["password"];
            $nlcore->safe->strongpassword($password);
        }
        // 檢查暱稱
        $nickname = $argReceived["nickname"];
        $nicknamelen = mb_strlen($nickname, "utf-8");
        // 如果沒有暱稱，則直接使用登入憑據作為名字
        if ($nicknamelen < 1) {
            $nickname = $user;
        } else if ($nicknamelen > $maxLen["name"]) {
            $nlcore->msg->stopmsg(2040105, $nickname); // 暱稱太長
        }
        // 檢查異常符號
        $nlcore->safe->safestr($nickname, true, false);
        // 檢查敏感詞
        $nlcore->safe->wordfilter($nickname);
        // 檢查郵箱或者手機號是否已經重複
        $isalreadyexists = $nlcore->func->isalreadyexists($logintype, $user);
        if ($isalreadyexists == 1) $nlcore->msg->stopmsg(2040102, $user);
        // 生成唯一雜湊，遇到重複的重試 10 次
        $hash = null;
        for ($i = 0; $i < 10; $i++) {
            $hash = $nlcore->safe->randstr(64);
            // 檢查雜湊是否存在
            $exists = $nlcore->func->isalreadyexists(2, $hash);
            if ($exists) $hash = null;
            else break;
        }
        if ($hash == null) $nlcore->msg->stopmsg(2040107);
        // 生成賬戶程式碼
        $nameid = $nlcore->func->genuserid($nickname, $hash);
        // 分配預設的使用者組
        $usergroup = $newuserconf["group"];
        // 生成密碼到期時間
        $datetime = $nlcore->safe->getdatetime();
        $timestamp = $datetime[0];
        $pwdend = $timestamp + $newuserconf["pwdexpiration"];
        $pwdend = $nlcore->safe->getdatetime(null, $pwdend)[1];
        $timestr = $datetime[1];
        // 加密密碼
        $passwordhash = $noPassword ? null : $nlcore->safe->passwordhash($password, $pwdend);
        // 註冊 users 表
        $insertDic = [
            "hash" => $hash,
            "pwd" => $passwordhash,
            "pwdend" => $pwdend,
            "regtime" => $timestr,
            "enabletime" => $timestr
        ];
        if ($logintype == 0) {
            $insertDic["mail"] = $user;
        } else if ($logintype == 1) {
            $tel = $nlcore->safe->telarea($user);
            $insertDic["telarea"] = $tel[0];
            $insertDic["tel"] = $tel[1];
        }
        if (isset($argReceived["type"])) $insertDic["type"] = $argReceived["type"];
        $returnClientData["code"] = 1020000;
        $tableStr = $nlcore->cfg->db->tables["users"];
        $result = $nlcore->db->insert($tableStr, $insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040108);
        // 註冊 usergroup 表
        $insertDic = [
            "userhash" => $hash,
            "groupid" => $usergroup
        ];
        $tableStr = $nlcore->cfg->db->tables["usergroup"];
        $result = $nlcore->db->insert($tableStr, $insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040109);
        // 註冊 protection 表
        $insertDic = [
            "userhash" => $hash
        ];
        $tableStr = $nlcore->cfg->db->tables["protection"];
        $result = $nlcore->db->insert($tableStr, $insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040110);
        // 註冊 info 表
        $insertDic = [
            "userhash" => $hash,
            "name" => $nickname,
            "nameid" => $nameid
        ];
        // 檢查可選欄位
        $userInfoEdit = new userInfoEdit($nlcore->sess->argReceived);
        $infoEditDic = $userInfoEdit->batchUpdate();
        if ($infoEditDic && count($infoEditDic) > 0) $insertDic = array_merge($insertDic, $infoEditDic);
        // 更新 info 表
        $tableStr = $nlcore->cfg->db->tables["info"];
        $result = $nlcore->db->insert($tableStr, $insertDic);
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
        $result = $nlcore->db->insert($tableStr, $insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040112);
        // 返回到客戶端
        $returnClientData["userhash"] = $hash;
        $returnClientData["msg"] = $nlcore->msg->imsg[$returnClientData["code"]];
        $returnClientData["username"] = $nickname . "#" . $nameid;
        $returnClientData["timestamp"] = $timestamp;
        return $returnClientData;
    }
    /**
     * @description: 僅做測試用，生成加密後密碼
     * @param String password 明文密碼
     * @param String timestr 密碼到期時間的時間文字
     * @return 直接返回加密後的內容到客戶端
     */
    function passwordhashtest(string $password, string $timestr): void {
        global $nlcore;
        echo $nlcore->safe->passwordhash($password, $timestr);
    }
}
