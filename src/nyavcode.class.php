<?php

/**
 * @description: 簡訊和郵箱驗證碼的建立和認證
 * @package NyarukoLogin
 */
class nyavcode {
    public $module = "";
    private $code = 000000;
    /**
     * @description: 建立簡訊驗證碼
     */
    function getvcode_sns(): void {
        global $nlcore;
        $debug = $nlcore->cfg->verify->debug;
        $this->code = rand(100000, 999999);
        $this->module = "sns";
        $timeout = $nlcore->cfg->verify->timeout[$this->module];
        $txt = $this->msgAddInfo($nlcore->cfg->app->appname, $timeout, $nlcore->cfg->verify->vcodetext_sns);
    }
    /**
     * @description: 建立郵件驗證碼
     */
    function getvcode_mail(): void {
        global $nlcore;
        $debug = $nlcore->cfg->verify->debug;
        // $code = $nlcore->safe->randhash("", false, false);
        $code = rand(100000, 999999);
        $this->module = "mail";
        $timeout = $nlcore->cfg->verify->timeout[$this->module];
        $txt = $this->msgAddInfo($nlcore->cfg->app->appname, $timeout, $nlcore->cfg->verify->vcodetext_mail);
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
            // TODO
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
     */
    function check(int $code): void {
        global $nlcore;
        if ($nlcore->db->initRedis()) {
            // TODO
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
     * @description: 保存信息发送历史记录
     */
    function saveHistory() {
        // TODO
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
        global $nlcore;
        $time = strval($timeout / 60);
        $txt = str_replace(['{code}', '{time}', '{appname}'], [strval($this->code), $time, $appname], $template);
        return $txt;
    }
}
