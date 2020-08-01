<?php
class nyasession {
    // 检查 token 是否有效API
    function sessionstatus() {
        global $nlcore;
        if ($nlcore->cfg->app->sessioncachefirst && isset($_GET["quick"])) {
            $startend = $this->sessionstatuscon($token,false,null);
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
        if ($status) {
            $statinfo = $nlcore->msg->m(0,1030200);
            $statinfo = array_merge($statinfo,$status);
            $statinfo["timestamp"] = time();
            echo $nlcore->safe->encryptargv($statinfo,$totpSecret);
        } else {
            $nlcore->msg->stopmsg(1030201,$totpSecret);
        }
    }
    /**
     * @description: 检查 token 是否有效
     * @param String token 会话令牌
     * @param Bool getuserhash 需要获取用户哈希
     * @param String totpsecret totp加密码
     * @return Null/Array 空(无效) 或 起始-结束 时间数组
     */
    function sessionstatuscon($token,$getuserhash,$totpSecret) {
        $rtoken = $this->redisload($token);
        if ($rtoken) {
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
        return null;
    }
    /**
     * @description: 将 token 存储到 Redis
     * @param String token 会话令牌
     * @param String time 用户有效期起始时间戳
     * @param String endtime 用户有效期结束时间戳
     * @param String userhash 用户唯一哈希
     */
    function redissave($token,$time,$endtime,$userHash) {
        global $nlcore;
        if (!$nlcore->db->initRedis()) return false;
        $key = $nlcore->cfg->db->redis_tables["session"].$token;
        $timelen = $endtime - time();
        if ($timelen < 0) die("endtimeERR".$time); //DEBUG
        if ($timelen > $nlcore->cfg->app->sessioncachemaxtime) $timelen = $nlcore->cfg->app->sessioncachemaxtime;
        $val = json_encode([$time,$endtime,$userHash]);
        $nlcore->db->redis->setex($key,$timelen,$val);
    }
    /**
     * @description: 从 Redis 检查 token 是否有效
     * @param String token 会话令牌
     * @return Null/Array 空(无效) 或 起始-结束 时间数组
     */
    function redisload($token) {
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
            return null;
        }
    }
}
?>
