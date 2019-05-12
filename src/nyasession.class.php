<?php
class nyasession {
    function sessionstatus() {
        global $nlcore;
        //IP检查和解密客户端提交的信息
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
        $status = $this->sessionstatuscon($jsonarr["token"]);
        if ($status) {
            $statinfo = $nlcore->msg->m(0,1030200);
            $statinfo = array_merge($statinfo,$status);
            $statinfo["timestamp"] = time();
            echo $nlcore->safe->encryptargv($statinfo,$totpsecret);
        } else {
            $nlcore->msg->stopmsg(1030201,$totpsecret);
        }
    }

    function sessionstatuscon($token) {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["session"];
        $columnArr = ["time","endtime"];
        $whereDic = ["token" => $token];
        $customWhere = "`endtime` > CURRENT_TIME";
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic,$customWhere);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040401,$totpsecret);
        if (isset($result[2][0]["endtime"])) return $result[2][0];
        return null;
    }
}
?>
