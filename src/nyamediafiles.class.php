<?php
declare(strict_types=1);

/**
 * 媒體檔案路徑解析
 *
 * 根據客戶端提供的路徑解析並返回對應的媒體檔案網址。
 *
 * @package NyarukoLogin
 */
class nyamediafiles {
    /**
     * 獲取某個媒體檔案路徑
     *
     * @param array $argReceived 客戶端提交資訊陣列
     * @return array 準備返回到客戶端的資訊陣列
     */
    function mediafiles(array $argReceived): array {
        global $nlcore;
        if (!isset($argReceived["path"])) {
            $nlcore->msg->stopmsg(2050201);
        }
        $uploaddir = $nlcore->cfg->app->upload["uploaddir"];
        $returnClientData = $nlcore->func->imageurl($argReceived["path"]);
        if (count($returnClientData) > 0) {
            $returnClientData["code"] = 1000000;
        } else {
            $returnClientData["code"] = 2050200;
        }
        return $returnClientData;
    }
}
