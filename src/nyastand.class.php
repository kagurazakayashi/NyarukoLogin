<?php
/**
 * @description: 子賬戶註冊
 * @package NyarukoLogin
*/
class stand {
    function addstand(nyacore $nlcore, array $inputinformation, array $sessioninformation):array {
        // IP檢查和解密客戶端提交的資訊
        $jsonarr = $inputinformation[0];
        $totpsecret = $inputinformation[1];
        $totptoken = $inputinformation[2];
        $ipid = $inputinformation[3];
        $appid = $inputinformation[4];
        // 檢查用戶是否登入
        $usertoken = $sessioninformation[0];
        $usersessioninfo = $sessioninformation[1];
        $userhash = $sessioninformation[2];
        // 檢查必須提供的參數輸入是否齊全
        $getkeys = ["token","nickname"];
        if ($nlcore->safe->keyinarray($jsonarr,$getkeys) > 0) $nlcore->msg->stopmsg(2000101,$totpsecret);
        // 檢查是否允許使用這種方式註冊
        if (!$nlcore->cfg->app->logintype[2]) $nlcore->msg->stopmsg(2040103,$totpsecret);
        // 檢查輸入格式是否正確
        $newuserconf = $nlcore->cfg->app->newuser;
        $nickname = $jsonarr["nickname"];
        $nicknamelen = mb_strlen($nickname,"utf-8");
        if ($nicknamelen > $newuserconf["nicknamelen"]) $nlcore->msg->stopmsg(2040105,$totpsecret,$nickname); //暱稱太長
        // 檢查異常符號
        $nlcore->safe->safestr($nickname,true,false,$totpsecret);
        // 檢查敏感詞
        $nlcore->safe->wordfilter($nickname,true,$totpsecret);
        // 生成賬戶碼，遇到重複的重試 100 次
        for ($i=0; $i < 100; $i++) {
            $nameid = rand(1000, 9999);
            //检查昵称和状态代码是否重复
            $exists = $nlcore->func->useralreadyexists(null,$nickname,$nameid,$totpsecret);
            if ($exists) $nameid = null;
            else break;
        }
        if ($nameid == null) $nlcore->msg->stopmsg(2040200,$totpsecret,$nickname."#".$nameid);
        // 生成唯一雜湊，遇到重複的重試 10 次
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
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040108,$totpsecret);
        // 註冊 usergroup 表
        $usergroup = $newuserconf["subgroup"];
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
            "belong" => $userhash,
            "name" => $nickname,
            "nameid" => $nameid
        ];
        $tableStr = $nlcore->cfg->db->tables["info"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040111,$totpsecret);
        // 記錄 history 表
        $returnjson = $nlcore->msg->m(0,1020000);
        $insertDic = [
            "userhash" => $hash,
            "apptoken" => $totptoken,
            "operation" => "USER_SUB_SIGN_UP",
            "sender" => $nickname,
            "ipid" => $ipid,
            "result" => $returnjson["code"]
        ];
        // 返回到客戶端
        $tableStr = $nlcore->cfg->db->tables["history"];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040112,$totpsecret);
        $returnjson["username"] = $nickname."#".$nameid;
        $returnjson["userhash"] = $hash;
        $returnjson["mainuser"] = $usertoken;
        return $returnjson;
    }
}
?>