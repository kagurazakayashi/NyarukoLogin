<?php
declare(strict_types=1);

/**
 * 站內信與站內通知
 *
 * 處理使用者之間的站內訊息收發、訊息合併、已讀/未讀狀態管理等。
 *
 * @package NyarukoLogin
 */
class nyamessage {
    /**
     * 使用者私信發送（讀取客戶端提交資訊）
     */
    function newMessageFromUser(): void {
        global $nlcore;
        $from = $nlcore->sess->userToken;
        $to = $nlcore->sess->argReceived["to"];
        if (!$nlcore->safe->is_rhash64($to)) {
            $nlcore->msg->stopmsg(2020209);
        }
        $text = $nlcore->sess->argReceived["text"];
        if (strlen($text) == 0) {
            // TODO
        }
        $nlcore->safe->wordfilter($text);
        // 查是否有傳送站內信的許可權
        $permission = $nlcore->sess->permission("SYS_SEND_MESSAGE_TO_USER");
        if (!$permission) {
            $nlcore->msg->stopmsg(2080007);
            return;
        }
        $nlcore->safe->wordfilter($text);
        $this->newMessage($to, [$from], $text);
    }

    /**
     * 獲取當前使用者的私信（讀取客戶端提交資訊）
     *
     * @return array 該使用者收到的私信原始資料
     */
    function getMessageFromUser(): array {
        global $nlcore;
        $to = $nlcore->sess->userHash;
        if ($to && !$nlcore->safe->is_rhash64($to)) {
            $nlcore->msg->stopmsg(2040400);
        }
        $mode = isset($nlcore->sess->argReceived["mode"]) ? intval($nlcore->sess->argReceived["mode"]) : -1;
        $limit = isset($nlcore->sess->argReceived["limit"]) ? intval($nlcore->sess->argReceived["limit"]) : 0;
        $offset = isset($nlcore->sess->argReceived["offset"]) ? intval($nlcore->sess->argReceived["offset"]) : 10;
        $onlynum = (isset($nlcore->sess->argReceived["onlylen"]) && intval($nlcore->sess->argReceived["onlylen"]) > 0) ? true : false;
        // 要查詢的使用者範圍
        $all = false;
        if (isset($nlcore->sess->argReceived["userhash"])) {
            if (strcmp('all', $nlcore->sess->argReceived["userhash"]) == 0) {
                // 遍歷查詢某個賬戶及其所有子賬戶的資訊
                $all = true;
            } else {
                // 查詢某個子賬戶的資訊
                $subUserHash = $nlcore->func->subAccountChk();
                if (count($subUserHash) == 1) $to = $subUserHash[0];
            }
        }
        // 查詢使用者訊息
        $returnArr = $nlcore->msg->m(0, 1000000);
        $msgArr = $this->getMessage($to, $mode, $onlynum, $limit, $offset);
        if ($onlynum) {
            $returnArr["msgnum"] = intval($msgArr[0]["count(*)"]);
        } else {
            $this->genText($msgArr);
            $returnArr["msglist"] = $msgArr;
            $returnArr["msgnum"] = count($msgArr);
        }
        if ($all) {
            foreach ($nlcore->func->subaccount($nlcore->sess->userHash) as $userInfo) {
                $msgArr = $this->getMessage($userInfo["userhash"], $mode, $onlynum, $limit, $offset);
                if ($onlynum) {
                    $returnArr["msgnum"] += intval($msgArr[0]["count(*)"]);
                } else {
                    $this->genText($msgArr);
                    $returnArr["msglist"] = array_merge($returnArr["msglist"], $msgArr);
                    $returnArr["msgnum"] += count($msgArr);
                }
            }
        }
        $returnArr["mode"] = $mode;
        return $returnArr;
    }

