<?php

/**
 * @description: 子賬戶註冊
 * @package NyarukoLogin
 */
class stand {
    /**
     * @description: 功能入口：子賬戶註冊
     * @param Array argReceived 客戶端提交資訊陣列
     * @param String appToken 客戶端令牌
     * @param Int ipId IP地址ID
     * @param Array userHash 使用者雜湊
     * @return 準備返回到客戶端的資訊陣列
     */
    function addstand($argReceived, $appToken, $ipId, $userHash): array {
        global $nlcore;
        // 檢查必須提供的參數輸入是否齊全
        $argReceivedKeys = ["token", "nickname"];
        if ($nlcore->safe->keyinarray($argReceived, $argReceivedKeys) > 0) $nlcore->msg->stopmsg(2000101);
        // 檢查是否允許使用這種方式註冊
        if (!$nlcore->cfg->app->logintype[2]) $nlcore->msg->stopmsg(2040103);
        // 檢查輸入格式是否正確
        $newuserconf = $nlcore->cfg->app->newuser;
        $nickname = $argReceived["nickname"];
        $nicknamelen = mb_strlen($nickname, "utf-8");
        if ($nicknamelen > $nlcore->cfg->app->maxLen["name"]) $nlcore->msg->stopmsg(2040105, $nickname); //暱稱太長
        // 檢查異常符號
        $nlcore->safe->safestr($nickname, true, false);
        // 檢查敏感詞
        $nlcore->safe->wordfilter($nickname);
        // 生成賬戶碼，遇到重複的重試 100 次
        for ($i = 0; $i < 100; $i++) {
            $nameid = rand(1000, 9999);
            //检查昵称和状态代码是否重复
            $exists = $nlcore->func->useralreadyexists(null, $nickname, $nameid);
            if ($exists) $nameid = null;
            else break;
        }
        if ($nameid == null) $nlcore->msg->stopmsg(2040200, $nickname . "#" . $nameid);
        // 生成唯一雜湊，遇到重複的重試 10 次
        $hash = null;
        for ($i = 0; $i < 10; $i++) {
            $hash = $nlcore->safe->randstr(64);
            // $hash = $nlcore->safe->rhash64$datetime[0]);
            // 检查哈希是否存在
            $exists = $nlcore->func->isalreadyexists(2, $hash);
            if ($exists) $hash = null;
            else break;
        }
        if ($hash == null) $nlcore->msg->stopmsg(2040107);
        // 分配預設的使用者組
        $usergroup = $newuserconf["group"];
        // 此處跳過密碼建立
        $datetime = $nlcore->safe->getdatetime();
        $timestr = $datetime[1];
        // 註冊 users 表
        $insertDic = [
            "hash" => $hash,
            "type" => 1
        ];
        $tableStr = $nlcore->cfg->db->tables["users"];
        $result = $nlcore->db->insert($tableStr, $insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040108);
        // 註冊 usergroup 表
        $usergroup = $newuserconf["subgroup"];
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
            "belong" => $userHash,
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
        $returnClientData = $nlcore->msg->m(0, 1020000);
        $insertDic = [
            "userhash" => $hash,
            "apptoken" => $appToken,
            "operation" => "USER_SUB_SIGN_UP",
            "sender" => $nickname,
            "ipid" => $ipId,
            "result" => $returnClientData["code"]
        ];
        // 返回到客戶端
        $tableStr = $nlcore->cfg->db->tables["history"];
        $result = $nlcore->db->insert($tableStr, $insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040112);
        $returnClientData["username"] = $nickname . "#" . $nameid;
        $returnClientData["userhash"] = $hash;
        $returnClientData["mainuser"] = $userHash;
        return $returnClientData;
    }
}
