<?php
class nyasearch {
    function search() {
        global $nlcore;
        //IP检查和解密客户端提交的信息
        $inputInformation = $nlcore->sess->decryptargv("fastsearch");
        $argReceived = $inputInformation[0];
        $totpToken = $inputInformation[2];
        $ipid = $inputInformation[3];
        $returnJson = [];
        //检查参数输入是否齐全
        $argReceivedKeys = ["type","word"];
        if ($nlcore->safe->keyinarray($argReceived,$argReceivedKeys) > 0) {
            $nlcore->msg->stopmsg(2000101);
        }
        //检查搜索模式
        $limit = [];
        if (isset($argReceived["limit"])) {
            $limits = explode("-", $argReceived["limit"]);
            $limit = [intval($limits[0]),intval($limits[1])];
        }
        switch ($argReceived["type"]) {
            case "username":
                if (!$limit) $limit = [10];
                $result = $this->searchuser($argReceived["word"],["name","nameid"],$limit);
                $returnJson["results"] = $result;
                break;
            default:
                break;
        }
        $returnJson["timestamp"] = time();
        echo $nlcore->sess->encryptargv($returnJson);
    }
    /**
     * @description: 输入关键词，模糊搜索用户
     * @param String word 关键词
     * @param Array<String> columnArr 需要搜索的列
     * @return:
     */
    function searchuser($word,$columnArr,$limit=null) {
        global $nlcore;
        $columnArr = ["name","nameid"];
        $whereDic = [
            "name" => "%".$word."%"
        ];
        $whereMode = "LIKE";
        $tableStr = $nlcore->cfg->db->tables["info"];
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic,"","AND",true,[],$limit);
        if ($result[0] != 1010000 && $result[0] != 1010001) $nlcore->msg->stopmsg(2040500);
        if (isset($result[2])) {
            return $result[2];
        } else {
            return [];
        }
    }
}
?>
