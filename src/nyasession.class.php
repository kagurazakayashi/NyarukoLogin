<?php
class nyasession {
    public $privateKey = ""; // 用於解密來自客戶端資料的私鑰
    public $publicKey = "";  // 用於加密傳送到客戶端資料的公鑰
    const HEADER_JSON = "Content-Type:application/json;charset=utf-8";
    // 以下变量用于外部访问运行结果
    // 客户端信息相关
    public $argReceived = null;
    public $appSecret = null;
    public $appToken = null;
    public $ipId = null;
    public $appId = null;
    // 登录状态相关
    public $userToken = null;
    public $userSessionInfo = null;
    public $userHash = null;
    // 时间记录相关
    public $timeStamp = null;
    public $timeString = null;

    /**
     * @description: 檢查 token 是否有效
     * @param String token 會話令牌
     * @return Void 無返回值為透過，如果出問題則直接將異常返回給客戶端
     */
    function sessionstatus(string $token): void {
        global $nlcore;
        if ($nlcore->cfg->app->sessioncachefirst && isset($_GET["quick"])) {
            $startend = $this->sessionstatuscon($token, false, "");
            if ($startend) {
                $statinfo = $nlcore->msg->m(0, 1030200);
                $statinfo = array_merge($statinfo, $startend);
                echo $nlcore->sess->encryptargv($statinfo, null);
            } else {
                $nlcore->msg->stopmsg(1030201);
            }
            die();
        }
        $inputInformation = $nlcore->sess->decryptargv("session");
        $argReceived = $inputInformation[0];
        $totpSecret = $inputInformation[1];
        $totpToken = $inputInformation[2];
        $ipid = $inputInformation[3];
        $appid = $inputInformation[4];
        $returnJson = [];
        $usertoken = $argReceived["token"] ?? null;
        if (!$usertoken || !$nlcore->safe->is_rhash64($argReceived["token"])) {
            $nlcore->msg->stopmsg(2040400, $usertoken);
        }
        $status = $this->sessionstatuscon($argReceived["token"], false, $totpSecret);
        if (count($status) > 0) {
            $statinfo = $nlcore->msg->m(0, 1030200);
            $statinfo = array_merge($statinfo, $status);
            $statinfo["timestamp"] = time();
            echo $nlcore->sess->encryptargv($statinfo, $totpSecret);
        } else {
            $nlcore->msg->stopmsg(1030201, $totpSecret);
        }
    }
    /**
     * @description: 檢查 token 是否有效
     * @param String token 會話令牌
     * @param Bool getuserhash 需要獲取使用者雜湊
     * @param String totpsecret totp加密碼
     * @return Array 空陣列(無效) 或 起始-結束 時間陣列
     */
    function sessionstatuscon(string $token, bool $getuserhash, string $totpSecret): array {
        $rtoken = $this->redisLoadToken($token);
        if (count($rtoken) > 0) {
            if (!$getuserhash) array_pop($rtoken, "userhash");
            return $rtoken;
        }
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["session"];
        $columnArr = ["time", "endtime", "userhash"];
        $whereDic = ["token" => $token];
        $customWhere = "`endtime` > CURRENT_TIME";
        $result = $nlcore->db->select($columnArr, $tableStr, $whereDic, $customWhere);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040401, $totpSecret);
        if (isset($result[2][0]["endtime"])) {
            $startend = $result[2][0];
            $starttime = strtotime($startend["time"]);
            $endtime = strtotime($startend["endtime"]);
            $userHash = $startend["userhash"];
            $this->redissave($token, $starttime, $endtime, $userHash);
            $returnarr = ["starttime" => $starttime, "endtime" => $endtime];
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
    function redissave(string $token, int $time, int $endtime, string $userHash): void {
        global $nlcore;
        if (!$nlcore->db->initRedis()) return;
        $key = $nlcore->cfg->db->redis_tables["session"] . $token;
        $timelen = $endtime - time();
        if ($timelen < 0) die("endtimeERR" . $time); //DEBUG
        if ($timelen > $nlcore->cfg->app->sessioncachemaxtime) $timelen = $nlcore->cfg->app->sessioncachemaxtime;
        $val = json_encode([$time, $endtime, $userHash]);
        $nlcore->db->redis->setex($key, $timelen, $val);
    }
    /**
     * @description: 從 Redis 檢查 token 是否有效
     * @param String token 會話令牌
     * @return Array 空陣列(無效) 或 起始-結束 時間陣列
     */
    function redisLoadToken(string $token): array {
        global $nlcore;
        if (!$nlcore->db->initRedis()) return false;
        $key = $nlcore->cfg->db->redis_tables["session"] . $token;
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
    /**
     * @description: ☆資料接收☆ 解析變體、base64解碼、解密、解析 JSON 到陣列
     * GET/POST: 見 WiKi : 加密通訊處理流程.md
     * @param String module 功能名稱（$conf->limittime），提供此項將覆蓋下面兩項
     * @param Int interval 在多少秒內
     * @param Int times 允許請求多少次
     * @param Bool onlyCheckIP 只檢查 IP 是否合法，不進行資料解析
     */
    function decryptargv(string $module = "", int $interval = PHP_INT_MAX, int $times = PHP_INT_MAX, bool $onlyCheckIP = false): void {
        global $nlcore;
        // 檢查 IP 訪問頻率
        if (strlen($module) > 0) {
            $result = $nlcore->safe->frequencylimitation($module, $interval, $times);
            if ($result[0] >= 2000000) $nlcore->msg->stopmsg($result[0]);
        }
        // 記錄到除錯日誌檔案（如果啟用）
        $argvs = $nlcore->safe->getarg();
        if ($argvs) {
            $nlcore->safe->log($_SERVER['REQUEST_METHOD'], $argvs);
        } else {
            $nlcore->safe->log($_SERVER['REQUEST_METHOD'], ["[NULL!]" . count($argvs)]);
        }
        // 檢查資料是否超過指定長度
        $jsonlen = ($_SERVER['REQUEST_METHOD'] == "GET") ? $nlcore->cfg->app->maxlen_get : $nlcore->cfg->app->maxlen_post;
        $arglen = strlen(implode("", $argvs));
        if ($arglen > $jsonlen) $nlcore->msg->stopmsg(2020414, $arglen);
        // 檢查 IP 是否被封禁
        $stime = $nlcore->safe->getdatetime();
        $this->timeStamp = $stime[0];
        $this->timeString = $stime[1];
        $time = $stime[0];
        $result = $nlcore->safe->chkip($stime[0]);
        $stime = $stime[1];
        if ($result[0] != 0) $nlcore->msg->stopmsg($result[0]);
        $ipid = $result[1];
        $this->ipId = $ipid;
        $argReceived = null;
        $secret = null;
        if ($onlyCheckIP) return;
        // 檢查客戶端提交是否應用了加密功能
        $isEncrypt = false;
        $isDefaultKey = (strcmp($module, "encryption") == 0) ? true : false;
        $argks = array_keys($argvs);
        $apptoken = null;
        $encryptedJson = null;
        $isDefaultKey = ($isDefaultKey && count($argks) == 1 && strlen($argks[0]) == 1);
        if (count($argks) == 1 && (strlen($argks[0]) == 64 || $isDefaultKey)) {
            // 客戶端提交了加密資料( &token=json )
            $apptoken = $argks[0];
            $encryptedJson = $argvs[$apptoken];
            $isEncrypt = true;
        } else if (!$isDefaultKey && $nlcore->cfg->app->alwayencrypt) {
            // 要求加密但沒有加密则报错，预共享密钥不受此限制
            $nlcore->msg->stopmsg(2020415);
        } else {
            // 客戶端沒有加密
            $apptoken = $argvs["apptoken"] ?? "";
            $isEncrypt = false;
        }
        if (!($isDefaultKey && strcmp($apptoken, "d") == 0) && !$nlcore->safe->is_rhash64($apptoken)) { // 檢查應用令牌格式
            $nlcore->msg->stopmsg(2020417);
        }
        if ($isEncrypt) { // 已加密，需要解密
            // 進行解密，快取 publicKey 和 privateKey
            $argReceived = $nlcore->sess->decryptRsaMode($encryptedJson, $apptoken);
            if ($argReceived) {
                $nlcore->safe->log("DECODE", $argReceived);
            } else {
                $nlcore->safe->log("DECODE", ["[ERROR!]"]);
            }
        } else { // 未加密
            $argReceived = $argvs;
            unset($argReceived["apptoken"]);
        }
        if (!$argReceived || count($argReceived) == 0) $nlcore->msg->stopmsg(2020400);
        // 檢查 API 版本是否一致
        if (!isset($argReceived["apiver"]) || intval($argReceived["apiver"]) != 2) $nlcore->msg->stopmsg(2020412);
        // 如果提供了 appkey ，則檢查 APP 是否有效，並查詢 appid
        $appid = null;
        if (isset($argReceived["appkey"])) {
            if (!$nlcore->safe->isNumberOrEnglishChar($argReceived["appkey"], 64, 64)) $nlcore->msg->stopmsg(2020401);
            $appid = $nlcore->safe->chkAppKey($argReceived["appkey"]);
            if ($appid == null) $nlcore->msg->stopmsg(2020401);
        }
        if (!$isEncrypt) {
            $this->privateKey = "";
            $this->publicKey = "";
        }
        $this->argReceived = $argReceived;
        $this->appSecret = $secret;
        $this->appToken = $apptoken;
        $this->appId = $appid;
        return;
    }
    /**
     * @description: 使用 RSA 方式進行解密
     * @param String encryptedJson 客戶端提交的加密資料
     * @param String apptoken 應用令牌，空字串則嘗試讀取預共享金鑰對
     * @return Array [string:string] 客戶端提交的資料（解密後）
     */
    function decryptRsaMode(string $encryptedJson, string $apptoken) {
        global $nlcore;
        $isDefaultKey = (strlen($apptoken) == 1 && strcmp($apptoken, "d") == 0) ? true : false;
        if ($isDefaultKey) {
            // 載入預共享金鑰對
            if ($nlcore->cfg->enc->privateKeyPassword) {
                $this->privateKey = $nlcore->safe::PKBE_PRIE_B . $nlcore->cfg->enc->defaultPrivateKey . $nlcore->safe::PKBE_PRIE_E;
            } else {
                $this->privateKey = $nlcore->safe::PKBE_PRI_B . $nlcore->cfg->enc->defaultPrivateKey . $nlcore->safe::PKBE_PRI_E;
            }
            $this->publicKey = $nlcore->safe::PKBE_PUB_B . $nlcore->cfg->enc->defaultPublicKey . $nlcore->safe::PKBE_PUB_E;
        } else {
            $redisTimeout = $nlcore->cfg->enc->redisCacheTimeout;
            // 查詢 apptoken 對應的 公鑰 和 私鑰
            // 先嚐試從 Redis 中載入
            $redisName = $nlcore->cfg->db->redis_tables["rsa"];
            $redisKey = $redisName . $apptoken;
            if ($redisTimeout != 0 && $nlcore->db->initRedis()) {
                if ($nlcore->db->redis->exists($redisKey)) {
                    $redisVal = $nlcore->db->redis->get($redisKey);
                    $keyArr = explode("|", $redisVal);
                    $this->publicKey = $keyArr[0];
                    $this->privateKey = $keyArr[1];
                }
            }
            // 校驗是否為私鑰和公鑰
            $nlcore->safe->autoRsaAddTag();
            if (!$nlcore->safe->autoCheck()) {
                // 不能從 Redis 中載入，從 MySQL 中載入
                $datadic = ["apptoken" => $apptoken];
                $tableStr = $nlcore->cfg->db->tables["encryption"];
                $result = $nlcore->db->select(["secret"], $tableStr, $datadic);
                // 空或查詢失敗都視為不正確
                if (!$result || $result[0] != 1010000 || !isset($result[2][0])) {
                    $nlcore->msg->stopmsg(2020409, $apptoken ?? "no token");
                }
                $rdata = $result[2][0];
                if (!isset($rdata["private"]) || !isset($rdata["public"])) {
                    $nlcore->msg->stopmsg(2020421, $apptoken);
                }
                // 再次校驗是否為私鑰和公鑰，這次還有問題則錯誤
                $nlcore->safe->autoRsaAddTag();
                if (!$nlcore->safe->autoCheck()) {
                    $nlcore->msg->stopmsg(2020421, strval(strlen($this->publicKey)) . "-" . strval(strlen($this->privateKey)));
                }
                if ($redisTimeout != 0) {
                    // 重新建立 Redis 快取
                    $redisVal = $datadic["public"] . "|" . $datadic["private"];
                    if ($redisTimeout < 0) {
                        $nlcore->db->redis->set($redisKey, $redisVal);
                    } else {
                        $nlcore->db->redis->setex($redisKey, $redisTimeout, $redisVal);
                    }
                }
            }
        }

        $decryptDataFull = "";
        $subEncryptedJsonArr = explode(",", $encryptedJson);
        for ($i = 0; $i < count($subEncryptedJsonArr); $i++) {
            $encryptedData = null;
            $subEncryptedJson = $subEncryptedJsonArr[$i];
            // 解密資料，自動判斷是否是變種 base64
            if ($nlcore->safe->isbase64($subEncryptedJson, true)) {
                $encryptedData = $nlcore->safe->urlb64decode($subEncryptedJson);
                if (strlen($encryptedData) == 0) {
                    $nlcore->msg->stopmsg(2020410, "2");
                }
            } else if ($nlcore->safe->isbase64($subEncryptedJson, false)) {
                $encryptedData = base64_decode($subEncryptedJson);
                if ($encryptedData === FALSE || strlen($encryptedData) == 0) {
                    $nlcore->msg->stopmsg(2020410, "1");
                }
            } else {
                $nlcore->msg->stopmsg(2020410, "0");
            }
            // 開始解密


            $decryptData = $nlcore->safe->rsaDecryptChunk($encryptedData);
            // die($decryptData);
            // $decryptData = json_decode($decryptData, true);
            // die($decryptData);
            if ($decryptData === FALSE) {
                $nlcore->msg->stopmsg(2020410);
            }
            $decryptDataFull .= $decryptData;
        }
        $decryptDataFull = json_decode($decryptDataFull, true);

        return $decryptDataFull;
    }
    /**
     * @description: [已棄用] 使用 TOTP + XXTea 方式進行解密
     * @param String argvs 客戶端提交的加密引數
     * @return Array [string:string] 客戶端提交的引數（解密後）
     */
    function decryptTotpXXteaMode($argvs) {
        global $nlcore;
        // 查詢 apptoken 對應的 secret
        $datadic = ["apptoken" => $argvs["t"]];
        $tableStr = $nlcore->cfg->db->tables["encryption"];
        $result = $nlcore->db->select(["secret"], $tableStr, $datadic);
        // 空或查詢失敗都視為不正確
        if (!$result || $result[0] != 1010000 || !isset($result[2][0]["secret"])) $nlcore->msg->stopmsg(2020409, $argvs["t"]);
        $secret = $result[2][0]["secret"];
        // 使用 Secret 生成 TOTP 數字
        $ga = new PHPGangsta_GoogleAuthenticator();
        $gaisok = false;
        $timestamp = isset($argvs["s"]) ? $argvs["s"] : time();
        if ($timestamp > 1000000000000) {
            $timestamp = intval($timestamp / 1000);
        }
        $totptimeslice = $nlcore->cfg->app->totptimeslice;
        $tryi = 0;
        for ($i = -$totptimeslice; $i <= $totptimeslice; ++$i) {
            $tryi++;
            $ntimestamp = $timestamp + ($i * 30);
            $timeSlice = floor($ntimestamp / 30);
            $numcode = intval($ga->getCode($secret, $timeSlice));
            if ($numcode < 0 || $numcode > 999999) {
                $nlcore->msg->stopmsg(2020418, strval($numcode));
            } else if ($numcode < 100000) {
                $numcode = str_pad($numcode, 6, "0", STR_PAD_LEFT);
            }
            $numcode = strval($numcode) + $nlcore->cfg->app->totpcompensate;
            // MD5
            $numcode5 = md5($secret . $numcode);
            // 解密 Base64
            $xxteadata = $nlcore->safe->urlb64decode($argvs["j"]);
            // 使用 TOTP 數字解密
            $decrypt_data = xxtea_decrypt($xxteadata, $numcode5);
            if (strlen($decrypt_data) > 0) {
                $gaisok = true;
                break;
            }
        }
        if (!$gaisok) {
            $failinfo = strval($timestamp) . '-' . strval(time()) . ',' . strval($tryi) . ',' . strval($numcode);
            $nlcore->msg->stopmsg(2020411, $failinfo);
        }
        // die(json_encode($decrypt_data));
        $argReceived = json_decode($decrypt_data, true);
        return $argReceived;
    }
    /**
     * @description: ☆資料傳送☆ 從陣列建立 JSON、加密、base64 編碼、變體
     * @param String dataarray 要返回到客戶端的內容字典
     * @return String 如果加密啟用，回傳加密後的資訊；否則返回明文 JSON
     */
    function encryptargv($dataarray) {
        global $nlcore;
        //加时间戳
        if (!isset($dataarray["timestamp"])) $dataarray["timestamp"] = time();
        //转换为json
        $nlcore->safe->log("RETURN", $dataarray);
        $json = json_encode($dataarray);
        $json = $this->encryptRsaMode($json);
        return $json;
    }
    /**
     * @description: 使用 RSA 方式進行加密
     * @param String json 要加密的 JSON
     * @return String 加密後的資訊（JSON變種）
     */
    function encryptRsaMode(string $json) {
        global $nlcore;
        if (strlen($this->privateKey) < 4 || strlen($this->publicKey) < 4) {
            return $json;
        }
        $encryptJson = $nlcore->safe->rsaEncryptChunk($json);
        $returndata = $nlcore->safe->urlb64encode($encryptJson);
        return $returndata;
    }
    /**
     * @description: [已棄用] 使用 TOTP + XXTea 方式進行加密
     * @param String json 要加密的 JSON
     * @param String secret totp加密碼（可選，不加不進行加密）
     * @return String 加密後的資訊（JSON變種）
     */
    function encryptTotpXXteaMode($json, $secret = null) {
        if ($secret) {
            global $nlcore;
            //使用secret生成totp数字
            $ga = new PHPGangsta_GoogleAuthenticator();
            $numcode = $ga->getCode($secret) + $nlcore->cfg->app->totpcompensate;
            //MD5
            $numcode = md5($secret . $numcode);
            //使用totp数字加密
            $json = xxtea_encrypt($json, $numcode);
            $returndata = $nlcore->safe->urlb64encode($json);
            return $returndata;
        }
        return $json;
    }
    /**
     * @description: 驗證該使用者已登入並取得資訊，如果未登入直接返回錯誤資訊到客戶端。
     * @param Array inputInformation 由 decryptargv 函式返回的結果陣列
     * @return Array [會話令牌,使用者會話資訊,使用者雜湊]
     */
    function userLogged(array $inputinformation): array {
        global $nlcore;
        $argReceived = $inputinformation[0];
        $totpSecret = $inputinformation[1];
        $userToken = $argReceived["token"];
        if (!$nlcore->safe->is_rhash64($userToken)) $nlcore->msg->stopmsg(2040402, $totpSecret, "T-" . $userToken);
        $userSessionInfo = $nlcore->sess->sessionstatuscon($userToken, true, $totpSecret);
        if (!$userSessionInfo) $nlcore->msg->stopmsg(2040400, $totpSecret, "T-" . $userToken);
        $userHash = $userSessionInfo["userhash"];
        return [$userToken, $userSessionInfo, $userHash];
    }
    /**
     * @description: 将准备好的数组打包成 JSON 返回给客户端，并停止当前 PHP 程序
     * @param  returnClientData 数据数组
     */
    function returnToClient($returnClientData) {
        header('Content-Type:application/json;charset=utf-8');
        exit(json_encode($returnClientData));
    }
    function __destruct() {
        $this->publicKey = null;
        unset($this->publicKey);
        $this->privateKey = null;
        unset($this->privateKey);
    }
}
