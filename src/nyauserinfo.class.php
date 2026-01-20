<?php
declare(strict_types=1);

/**
 * 使用者資訊擷取
 *
 * 提供取得當前使用者或指定使用者之個人資料、群組與權限的功能。
 *
 * @package NyarukoLogin
 */
class userinfo {
    /**
     * 功能入口：獲取當前使用者資料
     *
     * @param string $userHash 使用者雜湊
     * @return array 準備返回到客戶端的資訊陣列
     */
    function getuserinfo(string $userHash): array {
        global $nlcore;
        $argReceived = $nlcore->sess->argReceived;
        // 取得使用者個性化資訊
        $cuser = $argReceived["cuser"] ?? $userHash;
        $userinfo = $nlcore->func->getuserinfo($cuser);
        if (empty($userinfo)) {
            $nlcore->msg->stopmsg(2070001);
        }
        $groupInfos = $nlcore->sess->inGroup();
        $userinfo["groupCode"] = $groupInfos[1];
        $userinfo["groupName"] = $groupInfos[2];
        $userinfo["permissions"] = explode(',', $groupInfos[3]);
        $returnJson = $nlcore->msg->m(0, 1030202);
        $returnJson["uinfo"] = $userinfo;
        return $returnJson;
    }
}
