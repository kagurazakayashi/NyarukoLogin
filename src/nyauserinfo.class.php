<?php
class userinfo {
    function getuserinfo($echojson=true) {
        global $nlcore;
        $jsonarrTotpsecret = $nlcore->safe->decryptargv("session");
        $jsonarr = $jsonarrTotpsecret[0];
        $totpsecret = $jsonarrTotpsecret[1];
        $totptoken = $jsonarrTotpsecret[2];
        $ipid = $jsonarrTotpsecret[3];
        $appid = $jsonarrTotpsecret[4];
        // 檢查用戶是否登入
        $userhash = null;
        if (isset($jsonarr["token"]) || $nlcore->cfg->verify->needlogin["userinfo"]) {
            $usertoken = $jsonarr["token"];
            if (!$nlcore->safe->is_rhash64($usertoken)) $nlcore->msg->stopmsg(2040402,$totpsecret,"COMM".$usertoken);
            $userpwdtimes = $nlcore->sess->sessionstatuscon($usertoken,true,$totpsecret);
            $userhash = $userpwdtimes["userhash"];
            if (!$userpwdtimes) $nlcore->msg->stopmsg(2040400,$totpsecret,"COMM".$usertoken); //token無效
        }
        // 取得使用者個性化資訊
        $cuser = $jsonarr["cuser"] ?? $userhash;
        $userinfo = $nlcore->func->getuserinfo($cuser,$totpsecret,true);
        if (count($userinfo) == 0) $nlcore->msg->stopmsg(2070001,$totpsecret);
        $returnjson = [
            "code" => 1000000,
            "uinfo" => $userinfo
        ];
        echo $nlcore->safe->encryptargv($returnjson,$totpsecret);
    }
}
