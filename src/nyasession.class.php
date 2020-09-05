<?php
class nyasession {
    /**
     * @description: 檢查 token 是否有效
     * @param String token 會話令牌
     * @return Void 無返回值為透過，如果出問題則直接將異常返回給客戶端
     */
    function sessionstatus(string $token):void {
        global $nlcore;
        if ($nlcore->cfg->app->sessioncachefirst && isset($_GET["quick"])) {
            $startend = $this->sessionstatuscon($token,false,"");
            if ($startend) {
                $statinfo = $nlcore->msg->m(0,1030200);
                $statinfo = array_merge($statinfo,$startend);
                echo $nlcore->safe->encryptargv($statinfo,null);
            } else {
                $nlcore->msg->stopmsg(1030201,null);
            }
            die();
        }
        $inputInformation = $nlcore->safe->decryptargv("session");
        $argReceived = $inputInformation[0];
        $totpSecret = $inputInformation[1];
        $totpToken = $inputInformation[2];
        $ipid = $inputInformation[3];
        $appid = $inputInformation[4];
        $returnJson = [];
        $usertoken = $argReceived["token"] ?? null;
        if (!$usertoken || !$nlcore->safe->is_rhash64($argReceived["token"])) {
            $nlcore->msg->stopmsg(2040400,$totpSecret,$usertoken);
        }
        $status = $this->sessionstatuscon($argReceived["token"],false,$totpSecret);
        if (count($status) > 0) {
            $statinfo = $nlcore->msg->m(0,1030200);
            $statinfo = array_merge($statinfo,$status);
            $statinfo["timestamp"] = time();
            echo $nlcore->safe->encryptargv($statinfo,$totpSecret);
        } else {
            $nlcore->msg->stopmsg(1030201,$totpSecret);
        }
    }
    /**
     * @description: 檢查 token 是否有效
     * @param String token 會話令牌
     * @param Bool getuserhash 需要獲取使用者雜湊
     * @param String totpsecret totp加密碼
     * @return Array 空陣列(無效) 或 起始-結束 時間陣列
     */
    function sessionstatuscon(string $token,bool $getuserhash,string $totpSecret):array {
        $rtoken = $this->redisLoadToken($token);
        if (count($rtoken) > 0) {
            if (!$getuserhash) array_pop($rtoken,"userhash");
            return $rtoken;
        }
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["session"];
        $columnArr = ["time","endtime","userhash"];
        $whereDic = ["token" => $token];
        $customWhere = "`endtime` > CURRENT_TIME";
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic,$customWhere);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040401,$totpSecret);
        if (isset($result[2][0]["endtime"])) {
            $startend = $result[2][0];
            $starttime = strtotime($startend["time"]);
            $endtime = strtotime($startend["endtime"]);
            $userHash = $startend["userhash"];
            $this->redissave($token,$starttime,$endtime,$userHash);
            $returnarr = ["starttime"=>$starttime,"endtime"=>$endtime];
            if ($getuserhash) $returnarr["userhash"] = $startend["userhash"];
            return $returnarr;
        }
        return [];
    }
    /**
     * @description: 將 token 儲存到 Redis
     * @param String token 會話令牌
     * @param Int time 使用者有效期起始時間戳
     * @param Int endtime 使用者有效期結束時間戳
     * @param String userhash 使用者唯一雜湊
     */
    function redissave(string $token,int $time,int $endtime,string $userHash):void {
        global $nlcore;
        if (!$nlcore->db->initRedis()) return;
        $key = $nlcore->cfg->db->redis_tables["session"].$token;
        $timelen = $endtime - time();
        if ($timelen < 0) die("endtimeERR".$time); //DEBUG
        if ($timelen > $nlcore->cfg->app->sessioncachemaxtime) $timelen = $nlcore->cfg->app->sessioncachemaxtime;
        $val = json_encode([$time,$endtime,$userHash]);
        $nlcore->db->redis->setex($key,$timelen,$val);
    }
    /**
     * @description: 從 Redis 檢查 token 是否有效
     * @param String token 會話令牌
     * @return Array 空陣列(無效) 或 起始-結束 時間陣列
     */
    function redisLoadToken(string $token):array {
        global $nlcore;
        if (!$nlcore->db->initRedis()) return false;
        $key = $nlcore->cfg->db->redis_tables["session"].$token;
        $val = $nlcore->db->redis->get($key);
        if ($val) {
            $val = json_decode($val);
            return [
                "time" => $val[0],
                "endtime" => $val[1],
                "userhash" => $val[2]
            ];
        } else {
            return [];
        }
    }
}