    /**
     * 建立一個新的站內信（先檢查重複）
     *
     * @param string[] $to   收件人使用者雜湊陣列
     * @param string[] $from 發件人使用者雜湊陣列，空白則為系統訊息
     * @param string   $type 型別模板代碼
     * @param string   $text 訊息內容文字
     * @param int      $pri  優先級（0-9）
     */
    function newMessage(array $to, array $from = [], string $type = "", string $text = "", int $pri = 0): void {
        global $nlcore;
        // 檢查輸入
        $typeLen = strlen($type);
        $typeStr = $type;
        if ($typeLen == 0) {
            $typeStr = null;
        } else if ($typeLen != 3) {
            return;
        }
        if (mb_strlen($text) > 64) {
            $text = mb_substr($text, 0, 64);
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
                $dataItem = $this->checkRepetitive($tohash, $typeStr);
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
     * 建立一個新的站內信（直接寫入）
     *
     * @param ?string $from 發件人使用者雜湊，逗號分隔多個發件人，null 為系統訊息
     * @param ?string $type 型別模板代碼
     * @param string  $to   收件人使用者雜湊
     * @param string  $text 訊息內容文字
     * @param int     $pri  優先級
     */
    function newMessageInsert(?string $from, ?string $type, string $to, string $text, int $pri): void {
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
     * 檢查是否有類似訊息（同一收件人、同一型別、未讀）
     *
     * @param string  $to   收件人使用者雜湊
     * @param ?string $type 訊息型別代碼
     * @return array 現有的類似訊息資料
     */
    function checkRepetitive(string $to, ?string $type): array {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["messages"];
        $columnArr = ["id", "fromusr"];
        $whereDic = [
            "tousr" => $to,
            "readed" => 0
        ];
        if ($type !== null) {
            $whereDic["type"] = $type;
        }
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
     * 將訊息整合到原有的相似訊息中
     *
     * @param array  $data 相似訊息資料
     * @param string $from 發件人使用者雜湊
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
     * 獲取指定使用者的訊息
     *
     * @param string $to      要查詢訊息列表的使用者雜湊
     * @param int    $mode    訊息類型：0 重要未讀 1 普通未讀 2 所有未讀 3 已讀 其他 全部
     * @param bool   $onlynum 只返回數量
     * @param int    $limit   起始偏移
     * @param int    $offset  讀取筆數
     * @return array 訊息陣列
     */
    function getMessage(string $to, int $mode, bool $onlynum = false, int $limit = 0, int $offset = 10): array {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["messages"];
        $columnArr = $onlynum ? null : ["hash", "fromusr", "tousr", "type", "text", "time", "pri", "readed"];
        $whereDic = ["tousr" => $to];
        if ($mode == 0) {
            $whereDic["readed"] = 0;
            $whereDic["pri"] = 1;
        } else if ($mode == 1) {
            $whereDic["readed"] = 0;
            $whereDic["pri"] = 0;
        } else if ($mode == 2) {
            $whereDic["readed"] = 0;
        } else if ($mode == 3) {
            $whereDic["readed"] = 1;
        }
        if ($onlynum) {
            $dbResult = $nlcore->db->scount($tableStr, $whereDic);
        } else {
            $dbResult = $nlcore->db->select($columnArr, $tableStr, $whereDic, "", "AND", false, ["time"], [$limit, $offset]);
        }
        if ($dbResult[0] >= 2000000) {
            $nlcore->msg->stopmsg(2080003);
        } else if ($dbResult[0] == 1010000) {
            return $dbResult[2];
        }
        return [];
    }

    /**
     * 生成友好通知訊息，以 message 鍵插入每個條目
     *
     * @param array &$messageArr 使用 getMessage 獲得的通知訊息陣列（傳參考）
     * @param int   $maxLen      資訊預覽顯示長度，0 為不顯示，63 為最大值
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
            for ($j = 0; $j < count($fromusrArr); $j++) {
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

    /**
     * 將某個通知資訊標記為已讀或未讀（讀取客戶端提交資訊）
     *
     * @return array 包含影響行數的結果
     */
    function setStatFromUser(): array {
        global $nlcore;
        // 要查詢的使用者範圍
        $all = false;
        $userHash = $nlcore->sess->userHash;
        if (isset($nlcore->sess->argReceived["userhash"])) {
            if (strcmp('all', $nlcore->sess->argReceived["userhash"]) == 0) {
                // 遍歷查詢某個賬戶及其所有子賬戶的資訊
                $all = true;
            } else {
                // 查詢某個子賬戶的資訊
                $subUserHash = $nlcore->func->subAccountChk();
                if (count($subUserHash) == 1) $userHash = $subUserHash[0];
            }
        }
        $isRead = isset($nlcore->sess->argReceived["readstat"]) ? intval($nlcore->sess->argReceived["readstat"]) : 1;
        $editLine = 0;
        if ($isRead != 2) {
            $msgHash = $nlcore->sess->argReceived["msghash"] ?? "";
            if (strlen($msgHash) == 0 || !$nlcore->safe->is_rhash64($msgHash)) {
                $nlcore->msg->stopmsg(2080006);
            }
            $editLine = $this->setStat($isRead, $userHash, $msgHash);
        } else if ($all) {
            foreach ($nlcore->func->subaccount($nlcore->sess->userHash) as $userInfo) {
                $editLine += $this->setStat($isRead, $userInfo["userhash"]);
            }
        } else {
            $editLine = $this->setStat($isRead, $userHash);
        }
        $returnArr = $nlcore->msg->m(0, 1000000);
        $returnArr["num"] = $editLine;
        return $returnArr;
    }

    /**
     * 將某個通知資訊標記為已讀或未讀
     *
     * @param int     $isRead   標記為 0 未讀 1 已讀 2 全部已讀
     * @param ?string $userHash 使用者雜湊
     * @param ?string $msgHash  訊息雜湊（isRead != 2 時必須提供）
     * @return int 影響的資料行數
     */
    function setStat(int $isRead = 1, ?string $userHash = null, ?string $msgHash = null): int {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["messages"];
        $whereDic = [];
        $updateDic = [
            "readed" => 1
        ];
        $whereDic = [
            "tousr" => $userHash
        ];
        if ($isRead != 2) {
            $whereDic["hash"] = $msgHash;
            $updateDic["readed"] = ($isRead > 0) ? 1 : 0;
        }
        $dbResult = $nlcore->db->update($updateDic, $tableStr, $whereDic);
        if ($dbResult[0] >= 2000000) {
            $nlcore->msg->stopmsg(2080005);
        }
        return $dbResult[3];
    }
}
