<?php

/**
 * @description: 站內信和站內通知
 * @package NyarukoLogin
 */
class nyamessage {

    /**
     * @description: 建立一個新的站內信（先檢查）
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
        if (mb_strlen($text) > 63) {
            $text = mb_substr($text, 0, 63);
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
                if (count($dataItem) > 0) {
                    $this->newMessageUpdate($dataItem, $fromStr);
                } else {
                    // 沒有類似訊息，立即建立新訊息
                    $this->newMessageInsert($fromStr, $typeStr, $tohash,  $text, $pri);
                }
            } else {
                // 發件人是系統訊息，無需合併訊息，立即建立新訊息
                $this->newMessageInsert($fromStr, $typeStr, $tohash,  $text, $pri);
            }
        }
    }

    /**
     * @description: 建立一個新的站內信
     * @param string from 發件人使用者雜湊，支援多個發件人，使用逗號分隔，空白則為系統訊息
     * @param string type 型別模板（配置檔案中的 $messageTmp）
     * @param string to   收件人使用者雜湊，支援多個收件人，不可以空白
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
        if ($from) $insertDic["fromusr"] = $from;
        $dbResult = $nlcore->db->insert($tableStr, $insertDic);
        if ($dbResult[0] >= 2000000) $nlcore->msg->stopmsg(2080002);
    }

    /**
     * @description: 檢查是否有類似訊息（訊息型別、收件人、訊息內容相同）
     * @param  string to   收件人使用者雜湊
     * @param  string type 訊息型別程式碼
     * @return array 當前查到的先有類似訊息資料
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
        $dbResult = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        if ($dbResult[0] == 1010000) {
            return $dbResult[2][0];
        } else if ($dbResult[0] == 1010001) {
            return [];
        } else {
            $nlcore->msg->stopmsg(2080000);
        }
    }

    /**
     * @description: 將訊息整合到原有的相似訊息中
     * @param array  data 查詢類似訊息時伺服器返回資料
     * @param string from 發件人使用者雜湊，支援多個發件人，使用逗號分隔
     */
    function newMessageUpdate(array $data, string $from): void {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["messages"];
        // 已經有類似訊息存在，合併類似訊息
        $fromStr = "";
        $fromusrArr = explode(',', $data["fromusr"]);
        if (!in_array($from, $fromusrArr)) {
            array_push($fromusrArr, $from);
            $fromStr = implode(',', $fromusrArr);
        }
        // 更新已有訊息記錄
        if (strlen($fromStr) > 0) {
            $updateDic = [
                "fromusr" => $fromStr
            ];
            $whereDic = [
                "id" => $data["id"]
            ];
            $dbResult = $nlcore->db->update($updateDic, $tableStr, $whereDic);
            if ($dbResult[0] >= 2000000) {
                $nlcore->msg->stopmsg(2080001);
            }
        }
    }

    /**
     * @description: 獲取我的訊息
     * @param string to     要查詢訊息列表的使用者雜湊
     * @param int    mode   要獲得的訊息型別
     *               0 重要未讀資訊  1 普通未讀資訊  2 已讀資訊  ? 所有資訊
     * @param int    limit  從哪條開始
     * @param int    offset 讀取多少條
     */
    function getMessage(string $to, int $mode, int $limit = 0, int $offset = 10): array {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["messages"];
        $columnArr = ["hash", "fromusr", "tousr", "type", "text", "time", "pri", "readed"];
        $whereDic = ["tousr" => $to];
        if ($mode == 0) {
            $whereDic["readed"] = 0;
            $whereDic["pri"] = 1;
        } else if ($mode == 1) {
            $whereDic["readed"] = 0;
            $whereDic["pri"] = 0;
        } else if ($mode == 2) {
            $whereDic["readed"] = 1;
        }
        $dbResult = $nlcore->db->select($columnArr, $tableStr, $whereDic, "", "AND", false, ["time"], [$limit, $offset]);
        if ($dbResult[0] >= 2000000) {
            $nlcore->msg->stopmsg(2080003);
        } else if ($dbResult[0] == 1010000) {
            return $dbResult[2];
        }
        return [];
    }

    /**
     * @description: 生成友好通知訊息，以 info 鍵插入每個條目
     * @param array &messageArr 使用 getMessage 獲得的通知訊息陣列指標
     * @param int maxLen 信息预览的显示长度 0为不显示，63为最大值（负数视为63）
     */
    function genText(array &$messageArr, int $maxLen = 0): void {
        global $nlcore;
        $cfgNum = $nlcore->cfg->app->messageNum;
        $cfgTmp = $nlcore->cfg->app->messageTmp;
        for ($i = 0; $i < count($messageArr); $i++) {
            $messageItem = $messageArr[$i];
            $fromusrArr = explode(',', $messageItem["fromusr"]);
            $info = "";
            $fromusrArrCount = count($fromusrArr);
            $numStr = $cfgNum[$fromusrArrCount - 1] ?? $nlcore->msg->stopmsg(2080004, 'cfgNum');
            $tmpStr = $cfgTmp[$messageItem["type"]] ?? $nlcore->msg->stopmsg(2080004, 'cfgTmp');
            // 為每個使用者雜湊生成暱稱
            for ($j=0; $j < count($fromusrArr); $j++) {
                $fromusrArr[$j] = $nlcore->func->nickNameArr2nickNameFullStr($nlcore->func->userHash2nickNameArr($fromusrArr[$j]));
            }
            // 建立友好資訊
            if ($fromusrArrCount >= 1) {
                $info = str_replace("%1", $fromusrArr[0], $numStr);
            }
            if ($fromusrArrCount >= 2) {
                $info = str_replace("%2", $fromusrArr[1], $numStr);
            }
            if ($fromusrArrCount >= 3) {
                $info = str_replace("%3", strval($fromusrArrCount), $numStr);
            }
            $text = "";
            if ($maxLen != 0) {
                $text = $messageItem["text"];
                $textLen = mb_strlen($text);
                if ($maxLen > 0) {
                    if ($maxLen > 63) {
                        $maxLen = 63;
                    }
                    if ($textLen >= $maxLen) {
                        $text = ': ' . mb_substr($text, 0, $maxLen) . '...';
                    }
                } else {
                    $text = ': ' . $text;
                    if ($textLen >= 63) {
                        $text .= '...';
                    }
                }
            }
            $messageItem["message"] = $info . $tmpStr . $text;
            $messageArr[$i] = $messageItem;
        }
    }
}
