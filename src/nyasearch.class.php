<?php
class nyasearch {
    function search() {
        global $nlcore;
        //IP检查和解密客户端提交的信息
        $jsonarrTotpsecret = $nlcore->safe->decryptargv("fastsearch");
        $jsonarr = $jsonarrTotpsecret[0];
        $totpsecret = $jsonarrTotpsecret[1];
        $totptoken = $jsonarrTotpsecret[2];
        $ipid = $jsonarrTotpsecret[3];
        $returnjson = [];
        //检查参数输入是否齐全
        $getkeys = ["type","word"];
        if ($nlcore->safe->keyinarray($jsonarr,$getkeys) > 0) {
            $nlcore->msg->stopmsg(2000101,$totpsecret);
        }
        //检查搜索模式
        $limit = null;
        if (isset($jsonarr["limit"])) {
            $limits = explode("-", $jsonarr["limit"]);
            $limit = [intval($limits[0]),intval($limits[1])];
        }
        switch ($jsonarr["type"]) {
            case "username":
                if (!$limit) $limit = 10;
                $result = $this->searchuser($jsonarr["word"],["name","nameid"],$limit,$totpsecret);
                $returnjson["results"] = $result;
                break;
            default:
                break;
        }
        $returnjson["timestamp"] = time();
        echo $nlcore->safe->encryptargv($returnjson,$totpsecret);
    }
    /**
     * @description: 输入关键词，模糊搜索用户
     * @param String word 关键词
     * @param Array<String> columnArr 需要搜索的列
     * @param String totpsecret 加密用secret（不加则自动）
     * @return:
     */
    function searchuser($word,$columnArr,$limit,$totpsecret=null) {
        global $nlcore;
        $columnArr = ["name","nameid"];
        $whereDic = [
            "name" => "%".$word."%"
        ];
        $whereMode = "LIKE";
        $tableStr = $nlcore->cfg->db->tables["info"];
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic,"","AND",true,null,$limit);
        if ($result[0] != 1010000 && $result[0] != 1010001) $nlcore->msg->stopmsg(2040500,$totpsecret);
        if (isset($result[2])) {
            return $result[2];
        } else {
            return [];
        }
    }
}
?>
