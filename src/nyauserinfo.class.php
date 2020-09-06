<?php
class userinfo {
    function getuserinfo($echojson=true) {
        global $nlcore;
        $inputInformation = $nlcore->sess->decryptargv("session");
        $argReceived = $inputInformation[0];
        $totpSecret = $inputInformation[1];
        $totpToken = $inputInformation[2];
        $ipid = $inputInformation[3];
        $appid = $inputInformation[4];
        // 檢查用戶是否登入
        $userHash = null;
        if (isset($argReceived["token"]) || $nlcore->cfg->verify->needlogin["userinfo"]) {
            $usertoken = $argReceived["token"];
            if (!$nlcore->safe->is_rhash64($usertoken)) $nlcore->msg->stopmsg(2040402,$totpSecret,"UINF".$usertoken);
            $userpwdtimes = $nlcore->sess->sessionstatuscon($usertoken,true,$totpSecret);
            $userHash = $userpwdtimes["userhash"];
            if (!$userpwdtimes) $nlcore->msg->stopmsg(2040400,$totpSecret,"COMM".$usertoken); //token無效
        }
        // 取得使用者個性化資訊
        $cuser = $argReceived["cuser"] ?? $userHash;
        $userinfo = $nlcore->func->getuserinfo($cuser,$totpSecret);
        if (count($userinfo) == 0) $nlcore->msg->stopmsg(2070001,$totpSecret);
        $returnJson = $nlcore->msg->m(0,1030300);
        $returnJson["uinfo"] = $userinfo;
        echo $nlcore->sess->encryptargv($returnJson,$totpSecret);
    }
}