<?php

/**
 * @description: 使用者登入
 * @package NyarukoLogin
 */
class nyachangepassword
{
    /**
     * @description: 功能入口：使用者登入
     * @param string userid 用户名
     * @param string oldpassword 旧密码
     * @param string newpassword 新密码
     * @return 準備返回到客戶端的資訊陣列
     */
    function changepassword(string $userid, string $oldpassword, string $newpassword): bool
    {
        global $nlcore;
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
        $passwordhash = $nlcore->safe->passwordhash($newpassword, $pwdend);
        // 尝试修改密码
        $updateDic = ["pwd" => $passwordhash];
        $tableStr = $nlcore->cfg->db->tables["users"];
        $whereDic = $isphone ? ["telarea" => $userid] : ["mail" => $userid];
        $result = $nlcore->db->update($updateDic, $tableStr, $whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040116);
        return true;
    }
}
