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
     * @description: 從 SQL 檢查 token 是否有效（從客戶端獲取 token）
     * @param  string token 會話令牌
     * @return void   無返回值為透過，如果出問題則直接將異常返回給客戶端
     */
    function sessionstatus(string $token): void {
        global $nlcore;
        if ($nlcore->cfg->app->sessioncachefirst && isset($_GET["quick"])) {
            $startend = $this->sessionstatuscon($token, false, "");
            if ($startend) {
                $statinfo = $nlcore->msg->m(0, 1030200);
                $statinfo = array_merge($statinfo, $startend);
                echo $this->encryptargv($statinfo, null);
            } else {
                $nlcore->msg->stopmsg(1030201);
            }
            die();
        }
        $this->decryptargv("session");
        $argReceived = $this->argReceived;
        $usertoken = $argReceived["token"] ?? null;
        if (!$usertoken || !$nlcore->safe->is_rhash64($argReceived["token"])) {
            $nlcore->msg->stopmsg(2040400, $usertoken);
        }
        $type = isset($argReceived["type"]) ? intval($argReceived["type"]) : 0;
        $status = $this->sessionstatuscon($argReceived["token"], false, $type);
        if (count($status) > 0) {
            $statinfo = $nlcore->msg->m(0, 1030200);
            $statinfo = array_merge($statinfo, $status);
            $statinfo["timestamp"] = time();
            echo $this->encryptargv($statinfo);
        } else {
            $nlcore->msg->stopmsg(1030201);
        }
    }

    /**
     * @description: 從 SQL 檢查 token 是否有效
     * @param  string token       會話令牌
     * @param  bool   getuserhash 需要獲取使用者雜湊
     * @param  int    type        驗證碼型別 0標準 1預分配
     * @return array  [] (無效) 或 [起始,結束] 時間
     */
    function sessionstatuscon(string $token, bool $getuserhash, int $type = 0): array {
        $rtoken = $this->redisLoadToken($token);
        if (count($rtoken) > 0) {
            if (!$getuserhash && isset($rtoken["userhash"])) unset($rtoken["userhash"]);
            return $rtoken;
        }
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["session"];
        $columnArr = ["time", "endtime", "userhash", "type"];
        $whereDic = [
            "token" => $token,
            "type" => $type
        ];
        $customWhere = "`endtime` > CURRENT_TIME";
        $result = $nlcore->db->select($columnArr, $tableStr, $whereDic, $customWhere);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040401);
        if (isset($result[2][0]["endtime"])) {
            $startend = $result[2][0];
            $starttime = strtotime($startend["time"]);
            $endtime = strtotime($startend["endtime"]);
            $type = intval($startend["type"]);
            $userHash = $startend["userhash"];
            $this->redissave($token, $starttime, $endtime, $userHash, $type);
            $returnarr = [
                "starttime" => $starttime,
                "endtime" => $endtime
            ];
            if ($getuserhash) $returnarr["userhash"] = $startend["userhash"];
            return $returnarr;
        }
        return [];
    }
    /**
     * @description: 將 token 儲存到 Redis
     * @param  string token    會話令牌
     * @param  int    time     使用者有效期起始時間戳
     * @param  int    endtime  使用者有效期結束時間戳
     * @param  string userhash 使用者唯一雜湊
     * @return bool   是否允許使用 Redis 儲存
     */
    function redissave(string $token, int $time, int $endtime, string $userHash, int $type = 0): bool {
        global $nlcore;
        if (!$nlcore->db->initRedis()) return false;
        $key = $nlcore->cfg->db->redis_tables["session"] . $token;
        $timelen = $endtime - time();
        // if ($timelen < 0) die("endtimeERR" . $time); //DEBUG
        if ($type == 1 && $timelen > $nlcore->cfg->verify->pretokentimeout) {
            $timelen = $nlcore->cfg->verify->pretokentimeout;
        } else if ($timelen > $nlcore->cfg->verify->tokentimeout) {
            $timelen = $nlcore->cfg->verify->tokentimeout;
        }
        $val = json_encode([$time, $endtime, $userHash, $type]);
        $nlcore->db->redis->setex($key, $timelen, $val);
        return true;
    }
    /**
     * @description: 從 Redis 檢查 token 是否有效
     * @param  string token 會話令牌
     * @return array  [] (無效) 或 [起始,結束] 時間
     */
    function redisLoadToken(string $token): array {
        global $nlcore;
        if (!$nlcore->db->initRedis()) return [];
        $key = $nlcore->cfg->db->redis_tables["session"] . $token;
        $val = $nlcore->db->redis->get($key);
        if ($val) {
            $val = json_decode($val);
            return [
                "time" => $val[0],
                "endtime" => $val[1],
                "userhash" => $val[2],
                "type" => $val[3] ?? 0
            ];
        } else {
            return [];
        }
    }

    /**
     * @description: 建立新的預分配令牌
     * @return array [新的預分配令牌,起始時間,結束時間]
     */
    function preTokenNew(): array {
        global $nlcore;
        $appToken = $nlcore->sess->appToken;
        $datetime = $nlcore->safe->getdatetime();
        $timestamp = $datetime[0];
        $timestr = $datetime[1];
        $token = $nlcore->safe->rhash64(strval(rand(1000, 9999)) . $timestamp);
        $tokentimeout = $nlcore->cfg->verify->pretokentimeout;
        $tokentimeout += $timestamp;
        $tokentimeoutstr = $nlcore->safe->getdatetime(null, $tokentimeout)[1];
        $deviceid = $nlcore->func->getdeviceid($appToken);
        $ua = (isset($_SERVER["HTTP_USER_AGENT"]) && strlen($_SERVER["HTTP_USER_AGENT"]) > 0) ? $ua = $_SERVER["HTTP_USER_AGENT"] : null;
        $devicetype = $nlcore->func->getdeviceinfo($deviceid, true);
        // 準備資料
        $insertDic = [
            "token" => $token,
            "type" => 1,
            "apptoken" => $appToken,
            "ipid" => $nlcore->sess->ipId,
            "devid" => $deviceid,
            "devtype" => $devicetype,
            "time" => $timestr,
            "endtime" => $tokentimeoutstr
        ];
        if ($ua) $insertDic["ua"] = $ua;
        // 寫入 Redis
        if (!$this->redissave($token, strtotime($timestr), strtotime($tokentimeoutstr), '', 1)) {
            // 如果 寫入 Redis 失敗，寫入 MySQL
            $tableStr = $nlcore->cfg->db->tables["session"];
            $result = $nlcore->db->insert($tableStr, $insertDic);
            if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040113);
        }
        return [$token,$timestr,$tokentimeoutstr];
    }

    /**
     * @description: 驗證預分配令牌
     * @param  string token 預分配令牌
     * @return array  [] (無效) 或 [起始時間,結束時間]
     */
    function preTokenVerify(string $token): array {
        global $nlcore;
        if ($nlcore->safe->is_rhash64($token) == false) return [];
        // 從 Redis 驗證
        $rtoken = $this->redisLoadToken($token);
        if (count($rtoken) == 0) {
            // Redis 禁用或找不到，從 MySQL 驗證
            $rtoken = $this->sessionstatuscon($token, false, 1);
            if (count($rtoken) == 0) {
                return [];
            }
        } else if ($rtoken["type"] != 1) {
            // 令牌型別不正確
            return [];
        }
        return $rtoken;
    }

    /**
     * @description: 刪除預分配令牌
     * @param  string token 預分配令牌
     */
    function preTokenRemove(string $token) {
        global $nlcore;
        // 從 Redis 刪除
        $redisKey = 's_' . $token;
        if ($nlcore->db->initRedis() && $nlcore->db->redis->exists($redisKey)) {
            $nlcore->db->redis->del($redisKey);
        }
        // 從 SQL 刪除
        $tableStr = $nlcore->cfg->db->tables["session"];
        $whereDic = ["token" => $token];
        $dbresult = $nlcore->db->delete($tableStr, $whereDic);
        if ($dbresult[0] >= 2000000) $nlcore->msg->stopmsg(2040306);
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
        $stime = $nlcore->safe->getdatetime();
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
        // 如果有檔案上載，也進行記錄
        if (count($_FILES) > 0) {
            $nlcore->safe->log("FILE", $_FILES);
        }
        // 檢查資料是否超過指定長度
        $jsonlen = ($_SERVER['REQUEST_METHOD'] == "GET") ? $nlcore->cfg->app->maxlen_get : $nlcore->cfg->app->maxlen_post;
        $arglen = strlen(implode("", $argvs));
        if ($arglen > $jsonlen) $nlcore->msg->stopmsg(2020414, $arglen);
        // 檢查 IP 是否被封禁
        $this->timeStamp = $stime[0];
        $this->timeString = $stime[1];
        $time = $stime[0];
        $result = $nlcore->safe->chkip($stime[0]);
        $stime = $stime[1];
        if ($result[0] != 0) $nlcore->msg->stopmsg($result[0]);
        $ipid = $result[1];
        $this->ipId = intval($ipid);
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
            $nlcore->msg->stopmsg(2020417, $apptoken);
        }
        if ($isEncrypt) { // 已加密，需要解密
            // 進行解密，快取 publicKey 和 privateKey
            $argReceived = $this->decryptRsaMode($encryptedJson, $apptoken);
            if ($argReceived) $nlcore->safe->log("DECODE", $argReceived);
        } else { // 未加密
            $argReceived = $argvs;
            unset($argReceived["apptoken"]);
        }
        if (!$argReceived || count($argReceived) == 0) $nlcore->msg->stopmsg(2020400);
        // 檢查 API 版本是否一致
        if (!isset($argReceived["apiver"]) || intval($argReceived["apiver"]) != 2) $nlcore->msg->stopmsg(2020412, $argReceived["apiver"] ?? "");
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
    }
    /**
     * @description: 仅获取應用令牌
     * @return String 應用令牌
     */
    function getAppToken(): string {
        global $nlcore;
        $apptoken = '';
        $argvs = count($_POST) > 0 ? $_POST : $_GET;
        $argks = array_keys($argvs);
        if (count($argks) == 1 && (strlen($argks[0]) == 64)) {
            // 客戶端提交了加密資料
            $apptoken = $argks[0];
        } else {
            // 客戶端沒有加密
            $apptoken = $argvs["apptoken"] ?? '';
        }
        if ($nlcore->safe->is_rhash64($apptoken)) {
            return $apptoken;
        } else {
            $nlcore->msg->stopmsg(2020417, $apptoken);
        }
    }

    /**
     * @description: 登出當前使用者，刪除會話令牌
     */
    function logout() {
        global $nlcore;
        $this->logoutUser();
        $returnClientData = $nlcore->msg->m(0, 1020103);
        return $returnClientData;
    }

    /**
     * @description: 登出當前使用者，刪除會話令牌
     * @param String appToken 會話令牌，不傳則試圖獲取當前令牌
     * @param String mode 模式 1從Redis刪除 2從資料庫刪除 3都刪除
     */
    function logoutUser(string $userToken = null, int $mode = 3) {
        global $nlcore;
        if ($userToken == null) {
            if ($this->userToken == null) {
                $nlcore->msg->stopmsg(2040700, 'nil');
            } else {
                $userToken = $this->userToken;
            }
        }
        if ($mode == 1 || $mode == 3) {
            $redisKey = 's_' . $userToken;
            if ($nlcore->cfg->enc->redisCacheTimeout != 0 && $nlcore->db->initRedis() && $nlcore->db->redis->exists($redisKey)) {
                $nlcore->db->redis->del($redisKey);
            }
        }
        if ($mode == 2 || $mode == 3) {
            $tableStr = $nlcore->cfg->db->tables["session"];
            $whereDic = ["token" => $userToken];
            $dbresult = $nlcore->db->delete($tableStr, $whereDic);
            if ($dbresult[0] >= 2000000) $nlcore->msg->stopmsg(2040711);
        }
    }

    /**
     * @description: 登出當前裝置，刪除金鑰對
     * @param String appToken 應用令牌，不传则试图获取当前令牌
     * @param String mode 模式 1從Redis刪除 2從資料庫刪除 3都刪除
     */
    function logoutDevice(string $appToken = null, int $mode = 3, bool $logoutUser = true) {
        global $nlcore;
        if ($appToken == null) {
            if ($this->appToken == null) {
                $nlcore->msg->stopmsg(2040713, 'nil');
            } else {
                $appToken = $this->appToken;
            }
        }
        if ($mode == 1 || $mode == 3) {
            $redisKey = $nlcore->cfg->db->redis_tables["rsa"] . $appToken;
            if ($nlcore->cfg->enc->redisCacheTimeout != 0 && $nlcore->db->initRedis() && $nlcore->db->redis->exists($redisKey)) {
                $nlcore->db->redis->del($redisKey);
            }
        }
        if ($mode == 2 || $mode == 3) {
            $tableStr = $nlcore->cfg->db->tables["encryption"];
            $whereDic = ["apptoken" => $appToken];
            $dbresult = $nlcore->db->delete($tableStr, $whereDic);
            if ($dbresult[0] >= 2000000) $nlcore->msg->stopmsg(2040713);
            // 若從資料庫刪除金鑰對，同時需要將使用者登出
            if ($logoutUser) $this->logoutUser();
        }
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
            // $this->publicKey = $nlcore->safe::PKBE_PUB_B . $nlcore->cfg->enc->defaultPublicKey . $nlcore->safe::PKBE_PUB_E;
        } else {
            $redisTimeout = $nlcore->cfg->enc->redisCacheTimeout;
            // 查詢 apptoken 對應的 公鑰 和 私鑰
            // 先嚐試從 Redis 中載入
            $redisName = $nlcore->cfg->db->redis_tables["rsa"];
            $redisKey = $redisName . $apptoken;
            if ($redisTimeout != 0 && $nlcore->db->initRedis() && $nlcore->db->redis->exists($redisKey)) {
                $redisVal = $nlcore->db->redis->get($redisKey);
                $keyArr = explode("|", $redisVal);
                if (count($keyArr) != 2) {
                    $this->publicKey = $keyArr[0];
                    $this->privateKey = $keyArr[1];
                } else {
                    // 移除 Redis 中的错误条目
                    $nlcore->sess->logoutDevice($apptoken, 1);
                }
            }
            // 校驗是否為私鑰和公鑰
            $nlcore->safe->autoRsaAddTag();
            if (!$nlcore->safe->autoCheck()) {
                // 移除 Redis 中的错误条目
                $nlcore->sess->logoutDevice($apptoken, 1);
                // 不能從 Redis 中載入，從 MySQL 中載入
                $datadic = ["apptoken" => $apptoken];
                $tableStr = $nlcore->cfg->db->tables["encryption"];
                $columnArr = ["public", "private"];
                $result = $nlcore->db->select($columnArr, $tableStr, $datadic);
                // 空或查詢失敗都視為不正確
                if (!$result || $result[0] != 1010000 || !isset($result[2][0])) {
                    $nlcore->msg->stopmsg(2020409, $apptoken ?? "no token");
                }
                $rdata = $result[2][0];
                // 金鑰只有一個還是兩個都沒有
                $rdataNone = 0;
                if (!isset($rdata["private"])) $rdataNone++;
                if (!isset($rdata["public"])) $rdataNone++;
                if ($rdataNone == 1) {
                    // 只有一個沒有，刪除錯誤的金鑰對
                    $nlcore->sess->logoutDevice($apptoken, 2);
                }
                if ($rdataNone > 0) {
                    // 只要有一個沒有，就返回錯誤
                    $nlcore->msg->stopmsg(2020421, $apptoken, strval($rdataNone));
                }
                $this->publicKey = $rdata["public"];
                $this->privateKey = $rdata["private"];
                // 再次校驗是否為私鑰和公鑰，這次還有問題則錯誤
                $nlcore->safe->autoRsaAddTag();
                if (!$nlcore->safe->autoCheck()) {
                    $nlcore->msg->stopmsg(2020421, strval(strlen($this->publicKey)) . "-" . strval(strlen($this->privateKey)));
                }
                if ($redisTimeout != 0) {
                    // 重新建立 Redis 快取
                    $redisVal = $this->publicKey . "|" . $this->privateKey;
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
            if ($decryptData === FALSE) {
                $nlcore->msg->stopmsg(2020410, "3");
            }
            $decryptDataFull .= $decryptData;
        }
        // $decryptDataFullpreJD = strlen($decryptDataFull);
        if ($decryptDataFull != null) {
            // die($decryptDataFull);
            $decryptDataFull = str_replace("\n", "\\n", $decryptDataFull);
            $decryptDataFull = json_decode($decryptDataFull, true);
            if ($decryptDataFull == null) {
                $nlcore->msg->stopmsg(2020410, "4"); //errorcode : 请求不是json
                $nlcore->safe->log("DECODE", ["[ERROR!]", 2020410, json_encode($decryptDataFull)]);
            }
        } else {
            $nlcore->msg->stopmsg(2020411);
            $nlcore->safe->log("DECODE", ["[ERROR!]", 2020411]);
        }
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
     * @return Array [會話令牌,使用者會話資訊,使用者雜湊]
     */
    function userLogged(): void {
        global $nlcore;
        $argReceived = $this->argReceived;
        if (!isset($argReceived["token"])) $nlcore->msg->stopmsg(2040400, "T-N");
        $userToken = $argReceived["token"];
        if (!$nlcore->safe->is_rhash64($userToken)) $nlcore->msg->stopmsg(2040402, "T-" . $userToken);
        $userSessionInfo = $this->sessionstatuscon($userToken, true);
        if (!$userSessionInfo) $nlcore->msg->stopmsg(2040400, "T-" . $userToken);
        $userHash = $userSessionInfo["userhash"];
        $this->userToken = $userToken;
        $this->userSessionInfo = $userSessionInfo;
        $this->userHash = $userHash;
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
