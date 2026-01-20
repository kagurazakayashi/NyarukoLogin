<?php
declare(strict_types=1);

/**
 * 修改密碼
 *
 * 處理使用者密碼變更，支援透過手機號或郵箱進行密碼重設。
 *
 * @package NyarukoLogin
 */
class nyachangepassword {
    /**
     * 修改密碼
     *
     * @param array $argReceived 客戶端提交資訊陣列
     * @return array 準備返回到客戶端的資訊陣列
     */
    function changepassword(array $argReceived): array {
        global $nlcore;
        $userid = $argReceived["userid"] ?? $nlcore->msg->stopmsg(2030104, '1');
        // $oldpassword = $argReceived["oldpassword"] ?? $nlcore->msg->stopmsg(2030104,'2');
        $newpassword = $argReceived["newpassword"] ?? $nlcore->msg->stopmsg(2030104, '3');
        $pretoken = $argReceived["pretoken"] ?? $nlcore->msg->stopmsg(2030104, '4');
        $rePretoken = $nlcore->sess->sessionstatuscon($pretoken, false, 1);
        if (count($rePretoken) == 0) {
            die();
        }
        $isphone = true;
        if (!$nlcore->safe->isPhoneNumCN($userid)) {
            if ($nlcore->safe->isEmail($userid)) {
                $isphone = false;
            } else {
                $nlcore->msg->stopmsg(2020206);
            }
        }
        // 檢查密碼強度是否符合規則
        $nlcore->safe->strongpassword($newpassword);
        // 生成密碼到期時間
        $datetime = $nlcore->safe->getdatetime();
        $timestamp = $datetime[0];
        // 檢查輸入格式是否正確
        $newuserconf = $nlcore->cfg->app->newuser;
        $pwdend = $timestamp + $newuserconf["pwdexpiration"];
        $pwdend = $nlcore->safe->getdatetime(null, $pwdend)[1];
        // $oldpasswordhash = $nlcore->safe->passwordhash($oldpassword, $pwdend);
        $newpasswordhash = $nlcore->safe->passwordhash($newpassword, $pwdend);
        // 尝试修改密码
        $updateDic = ["pwd" => $newpasswordhash, "pwdend" => $pwdend];
        $tableStr = $nlcore->cfg->db->tables["users"];
        // $whereDic = ["pwd" => $pwdend];
        if ($isphone) {
            $whereDic = ["tel" => $userid];
        } else {
            $whereDic = ["mail" => $userid];
        }
        $result = $nlcore->db->update($updateDic, $tableStr, $whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2030104);
        if ($result[3] == 0) {
            // $nlcore->msg->stopmsg(2030103,$oldpasswordhash);
            $nlcore->msg->stopmsg(2030103, $isphone ? 'phone' : 'mail');
        }
        $returnClientData = $nlcore->msg->m(0, 1000000);
        return $returnClientData;
    }
}
