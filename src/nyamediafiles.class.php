<?php
/**
 * @description: 获取某个媒体文件路径
 * @package NyarukoLogin
*/
class nyamediafiles {
    /**
     * @description: 功能入口：子賬戶註冊
     * @param Array argReceived 客戶端提交資訊陣列
     * @return 準備返回到客戶端的資訊陣列
     */
    function mediafiles($argReceived) {
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
?>