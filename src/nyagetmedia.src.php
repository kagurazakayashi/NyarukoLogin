<?php
declare(strict_types=1);

/**
 * 媒體資訊查詢（存根）
 *
 * 獲得上傳資源的媒體資訊，目前為未完成的存根實作。
 *
 * @package NyarukoLogin
 */
class nyagetmedia {

    /**
     * 獲得該上傳資源的資訊
     *
     * @param string $path 相對路徑
     */
    function getmedia(string $path): void {
        global $nlcore;
        $uploaddir = $nlcore->cfg->app->upload["uploaddir"];
    }
}
