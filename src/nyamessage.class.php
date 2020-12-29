<?php

/**
 * @description: 站內信和站內通知
 * @package NyarukoLogin
 */
class nyamessage {
    /**
     * @description: 建立一個新的站內信
     * @param array  to   收件人使用者雜湊，支援多個收件人，不可以空白
     * @param array  from 發件人使用者雜湊，支援多個發件人，空白則為系統訊息
     * @param string type 型別模板（配置檔案中的 $messageTmp）
     * @param string text 訊息內容文字
     * @param int    pri  优先级
     */
    function newMessage(array $to, array $from = [], string $type = "", string $text = "", int $pri = 0) {
        global $nlcore;
        // 檢查輸入
        $typeLen = strlen($type);
        $typeStr = $type;
        if ($typeLen == 0) {
            $typeStr = null;
        } else if ($typeLen != 3) {
            return;
        }
        if (strlen($text) > 250) {
            $text = substr($text, 0, 250) . '...';
        }
        if ($pri < 0 || $pri > 9) {
            return;
        }
        // 收件人遍歷，分別給每個收件人傳送內容
        foreach ($to as $tohash) {
            // 發件人，空白則為系統訊息
            $fromStr = count($from) > 0 ? implode(',', $from) : null;
            if ($fromStr) {
                // 检查是否有类似消息
                $dataItem = $this->checkRepetitive($tohash, $typeStr, $fromStr);
                if ($dataItem) {
                    $this->newMessageUpdate($dataItem, $fromStr);
                } else {
                    // 沒有類似訊息，立即建立新訊息
                    $this->newMessageInsert($fromStr, $tohash, $typeStr, $text, $pri);
                }
            } else {
                // 發件人是系統訊息，無需合併資訊，立即建立新訊息
                $this->newMessageInsert($fromStr, $tohash, $typeStr, $text, $pri);
            }
        }
    }

    /**
     * @description: 建立一個新的站內信
     * @param string from 發件人使用者雜湊，支援多個發件人，使用逗號分隔，空白則為系統訊息
     * @param string to   收件人使用者雜湊，支援多個收件人，不可以空白
     * @param string type 型別模板（配置檔案中的 $messageTmp）
     * @param string text 訊息內容文字
     * @param int    pri  优先级
     */
    function newMessageInsert(string $from = null, string $type = null, string $to, string $text, int $pri): void {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["messages"];
        $hash = $nlcore->safe->randstr(64);
        $insertDic = [
            "hash" => $hash,
            "fromusr" => $from,
            "tousr" => $to,
            "text" => $text,
            "pri" => strval($pri)
        ];
        if ($type) $insertDic["type"] = $type;
        if ($from) $insertDic["fromusr"] = $type;
        $result = $nlcore->db->insert($tableStr, $insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2080002);
    }

    /**
     * @description: 檢查是否有類似訊息（資訊型別、收件人、訊息內容相同）
     * @param  string to   收件人使用者雜湊
     * @param  string type 資訊型別程式碼
     * @return array/null 當前查到的先有類似訊息資料
     */
    function checkRepetitive(string $to, string $type): array {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["messages"];
        $columnArr = ["id", "fromusr"];
        $whereDic = [
            "tousr" => $to,
            "type" => $type,
            "readed" => 0
        ];
        $dbreturn = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        if ($dbreturn[0] == 1010000) {
            return $dbreturn[2][0];
        } else if ($dbreturn[0] == 1010001) {
            return null;
        } else {
            $nlcore->msg->stopmsg(2080000);
        }
    }

    /**
     * @description: 將資訊整合到原有的相似資訊中
     * @param array  data 查詢類似訊息時伺服器返回資料
     * @param string from 發件人使用者雜湊，支援多個發件人，使用逗號分隔
     */
    function newMessageUpdate(array $data, string $from): void {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["messages"];
        // 已經有類似訊息存在，合併類似訊息
        $fromusrArr = explode(',', $data["fromusr"]);
        if (!in_array($from, $fromusrArr)) {
            array_push($fromusrArr, $from);
            $fromStr = implode(',', $fromusrArr);
        }
        // 更新已有資訊記錄
        $updateDic = [
            "fromusr" => $fromStr
        ];
        $whereDic = [
            "id" => $data["id"]
        ];
        $dbreturn = $nlcore->db->update($updateDic, $tableStr, $whereDic);
        if ($dbreturn[0] >= 2000000) {
            $nlcore->msg->stopmsg(2080001);
        }
    }
}
