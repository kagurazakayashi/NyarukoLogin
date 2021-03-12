<?php
class userinfo {
    /**
     * @description: 功能入口：獲取當前使用者資料
     * @param String 使用者雜湊
     * @return array 準備返回到客戶端的資訊陣列
     */
    function getuserinfo($userHash): array {
        global $nlcore;
        $inputInformation = $nlcore->sess->decryptargv("session");
        $argReceived = $inputInformation[0];
        // $totpToken = $inputInformation[2];
        // $ipid = $inputInformation[3];
        // $appid = $inputInformation[4];
        // 取得使用者個性化資訊
        $cuser = $argReceived["cuser"] ?? $userHash;
        $userinfo = $nlcore->func->getuserinfo($cuser);
        $groupInfos = $nlcore->sess->inGroup();
        $userinfo["groupCode"] = $groupInfos[1];
        $userinfo["groupName"] = $groupInfos[2];
        $userinfo["permissions"] = explode(',', $groupInfos[3]);
        if (count($userinfo) == 0) $nlcore->msg->stopmsg(2070001);
        $returnJson = $nlcore->msg->m(0, 1030202);
        $returnJson["uinfo"] = $userinfo;
        return $returnJson;
    }
}
