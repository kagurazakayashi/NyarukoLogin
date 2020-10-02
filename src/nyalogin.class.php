<?php

/**
 * @description: 使用者登入
 * @package NyarukoLogin
 */
class nyalogin {
    /**
     * @description: 功能入口：使用者登入
     * @param Array argReceived 客戶端提交資訊陣列
     * @param String appToken 客戶端令牌
     * @param Int ipId IP地址ID
     * @return 準備返回到客戶端的資訊陣列
     */
    function login(array $argReceived, string $appToken, int $ipid): array {
        global $nlcore;
        // IP檢查和解密客戶端提交的資訊
        $returnClientData = [];
        $process = "use=";
        $enableLoginType = $nlcore->cfg->app->logintype;
        // 檢查輸入是否齊全
        if (!isset($argReceived['user']) || (!isset($argReceived['vcode']) && !isset($argReceived['password']))) {
            $nlcore->msg->stopmsg(2000101);
        }
        // 檢查是郵箱還是手機號
        $user = $argReceived["user"];
        $logintype = $nlcore->func->logintype($user); // 0:郵箱 1:手機號
        // 如果提供了郵箱或簡訊驗證碼，預檢查
        $vcodeOK = false;
        if (isset($argReceived['vcode']) && $enableLoginType[$logintype]) {
            $nyavcode = new nyavcode();
            $logintypestr = ["mail", "sms"];
            $nyavcode->check($argReceived["vcode"], $logintypestr[$logintype]);
            $vcodeOK = true;
        }
        // 取出基礎資料
        $tableStr = $nlcore->cfg->db->tables["users"];
        $columnArr = ["id", "hash", "pwd", "mail", "telarea", "tel", "pwdend", "2fa", "fail", "enabletime", "errorcode"];
        if ($logintype == 0) {
            $whereDic = ["mail" => $user];
            $process .= "mail";
        } else if ($logintype == 1) {
            $tel = $nlcore->safe->telarea($user);
            $whereDic = [
                "telarea" => $tel[0],
                "tel" => $tel[1]
            ];
            $process .= "tel";
        }
        $result = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        // 檢查使用者是否存在
        if ($result[0] == 1010000) {
            // 使用者存在
        } else if ($result[0] == 1010001) {
            $nlcore->msg->stopmsg(2040201);
        } else {
            $nlcore->msg->stopmsg(2040200);
        }
        $userinfoarr = $result[2][0];
        // 檢查登入失敗次數
        $loginfail = intval($userinfoarr["fail"]);
        $process .= ",fail=" . $userinfoarr["fail"];
        // 檢查是否需要輸入圖形驗證碼，如果已經提供了郵箱或簡訊驗證碼則不再檢查圖形驗證碼
        if (!$vcodeOK) {
            $needcaptcha = $nlcore->func->needcaptcha($loginfail);
            if ($needcaptcha == "") {
                // 不需要驗證碼
                $process .= ",usecaptcha=no";
            } else if ($needcaptcha == "captcha" && $enableLoginType[2]) { // 需要圖形驗證碼
                $process .= ",usecaptcha=yes";
                if (isset($argReceived["captcha"])) {
                    // 有驗證碼，檢查驗證碼是否正確，不正確重新發放一個
                    $nyacaptcha = new nyacaptcha();
                    if (!$nyacaptcha->verifycaptcha($appToken, $argReceived["captcha"])) die();
                } else {
                    // 沒有驗證碼
                    $this->getcaptcha(2040202); // 發放一個新的驗證碼
                }
            } else {
                $nlcore->msg->stopmsg(2040203);
            }
        }
        // 檢查使用者名稱和密碼，如果已經提供了郵箱或簡訊驗證碼則不再檢查密碼
        $userHash = $userinfoarr["hash"];
        $userid = $userinfoarr["id"];
        $userfail = $userinfoarr["fail"];
        if (!$vcodeOK) {
            if ($enableLoginType[2] == false) $nlcore->msg->stopmsg(2040115);
            $password = $nlcore->safe->passwordhash($argReceived["password"], $userinfoarr["pwdend"]);
            if ($password != $userinfoarr["pwd"]) {
                // 密碼錯誤。記錄歷史記錄。
                $this->loginfailuretimes($userid, $userfail);
                $nlcore->func->writehistory("USER_SIGN_IN", 2040204, $userHash, $appToken, $ipid, $user, $process);
                // 預估下次是否會被要求驗證碼
                $needcaptcha = $nlcore->func->needcaptcha($loginfail + 1);
                if ($needcaptcha == "captcha") { // 需要圖形驗證碼
                    $this->getcaptcha(2040208); // 發放一個新的驗證碼
                }
                $nlcore->msg->stopmsg(2040204);
            }
            $process .= ",password=ok";
        } else {
            $process .= ",password=vcode";
        }
        // 檢查賬戶是否異常
        $alertinfo = [null, null];
        $process .= ",alertcode=" . $userinfoarr["errorcode"];
        if ($userinfoarr["errorcode"] != 0) {
            $alertinfo = [$userinfoarr["errorcode"], $nlcore->msg->imsg[$userinfoarr["errorcode"]]];
        }
        // 檢查賬戶是否被封禁
        $datetime = $nlcore->safe->getdatetime();
        $timestamp = $datetime[0];
        $timestr = $datetime[1];
        $process .= ",enabletime=" . $userinfoarr["enabletime"];
        if (strtotime($userinfoarr["enabletime"]) > $timestamp) {
            // 發現封禁。記錄歷史記錄。同時返回：封禁到日期和原因。
            $this->loginfailuretimes($userid, $userfail);
            $nlcore->func->writehistory("USER_SIGN_IN", 2040205, $userHash, $appToken, $ipid, $user, $process);
            $returnClientData = $nlcore->msg->m(0, 2040205, $alertinfo[1]);
            $returnClientData["enabletime"] = $userinfoarr["enabletime"];
            echo $nlcore->sess->encryptargv($returnClientData);
            die();
        }
        // 檢查登入是否封頂，如果封頂，同裝置最早的登入踢下線，並推送郵件
        $overflowsession = $this->chkoverflowsession($userHash, $appToken);
        if ($overflowsession) {
            $overprog = ",overflowsession=" . json_encode($overflowsession);
            $process .= $overprog;
            //TODO: 傳送頂掉通知郵件
        }
        // 檢查是否需要兩步驗證
        $fa = $userinfoarr["2fa"];
        if ($fa || $fa != "") {
            $process .= ",2fa=" . $userinfoarr["2fa"];
            $faarr = explode(",", $fa);
            if (!isset($argReceived["2famode"]) || !isset($argReceived["2fa"])) {
                // 沒有提供兩步驗證資訊則返回都開通了那些兩步驗證方式
                $returnClientData = $nlcore->msg->m(0, 2040300);
                $returnClientData["supported2fa"] = $faarr;
                if (in_array("qa", $faarr)) {
                    $returnClientData["question"] = $nlcore->func->getquestion($userHash);
                }
                echo $nlcore->sess->encryptargv($returnClientData);
                die();
            }
            if (!in_array($argReceived["2famode"], $faarr)) {
                $nlcore->msg->stopmsg(2040302);
            }
            $faval = $argReceived["2fa"];
            if ($argReceived["2famode"] == "ga") {
                //TOTP 碼
                if (!is_numeric($faval) || strlen($faval) != 6) {
                    //TOTP 碼錯誤。記錄歷史記錄。
                    $this->loginfailuretimes($userid, $userfail);
                    $nlcore->func->writehistory("USER_SIGN_IN", 2040303, $userHash, $appToken, $ipid, $user, $process);
                    $nlcore->msg->stopmsg(2040303);
                }
                //TODO: 檢查TOTP
            } else if ($argReceived["2famode"] == "qa") {
                // 密碼提示問題
                // $this->loginfailuretimes($userid,$userfail);
                // $nlcore->func->writehistory("USER_SIGN_IN",2040304,$userHash,$appToken,$ipid,$user,$process);
                // $nlcore->msg->stopmsg(2040304);
                //TODO: 檢查密碼提示問題
            } else if ($argReceived["2famode"] == "rc") {
                // 一次性恢復程式碼
                if (strlen($faval) != 25) {
                    // 恢復程式碼錯誤。記錄歷史記錄。
                    $this->loginfailuretimes($userid, $userfail);
                    $nlcore->func->writehistory("USER_SIGN_IN", 2040305, $userHash, $appToken, $ipid, $user, $process);
                    $nlcore->msg->stopmsg(2040305);
                }
                //TODO: 檢查恢復程式碼，沒問題則刪除恢復程式碼
            } else if ($argReceived["2famode"] == "sm") {
                //TODO: 檢查簡訊驗證碼，如果沒有則傳送一條
            } else if ($argReceived["2famode"] == "ma") {
                //TODO: 檢查郵件驗證碼，如果沒有則傳送一條
            }
        } else {
            $process .= ",2fa=no";
        }
        // 分配 token
        $token = $nlcore->safe->rhash64($userHash . $timestamp);
        $tokentimeout = 0;
        if (isset($argReceived["timeout"])) {
            $tokentimeout = intval($argReceived["timeout"]);
        } else {
            $tokentimeout = $nlcore->cfg->verify->tokentimeout;
        }
        $tokentimeout += $timestamp;
        $tokentimeoutstr = $nlcore->safe->getdatetime(null, $tokentimeout)[1];
        $deviceid = $nlcore->func->getdeviceid($appToken);
        // 獲取 UA
        $ua = null;
        if (isset($argReceived["ua"]) && strlen($argReceived["ua"]) > 0) {
            $ua = $argReceived["ua"];
        } else if (isset($_SERVER["HTTP_USER_AGENT"]) && strlen($_SERVER["HTTP_USER_AGENT"]) > 0) {
            $ua = $_SERVER["HTTP_USER_AGENT"];
        }
        // 獲取裝置型別
        $devicetype = $nlcore->func->getdeviceinfo($deviceid, true);
        $insertDic = [
            "token" => $token,
            "apptoken" => $appToken,
            "userhash" => $userHash,
            "ipid" => $ipid,
            "devid" => $deviceid,
            "devtype" => $devicetype,
            "time" => $timestr,
            "endtime" => $tokentimeoutstr
        ];
        $tableStr = $nlcore->cfg->db->tables["session"];
        $result = $nlcore->db->insert($tableStr, $insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040113);
        // 查詢使用者具體資料
        $userexinfoarr = $nlcore->func->getuserinfo($userHash);
        // 清除連續登入失敗次數
        if ($userfail > 0) $this->loginfailuretimes($userid);
        // 寫入成功歷史記錄
        $nlcore->func->writehistory("USER_SIGN_IN", 1020100, $userHash, $appToken, $ipid, $user, $process, $token);
        // 返回到客戶端
        $returnClientData = [];
        if ($alertinfo[0] == 3000000) {
            $returnClientData = $nlcore->msg->m(0, 1020102);
        } else if ($alertinfo[0] != null) {
            $returnClientData = $nlcore->msg->m(0, 1020101);
            $returnClientData["msg"] = $returnClientData["msg"] . $alertinfo[1];
        } else {
            $returnClientData = $nlcore->msg->m(0, 1020100);
        }
        $returnClientData = array_merge($returnClientData, [
            "token" => $token,
            "timestamp" => $timestamp,
            "endtime" => $tokentimeout,
            "mail" => $userinfoarr["mail"],
            "telarea" => $userinfoarr["telarea"],
            "tel" => $userinfoarr["tel"],
            "userinfo" => $userexinfoarr
        ]);
        if ($overflowsession) $returnClientData["logout"] = $overflowsession;
        return $returnClientData;
    }
    /**
     * @description: 修改當前使用者的登入失敗計數
     * @param Int users 資料表 ID
     * @param Int/String fail 當前登入失敗次數，-1 則清除失敗次數
     */
    function loginfailuretimes($id, $fail = -1) {
        global $nlcore;
        $f = intval($fail) + 1;
        $updateDic = ["fail" => $f];
        $tableStr = $nlcore->cfg->db->tables["users"];
        $whereDic = ["id" => $id];
        $result = $nlcore->db->update($updateDic, $tableStr, $whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040112);
    }
    /**
     * @description: 建立一份新的驗證碼並返回客戶端
     */
    function getcaptcha($code) {
        global $nlcore;
        $nyacaptcha = new nyacaptcha();
        $newcaptcha = $nyacaptcha->getcaptcha(false, false, false);
        $returnClientData = $nlcore->msg->m(0, $code);
        $returnClientData["img"] = $newcaptcha["img"];
        echo $nlcore->sess->encryptargv($returnClientData);
        die();
    }
    /**
     * @description: 檢查當前裝置型別和總共的同時登入數是否超出限制
     * @param String userhash 使用者雜湊
     * @return Array 被登出的裝置的資訊（手機型號等）
     */
    function chkoverflowsession($userHash, $appToken) {
        global $nlcore;
        //在 totp 表取 devid 獲得當前裝置資訊
        $tableStr = $nlcore->cfg->db->tables["encryption"];
        $columnArr = ["devid"];
        $whereDic = ["apptoken" => $appToken];
        $result = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        if ($result[0] >= 2000000 || !isset($result[2][0]["devid"])) $nlcore->msg->stopmsg(2040213);
        $thisdevid = $result[2][0]["devid"];
        //取出所有 session 中的處於有效期內的會話
        $tableStr = $nlcore->cfg->db->tables["session"];
        $columnArr = ["id", "apptoken", "devtype", "time"];
        $whereDic = ["userhash" => $userHash];
        $customWhere = "`endtime` > CURRENT_TIME";
        $result = $nlcore->db->select($columnArr, $tableStr, $whereDic, $customWhere);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040209);
        if (!isset($result[2]) || count($result[2]) == 0) return null;
        //取出所有 session
        $sessionarr = $result[2];
        $maxlogin = $nlcore->cfg->app->maxlogin;
        //檢查有沒有超過總數限制
        if (count($sessionarr) >= $maxlogin["all"]) {
            return $this->removeoverflowsession($sessionarr);
        }
        //查session表獲取當前裝置型別
        $resultdev = $nlcore->func->getdeviceinfo($thisdevid);
        if (!isset($resultdev["type"])) $nlcore->msg->stopmsg(2040213);
        $devtype = $resultdev["type"];
        //取會話陣列中用這個裝置型號的資料
        $thisdevsession = [];
        foreach ($sessionarr as $sessioninfo) {
            if ($devtype == $sessioninfo["devtype"]) {
                array_push($thisdevsession, $sessioninfo);
            }
        }
        //檢查有沒有超過額定限制
        if (!isset($maxlogin[$devtype])) $nlcore->msg->stopmsg(2040214);
        if (count($thisdevsession) >= $maxlogin[$devtype]) {
            //刪除本裝置的舊登入狀態
            return $this->removeoverflowsession($thisdevsession);
        }
        return null;
    }
    /**
     * @description: 將較早的裝置登出
     * @param Array sessionarr 使用者已有有效會話的陣列
     * @return Array 被登出裝置的相關裝置資訊，鍵均以 logout_ 為字首
     */
    function removeoverflowsession($sessionarr) {
        global $nlcore;
        //超過總數限制，登出最早的終端。取最小的時間戳對應的id
        $ttime = PHP_INT_MAX;
        $tid = -1;
        foreach ($sessionarr as $apptoken) {
            $ttimen = strtotime($apptoken["time"]);
            if ($ttimen < $ttime) {
                $ttime = $ttimen;
                $tid = $apptoken["id"];
            }
        }
        if ($tid == -1) $nlcore->msg->stopmsg(2040211);
        //取出要刪除會話的裝置型號
        $tableStr = $nlcore->cfg->db->tables["session"];
        $columnArr = ["devid"];
        $whereDic = ["id" => $tid];
        $result = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        if ($result[0] >= 2000000 || !isset($result[2][0]["devid"])) $nlcore->msg->stopmsg(2040212);
        $devid = $result[2][0]["devid"];
        //刪除最舊的會話
        $delwheredic = ["id" => $tid];
        $delresult = $nlcore->db->delete($tableStr, $delwheredic);
        if ($delresult[0] >= 2000000) $nlcore->msg->stopmsg(2040211);
        //查裝置表來返回被登出的裝置型號
        $logoutdevinfo = $nlcore->func->getdeviceinfo($devid);
        return $logoutdevinfo;
    }
}
