<?php
/**
 * @description: 模糊搜尋使用者
 * @package NyarukoLogin
*/
class nyasearch {
    /**
     * @description: 功能入口：輸入關鍵詞，模糊搜尋使用者
     * @param Array argReceived 客戶端提交資訊陣列
     * @return 準備返回到客戶端的資訊陣列
     */
    function search(array $argReceived): array {
        global $nlcore;
        $returnClientData = [];
        // 檢查引數輸入是否齊全
        $argReceivedKeys = ["type", "word"];
        if ($nlcore->safe->keyinarray($argReceived, $argReceivedKeys) > 0) {
            $nlcore->msg->stopmsg(2000101);
        }
        // 檢查搜尋模式
        $limit = [];
        if (isset($argReceived["limit"])) {
            $limits = explode("-", $argReceived["limit"]);
            $limit = [intval($limits[0]), intval($limits[1])];
        }
        switch ($argReceived["type"]) {
            case "username":
                if (!$limit) $limit = [10];
                $result = $this->searchuser($argReceived["word"], ["name", "nameid"], $limit);
                $returnClientData["results"] = $result;
                break;
            default:
                break;
        }
        return $returnClientData;
    }
    /**
     * @description: 輸入關鍵詞，模糊搜尋使用者
     * @param String word 關鍵詞
     * @param Array columnArr 需要搜尋的列
     * @param Array limit 限制結果數量
     * @return 使用者暱稱和暱稱唯一碼
     */
    function searchuser(string $word, array $columnArr, $limit = null): array {
        global $nlcore;
        $columnArr = ["name", "nameid"];
        $whereDic = [
            "name" => "%" . $word . "%"
        ];
        $tableStr = $nlcore->cfg->db->tables["info"];
        $result = $nlcore->db->select($columnArr, $tableStr, $whereDic, "", "AND", true, [], $limit);
        if ($result[0] != 1010000 && $result[0] != 1010001) $nlcore->msg->stopmsg(2040500);
        if (isset($result[2])) {
            return $result[2];
        } else {
            return [];
        }
    }
}
