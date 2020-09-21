<?php
require_once "nyaverification.class.php";

use PHPMailer\PHPMailer\PHPMailer; // composer require phpmailer/phpmailer
/**
 * @description: 簡訊和郵箱驗證碼的建立和認證
 * @package NyarukoLogin
 */
class nyavcode {
    private $module = "";
    private $code = 000000;
    /**
     * @description: 建立簡訊驗證碼
     * @param Array argReceived 客戶端提交資訊陣列
     * @return Array 可返回客戶端的資訊
     */
    function getvcode($argReceived): array {
        global $nlcore;
        $modulei = 0; //1.sms,2.mail
        // 檢查是否提供目標
        $to = isset($argReceived["to"]) ? $argReceived["to"] : $nlcore->msg->stopmsg(2000101);
        // 檢查是否提供目標型別
        if (isset($argReceived["type"]) && (strcmp($argReceived["type"], "sms") != 0 || strcmp($argReceived["type"], "mail") != 0)) {
            if ($argReceived["type"] == "sms") $modulei = 1;
            else if ($argReceived["type"] == "mail") $modulei = 2;
        } else {
            // 如果沒有提供目標型別，自動判斷目標型別
            if (is_numeric($to)) {
                if ($nlcore->safe->isPhoneNumCN($to)) {
                    $modulei = 1;
                } else {
                    $nlcore->msg->stopmsg(2000101); // 不支援的手機號碼格式
                }
            } else if ($nlcore->safe->isEmail($to)) {
                $modulei = 2;
            } else {
                $nlcore->msg->stopmsg(2000102);
            }
        }
        if ($modulei == 1) {
            $this->module = 'sms';
            return $this->getvcode_sms($to);
        } else if ($modulei == 2) {
            $this->module = 'mail';
            return $this->getvcode_mail($to);
        }
    }
    /**
     * @description: 建立簡訊驗證碼
     * @param String phoneNum 電話號碼
     * @return Array 可返回客戶端的資訊
     */
    function getvcode_sms(string $phoneNum): array {
        global $nlcore;
        $debug = $nlcore->cfg->verify->debug;
        $this->code = rand(100000, 999999);
        $timeout = $nlcore->cfg->verify->timeout[$this->module];
        $txt = $this->msgAddInfo($nlcore->cfg->app->appname, $timeout, $nlcore->cfg->verify->vcodetext_sns);
        $this->save();
        // 错误代码：渠道消息
        // 連線到傳送簡訊介面
        $statusCode = 2000002;
        if (strval($nlcore->cfg->verify->debugmail) > 0) {
            // 使用除錯郵箱模擬
            $statusCode = $this->smtp($nlcore->cfg->verify->debugmail, $phoneNum, $txt, $phoneNum, $txt, 0) ? 1030301 : 2030201;
            $this->saveHistory($phoneNum,$txt,strval($statusCode));
        }
        // TODO: 實際傳送簡訊
        $returnClientData = $nlcore->msg->m(0, $statusCode);
        if ($debug) $returnClientData['debug'] = $this->code;
        return $returnClientData;
    }
    /**
     * @description: 建立 Redis 鍵名
     * @return String 鍵名
     */
    function redisKeyName(): string {
        global $nlcore;
        return $nlcore->cfg->db->redis_tables["vcode2"] . $this->module . '_' . $nlcore->sess->appToken;
    }
    /**
     * @description: 儲存驗證碼
     */
    function save(): void {
        global $nlcore;
        $timeout = $nlcore->cfg->verify->timeout[$this->module];
        $cd = $nlcore->cfg->verify->cd[$this->module];
        if ($nlcore->db->initRedis()) {
            // 嘗試儲存到 Redis
            $redis = $nlcore->db->redis;
            $key = $this->redisKeyName($this->module);
            if ($redis->exists($key)) {
                // 如果有舊的驗證碼，先檢查時間是否允許再重新獲取下一個驗證碼
                if ($redis->TTL($key) > ($timeout - $cd)) {
                    $nlcore->msg->stopmsg(2020601, strval($timeout - $cd));
                } else {
                    // 移除舊的驗證碼
                    $redis->del($key);
                }
            }
            // 建立 val : 驗證碼,重試次數
            $val = $this->code . ',0';
            // 儲存驗證碼
            $redis->setex($key, $timeout, $val);
        } else {
            // 嘗試儲存到 MySQL
            $tableStr = $nlcore->cfg->db->tables['encryption'];
            $columnArr = ['vc2time'];
            $whereDic = ['apptoken' => $nlcore->sess->appToken];
            $result = $nlcore->db->select($columnArr, $tableStr, $whereDic);
            if ($result[0] >= 2000000) {
                $nlcore->msg->stopmsg(2020601);
            } else if ($result[0] == 1010000) {
                // 如果有舊的驗證碼，先檢查時間是否允許再重新獲取下一個驗證碼
                $data = $result[2][0];
                $starttime = $data['vc2time'];
                $ctime = time() - strtotime($starttime);
                if ($ctime < $cd) {
                    $nlcore->msg->stopmsg(2020601, strval($ctime));
                } else {
                    // 移除舊的驗證碼
                    $this->removeSqlvcode();
                }
            }
            // 儲存驗證碼
            $updateDic = [
                'vc2code' => strval($this->code),
                'vc2time' => $nlcore->safe->getdatetime(),
                'vc2type' => $this->module,
                'vc2try' => 0
            ];
            $result = $nlcore->db->update($updateDic, $tableStr, $whereDic);
            if ($result[0] >= 2000000) {
                $nlcore->msg->stopmsg(2020601);
            }
        }
    }
    /**
     * @description: 檢查驗證碼是否正確
     * @param Int code 驗證碼
     * @param String module 功能模块
     */
    function check(int $code, string $module = ""): void {
        global $nlcore;
        if (strlen($module) > 0) $this->module = $module;
        if ($nlcore->db->initRedis()) {
            // 嘗試從 Redis 載入
            $redis = $nlcore->db->redis;
            $key = $this->redisKeyName($this->module);
            // 檢查是否有驗證碼資料
            if ($redis->exists($key)) {
                $vals = explode(',', $redis->get($key));
                $saveCode = intval($vals[0]);
                $saveTry = intval($vals[1]);
                // 檢查驗證碼是否匹配
                if ($saveCode != $code) {
                    // 不匹配，增加重試次數
                    $saveTry++;
                    if ($saveTry > $nlcore->cfg->verify->maxtry[$this->module]) {
                        $redis->del($key);
                        $nlcore->msg->stopmsg(2020602);
                    } else {
                        // 儲存新的重試次數
                        $val = $saveCode . ',' . $saveTry;
                        $redis->setex($key, $redis->TTL($key), $val);
                    }
                    $nlcore->msg->stopmsg(2020600);
                }
            } else {
                $nlcore->msg->stopmsg(2020604);
            }
            // 刪除已經驗證透過的資訊
            $redis->del($key);
        } else {
            // 嘗試從 MySQL 載入
            // 檢查是否有驗證碼資料
            $tableStr = $nlcore->cfg->db->tables['encryption'];
            $columnArr = ['vc2code', 'vc2time', 'vc2type', 'vc2try'];
            $whereDic = ['apptoken' => $nlcore->sess->appToken];
            $result = $nlcore->db->select($columnArr, $tableStr, $whereDic);
            if ($result[0] == 1010000) {
                $data = $result[2][0];
                $saveCode = intval($data['vc2code']);
                $saveTry = intval($data['vc2try']);
                $saveType = $data['vc2type'];
                $saveTime = strtotime($data['vc2time']);
                // 檢查驗證碼是否匹配、是否超時
                if (time() - $saveTime < $nlcore->cfg->verify->timeout[$this->module]) {
                    // 超时，删除条目
                    $this->removeSqlvcode();
                    $nlcore->msg->stopmsg(2020604);
                } else if (strcmp($saveType, $this->module) == 0 && $saveCode != $code) {
                    // 不匹配，增加重試次數
                    $saveTry++;
                    if ($saveTry > $nlcore->cfg->verify->maxtry[$this->module]) {
                        $this->removeSqlvcode();
                        $nlcore->msg->stopmsg(2020602);
                    } else {
                        // 儲存新的重試次數
                        $updateDic = [
                            'vc2try' => strval($saveTry)
                        ];
                        $result = $nlcore->db->update($updateDic, $tableStr, $whereDic);
                        if ($result[0] >= 2000000) {
                            $nlcore->msg->stopmsg(2020602);
                        }
                        $nlcore->msg->stopmsg(2020600);
                    }
                }
            } else {
                $nlcore->msg->stopmsg(2020604);
            }
        }
    }
    /**
     * @description: 儲存資訊傳送歷史記錄
     * @param String sender 郵箱或手機號
     * @param String txt 傳送文字
     * @param String resultinfo 傳送結果
     */
    function saveHistory(string $sender, string $txt, string $resultinfo) {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables['encryption'];
        $insertDic = [
            'userhash' => $nlcore->sess->appToken,
            'apptoken' => $nlcore->sess->userHash,
            'ipid' => strval($nlcore->sess->ipId),
            'operation' => 'USER_SEND_' . strtoupper($this->module),
            'sender' => $sender,
            'process' => $txt,
            'result' => $resultinfo
        ];
        $nlcore->db->insert($tableStr, $insertDic);
    }
    /**
     * @description: 移除 SQL 中的舊驗證碼
     */
    function removeSqlvcode(): void {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables['encryption'];
        $whereDic = ['apptoken' => $nlcore->sess->appToken];
        $updateDic = [
            'vc2code' => null,
            'vc2time' => null,
            'vc2type' => null,
            'vc2try' => null
        ];
        $result = $nlcore->db->update($updateDic, $tableStr, $whereDic);
        if ($result[0] >= 2000000) {
            $nlcore->msg->stopmsg(2020602);
        }
    }
    /**
     * @description: 建立驗證碼，並向傳送文字內填充資訊
     * @param String appname 應用顯示名稱
     * @param Int timeout 多少秒有效
     * @param String template 文字模板
     * @return String 替換後的文字
     */
    function msgAddInfo(string $appname, int $timeout, string $template): string {
        $time = strval($timeout / 60);
        $txt = str_replace(['{code}', '{time}', '{appname}'], [strval($this->code), $time, $appname], $template);
        return $txt;
    }
}
