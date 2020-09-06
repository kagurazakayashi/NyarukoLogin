<?php
class nyaencryption {
    /**
     * @description: 建立新的裝置金鑰
     * @param Array argv 客戶端提供的資訊
     * @param Array ipinfo 獲取的客戶端資訊
     * @param Int timestamp 時間戳
     */
    function newDeviceKey(array $argv, array $ipinfo) {
        global $nlcore;
        $time = $ipinfo[0];
        $stime = $ipinfo[1];
        $ipid = $ipinfo[2];
        $appKey = isset($argv["appkey"]) ? $argv["appkey"] : $nlcore->msg->stopmsg(2000101, "", "", true);
        // 檢查應用名稱和金鑰
        if (!$nlcore->safe->is_rhash64($appKey)) $nlcore->msg->stopmsg(2020417);
        $datadic = ["appkey" => $appKey];
        $result = $nlcore->db->scount($nlcore->cfg->db->tables["app"], $datadic);
        if ($result[0] >= 2000000 || $result[2][0]["count(*)"] == 0) $nlcore->msg->stopmsg(2020401);
        // 檢查APP是否已經註冊 appKey
        $appid = $nlcore->safe->chkAppKey($appKey);
        if ($appid == null) $nlcore->msg->stopmsg(2020401);
        // 獲取客戶端的公鑰
        $clientPublicKey = null;
        $enableEncrypt = $nlcore->cfg->enc->enable;
        if ($enableEncrypt) {
            $clientPublicKey = $argv["publickey"] ?? $nlcore->msg->stopmsg(2020420);
            if (strcmp(substr($clientPublicKey, 0, 5), "-----") != 0) {
                $clientPublicKey = base64_decode(str_replace(['-', '_'], ['+', '/'], $clientPublicKey));
            }
            $clientPublicKey = $nlcore->safe->convertRsaHeaderInformation($clientPublicKey);
            $clientPublicKeyType = $nlcore->safe->isRsaKey($clientPublicKey, true);
            if ($clientPublicKeyType != 1) $nlcore->msg->stopmsg(2020420, "", strval($clientPublicKeyType));
        }
        // 建立 apptoken
        $apptoken = $nlcore->safe->randhash();
        // 檢查 session_totp 表
        $datadic = ["apptoken" => $apptoken];
        // 如果 apptoken 已存在則刪除
        $table = $nlcore->cfg->db->tables["encryption"];
        $result = $nlcore->db->delete($table, $datadic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2020405);
        // 獲取裝置提供的資訊，寫入 device 表
        $datadic = array();
        if (isset($argv["devtype"])) {
            $datadic["type"] = strtolower($nlcore->safe->retainletternumber($argv["devtype"]));
            $typeenum = ['phone', 'phone_emu', 'pad', 'pad_emu', 'pc', 'web', 'debug', 'other'];
            if ($datadic["type"] && !in_array($datadic["type"], $typeenum)) $nlcore->msg->stopmsg(2000104);
        }
        if (isset($argv["devos"])) {
            $datadic["os"] = strtolower($nlcore->safe->retainletternumber($argv["devos"]));
            $osenum = ['ios', 'android', 'windows', 'linux', 'harmony', 'emu', 'other'];
            if ($datadic["os"] && !in_array($datadic["os"], $osenum)) $nlcore->msg->stopmsg(2000104);
        }
        if (isset($argv["devdevice"])) $datadic["device"] = $nlcore->safe->retainletternumber($argv["devdevice"]);
        if (isset($argv["devosver"])) $datadic["osver"] = $nlcore->safe->retainletternumber($argv["devosver"]);
        if (isset($argv["devinfo"])) $datadic["info"] = $nlcore->safe->retainletternumber($argv["devinfo"]);
        $deviceid = null;
        if (!$nlcore->safe->allnull($datadic)) {
            // 檢查條目是否存在
            $result = $nlcore->db->select(["id"], $nlcore->cfg->db->tables["device"], $datadic);
            if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2020416);
            if (isset($result[2])) {
                $resultarr = $result[2];
                if (count($resultarr) > 0 && isset($resultarr[0]["id"])) $deviceid = $resultarr[0]["id"];
            }
        }
        if (!$deviceid) {
            // 如果不存在
            $result = $nlcore->db->insert($nlcore->cfg->db->tables["device"], $datadic);
            if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2020416);
            $deviceid = $result[1];
        }
        // 建立新的金鑰對和 secret
        if ($enableEncrypt) {
            $nlcore->safe->rsaCreateKey();
            $secret = $nlcore->safe->md6($nlcore->sess->privateKey . $clientPublicKey);
        } else {
            $secret = $nlcore->safe->randhash($time);
        }
        // 寫入 session_totp 表
        $datadic = array(
            "secret" => $secret,
            "apptoken" => $apptoken,
            "ipid" => $ipid,
            "appid" => $appid,
            "devid" => $deviceid,
            "time" => $stime
        );
        if ($enableEncrypt) {
            $datadic["private"] = $nlcore->safe->rsaRmTag($nlcore->sess->privateKey);
            // $datadic["private"] = $nlcore->safe->rsaRmBCode($datadic["private"]);
            $datadic["public"] = $nlcore->safe->rsaRmTag($clientPublicKey);
            // $datadic["public"] = $nlcore->safe->rsaRmBCode($datadic["public"]);
        }
        $result = $nlcore->db->insert($nlcore->cfg->db->tables["encryption"], $datadic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2020406);
        // 將執行結果返回給客戶端
        header('Content-Type:application/json;charset=utf-8');
        $returnClientData = [
            "code" => 1000000,
            "time" => $stime,
            "timestamp" => $time,
            "timezone" => date_default_timezone_get(),
            "encrypt" => strval($enableEncrypt)
        ];
        if ($enableEncrypt) {
            $returnClientData["publickey"] = $nlcore->sess->publicKey;
            $returnClientData["apptoken"] = $apptoken;
            $redisTimeout = $nlcore->cfg->enc->redisCacheTimeout;
            if ($redisTimeout != 0 && $nlcore->db->initRedis()) {
                $redisName = $nlcore->cfg->db->redis_tables["rsa"];
                $redisKey = $redisName . $apptoken;
                $redisVal = $datadic["public"] . "|" . $datadic["private"];
                if ($redisTimeout < 0) {
                    $nlcore->db->redis->set($redisKey, $redisVal);
                } else {
                    $nlcore->db->redis->setex($redisKey, $redisTimeout, $redisVal);
                }
            }
        }
        $nlcore->sess->publicKey = $clientPublicKey;
        echo json_encode($returnClientData);
    }
}
