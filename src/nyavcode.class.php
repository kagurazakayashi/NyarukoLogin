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
            // TODO
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
            // TODO
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
