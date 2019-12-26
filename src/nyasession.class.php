<?php
class nyasession {
    function sessionstatus() {
        global $nlcore;
        if ($nlcore->cfg->app->sessioncachefirst && isset($_GET["quick"])) {
            $startend = $this->sessionstatuscon($token,null);
            if ($startend) {
                $statinfo = $nlcore->msg->m(0,1030200);
                $statinfo = array_merge($statinfo,$startend);
                echo $nlcore->safe->encryptargv($statinfo,null);
            } else {
                $nlcore->msg->stopmsg(1030201,null);
            }
            die();
        }
        $jsonarrTotpsecret = $nlcore->safe->decryptargv("session");
        $jsonarr = $jsonarrTotpsecret[0];
        $totpsecret = $jsonarrTotpsecret[1];
        $totptoken = $jsonarrTotpsecret[2];
        $ipid = $jsonarrTotpsecret[3];
        $appid = $jsonarrTotpsecret[4];
        $returnjson = [];
        if (!isset($jsonarr["token"]) || !$nlcore->safe->is_md6($jsonarr["token"])) {
            $nlcore->msg->stopmsg(2040400,$totpsecret);
        }
        $status = $this->sessionstatuscon($jsonarr["token"],$totpsecret);
        if ($status) {
            $statinfo = $nlcore->msg->m(0,1030200);
            $statinfo = array_merge($statinfo,$status);
            $statinfo["timestamp"] = time();
            echo $nlcore->safe->encryptargv($statinfo,$totpsecret);
        } else {
            $nlcore->msg->stopmsg(1030201,$totpsecret);
        }
    }
    /**
     * @description: 检查 token 是否有效
     * @param String token 会话令牌
     * @return Null/Array 空(无效) 或 起始-结束 时间数组
     */
    function sessionstatuscon($token,$totpsecret) {
        $rtoken = $this->redisload($token);
        if ($rtoken) return $rtoken;
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["session"];
        $columnArr = ["time","endtime"];
        $whereDic = ["token" => $token];
        $customWhere = "`endtime` > CURRENT_TIME";
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic,$customWhere);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040401,$totpsecret);
        if (isset($result[2][0]["endtime"])) {
            $startend = $result[2][0];
            $starttime = strtotime($startend["time"]);
            $endtime = strtotime($startend["endtime"]);
            $this->redissave($token,$starttime,$endtime);
            return ["starttime"=>$starttime,"endtime"=>$endtime];
        }
        return null;
    }
    /**
     * @description: 将 token 存储到 Redis
     * @param String token 会话令牌
     * @param String time 用户有效期起始时间戳
     * @param String endtime 用户有效期结束时间戳
     */
    function redissave($token,$time,$endtime) {
        global $nlcore;
        if (!$nlcore->db->initRedis()) return false;
        $key = $nlcore->cfg->db->redis_tables["session"].$token;
        $timelen = $endtime - time();
        if ($timelen < 0) die("endtimeERR".$time); //DEBUG
        if ($timelen > $nlcore->cfg->app->sessioncachemaxtime) $timelen = $nlcore->cfg->app->sessioncachemaxtime;
        $val = json_encode([$time,$endtime]);
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
            return json_decode($val);
        } else {
            return null;
        }
    }
}
?>
