<?php

/**
 * @description: MySQL/Redis 資料庫語句生成和連接管理類
 * @package NyarukoLogin
 */
class nyadbconnect {
    const SQL_DEBUG = true; //出错时同时返回SQL语句原文（警告：请勿在生产服务器上开启）
    private $conR = null; //隻讀資料庫
    private $conW = null; //可寫入資料庫
    private $con = null; //當前 MySQL 資料庫（指針變數）
    private $logfile = null; //記錄詳細調試信息到文件
    public $redis = null; //當前 Redis 資料庫

    /**
     * @description: 初始化可寫入資料庫，按需建立SQL連接
     */
    function initWriteDbs() {
        global $nlcore;
        $this->log("[CONNECT] read-write mode.");
        if (!$this->conW) {
            $this->conW = $this->initMysqli($nlcore->cfg->db->write_dbs);
            mysqli_set_charset($this->conW, $nlcore->cfg->db->charset);
        }
        $this->con = &$this->conW;
    }

    /**
     * @description: 初始化隻讀資料庫，按需建立SQL連接
     */
    function initReadDbs() {
        global $nlcore;
        $this->log("[CONNECT] read-only mode.");
        if (!$this->conR) {
            $this->conR = $this->initMysqli($nlcore->cfg->db->read_dbs);
            mysqli_set_charset($this->conR, $nlcore->cfg->db->charset);
        }
        $this->con = &$this->conR;
    }

    /**
     * @description: 初始化資料庫
     * @param String selectdbs 資料庫配置數組($nlcore->cfg->db->*)
     * @return mysqli_connect 資料庫連接對象
     */
    function initMysqli($selectdbs) {
        global $nlcore;
        $selectdbscount = count($selectdbs) - 1;
        if ($selectdbscount >= 0) {
            // 如果 Redis 可用則順序選資料庫，不可用則隨機選資料庫
            $dbid = 0;
            if ($selectdbscount > 0) {
                if ($this->initRedis()) {
                    $redis = $this->redis;
                    $key = $nlcore->cfg->db->redis_tables["sqldb"];
                    if ($redis->exists($key)) {
                        $dbid = intval($redis->get($key));
                        if ($dbid > $selectdbscount) {
                            $redis->set($key, 0);
                        } else {
                            $dbid++;
                            $redis->incr($key);
                        }
                    } else {
                        $redis->set($key, 0);
                    }
                } else {
                    $dbid = rand(0, $selectdbscount);
                }
            }
            $selectdb = $selectdbs[$dbid];
            $this->log("[CONNECT] " . $selectdb["db_user"] . "@" . $selectdb["db_host"] . ":" . $selectdb["db_port"] . "/" . $selectdb["db_name"]);
            $newcon = mysqli_connect($selectdb["db_host"], $selectdb["db_user"], $selectdb["db_password"], $selectdb["db_name"], $selectdb["db_port"]);
            $sqlerrno = mysqli_connect_errno($newcon);
            if ($sqlerrno) {
                $this->log("[ERROR] " . $sqlerrno);
                die($nlcore->msg->m(1, 2010100, $sqlerrno));
            }
            return $newcon;
        } else {
            $this->log("[ERROR]");
            die($nlcore->msg->m(1, 2010103));
        }
        return null;
    }

    /**
     * @description: 清理提交數據中的註入語句
     * @param String/Array data 要進行清理的內容，支援多維數組、字符串，其他類型（如 int）不清理
     * @return String/Array 清理後的數組/字符串
     */
    function safe($data) {
        $newdata = null;
        if (is_array($data)) {
            $newdata = [];
            foreach ($data as $key => $value) {
                $newdata[$key] = $this->safe($value);
            }
        } else if (is_string($data)) {
            $newdata = mysqli_real_escape_string($this->con, $data);
        } else {
            $newdata = $data;
        }
        return $newdata;
    }

    /**
     * @description: 將每條SQL語句和返回內容記錄在日誌檔案中，通過 nyaconfig 中的此項設定來進行偵錯。
     * @param String logstr 要記錄的字元串
     */
    function log(string $logstr): void {
        global $nlcore;
        if (!isset($nlcore->cfg->db->logfile_db) || $nlcore->cfg->db->logfile_db == null || $nlcore->cfg->db->logfile_db == "") return;
        $logfilepath = $nlcore->cfg->db->logfile_db;
        if ($logfilepath) {
            $ipaddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
            $proxyaddr = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? "@" . $_SERVER['HTTP_X_FORWARDED_FOR'] : "";
            $logstr = "[" . $nlcore->safe->getdatetime()[1] . "][" . $ipaddr . $proxyaddr . "]" . $logstr . PHP_EOL;
            if (!$this->logfile) $this->logfile = fopen($logfilepath, "a");
            fwrite($this->logfile, $logstr);
        }
    }

    /**
     * @description: 查詢資料
     * @param Array<String/Array/Null> columnArr 要查詢的列名陣列，支援兩種格式 ["列1","列2"] 或 [["表1","列1"],["表1","列2"]]，傳 [] 則為 *
     * @param String tableStr 表名或 *JOIN*ON* 語句
     * @param Array<String/String> whereDic 條件字典（k:列名=v:預期內容），列名支援 '*' 和 '.' 標記，詳細見 dic2sql 的註釋。
     * @param String customWhere 自定義條件錶達式（可選，預設空，不走安全檢查）
     * @param String whereMode 條件判斷模式（AND/OR/...，可選，預設AND）
     * @param Array<String,Bool> order 排序方式[排序依據,是否倒序]，[]為不使用
     * @param Array<Int>/Array<Int,Int> limit 區間， [前N條] 或 [從多少,取多少]，[]為不使用
     * @param Boolean islike 模糊搜素（可選，預設關）
     * @return Array<Int,Array> 返回的狀態碼和內容
     */
    function select(array $columnArr = [], string $tableStr, array $whereDic, string $customWhere = "", string $whereMode = "AND", bool $islike = false, array $order = [], array $limit = []): array {
        $this->initReadDbs();
        $columnStr = $this->gColumnStr($columnArr);
        $whereDic = $this->safe($whereDic);
        $whereStr = "";
        if ($whereMode == "IN") {
            $whereStr = $this->dic2sql($whereDic, 4, $islike);
        } else {
            $whereStr = $this->dic2sql($whereDic, 2, $islike);
        }
        if ($customWhere != "" && $whereDic) $customWhere = " " . $whereMode . " " . $customWhere;
        $orderstr = "";
        if (count($order) > 0) {
            $orderstr = " ORDER BY `" . $order[0] . "`";
            if (isset($order[1]) && $order[1] === true) $orderstr .= " DESC";
        }
        if (count($limit) > 0) {
            $orderstr .= " limit ";
            if (count($limit) > 1) {
                $orderstr .= strval($limit[0]) . "," . strval($limit[1]);
            } else {
                $orderstr .= strval($limit[0]);
            }
        }
        if (strpos(strtoupper($tableStr), "JOIN") == false) {
            $tableStr = "`" . $tableStr . "`";
        }
        $where = $whereStr . $customWhere . $orderstr;
        if (strlen($where) > 0) $where = " WHERE " . $where;
        $sqlcmd = "SELECT " . $columnStr . " FROM " . $tableStr . $where . ";";
        return $this->sqlc($sqlcmd);
    }

    /**
     * @description: 處理列輸入
     * @param Array columnArr 要查詢的列名陣列
     * @return String 查詢字串
     */
    function gColumnStr(array $columnArr): string {
        $columnStr = "";
        if (count($columnArr) == 0) {
            $columnStr = "*";
        } else if (is_array($columnArr[0])) {
            $tablecolumnarr = [];
            foreach ($columnArr as $tc) {
                $table = $this->safe($tc[0]);
                $column = $this->safe($tc[1]);
                array_push($tablecolumnarr, "`" . $table . "`.`" . $column . "`");
            }
            $columnStr = implode(",", $tablecolumnarr);
        } else if (is_string($columnArr[0])) {
            $columnStr = "`" . implode("`,`", $this->safe($columnArr)) . "`";
        }
        return $columnStr;
    }

    /**
     * @description: 插入數據
     * @param String tableStr 錶名
     * @param Array <String:String> insertDic 要插入的數據字典
     * @param Bool ignoreExisting 如果数据已经存在则不添加
     * （僅適用於主鍵和索引，綜合所有輸入用 insertInNull 函式）
     * @return Array <Int,Array> 返回的狀態碼和內容
     */
    function insert(string $tableStr, array $insertDic, bool $ignoreExisting = false): array {
        $this->initWriteDbs();
        $insertDic = $this->safe($insertDic);
        $insertStr = $this->dic2sql($insertDic, 0);
        $ignore = $ignoreExisting ? " IGNORE" : "";
        $sqlcmd = "INSERT" . $ignore . " INTO `" . $tableStr . "` " . $insertStr . ";";
        return $this->sqlc($sqlcmd);
    }

    /**
     * @description: 更新數據
     * @param Array<String:String> updateDic 要更新的數據字典
     * @param String tableStr 錶名
     * @param Array<String:String> whereDic 條件字典（k:列名=v:預期內容），列名支援 '*' 和 '.' 標記，詳細見 dic2sql 的註釋。
     * @param String customWhere 自定義條件錶達式（可選，預設空，不走安全檢查註意）
     * @param String whereMode 條件判斷模式（AND/OR/...，可選，預設AND）
     * @return Array<Int,Array> 返回的狀態碼和內容
     */
    function update($updateDic, $tableStr, $whereDic, $customWhere = "", $whereMode = "AND") {
        $this->initWriteDbs();
        $updateDic = $this->safe($updateDic);
        $whereDic = $this->safe($whereDic);
        $update = $this->dic2sql($updateDic, 1);
        $whereStr = $this->dic2sql($whereDic, 2);
        if ($customWhere != "" && $whereDic) $customWhere = " " . $whereMode . " " . $customWhere;
        $sqlcmd = "UPDATE `" . $tableStr . "` SET " . $update . " WHERE " . $whereStr . $customWhere . ";";
        return $this->sqlc($sqlcmd);
    }

    /**
     * @description: 如果有則更新數據，冇有則插入數據
     * 已棄用：請使用 insert 函式中的 ignoreExisting
     * @param String tableStr 錶名
     * @param Array<String:String> dataDic 要更新或插入的數據字典
     * @param String customWhere 自定義條件錶達式（可選，預設空，不走安全檢查註意）
     * @param String whereMode 條件判斷模式（AND/OR/...，可選，預設AND）
     * @return Array<Int,Array> 返回的狀態碼和內容
     */
    function insertUpdate($tableStr, $dataDic, $whereDic = null, $customWhere = "", $whereMode = "AND") {
        $result = $this->scount($tableStr, $dataDic, $customWhere, $whereMode);
        if ($result[0] >= 2000000) return [$result[0]];
        $datacount = $result[2][0]["count(*)"];
        if ($datacount == 0) {
            return $this->insert($tableStr, $dataDic);
        } else if ($datacount == 1) {
            return $this->update($dataDic, $tableStr, $whereDic, $customWhere, $whereMode);
        } else {
            return [2010300];
        }
    }

    /**
     * @description: 如果冇有，才添加數據
     * @param String tableStr 錶名
     * @param Array<String:String> dataDic 要更新或插入的數據字典
     * @param String customWhere 自定義條件錶達式（可選，預設空，不走安全檢查註意）
     * @param String whereMode 條件判斷模式（AND/OR/...，可選，預設AND）
     * @return Array<Int,Array> 返回的狀態碼和內容
     */
    function insertInNull($tableStr, $dataDic, $customWhere = "", $whereMode = "AND") {
        $result = $this->scount($tableStr, $dataDic, $customWhere, $whereMode);
        if ($result[0] >= 2000000) return [$result[0]];
        $datacount = $result[2][0]["count(*)"];
        if ($datacount == 0) {
            return $this->insert($tableStr, $dataDic);
        } else {
            return [1010002];
        }
    }

    /**
     * @description: 刪除數據
     * @param String tableStr 錶名
     * @param Array<String:String> whereDic 條件字典（k:列名=v:預期內容），列名支援 '*' 和 '.' 標記，詳細見 dic2sql 的註釋。
     * @param String customWhere 自定義條件錶達式（可選，預設空，不走安全檢查註意）
     * @param String whereMode 條件判斷模式（AND/OR/...，可選，預設AND）
     * @return Array<Int,Array> 返回的狀態碼和內容
     */
    function delete($tableStr, $whereDic, $customWhere = "", $whereMode = "AND") {
        $this->initWriteDbs();
        $whereDic = $this->safe($whereDic);
        $whereStr = $this->dic2sql($whereDic, 2);
        if ($customWhere != "" && $whereDic) $customWhere = " " . $whereMode . " " . $customWhere;
        $sqlcmd = "DELETE FROM `" . $tableStr . "` WHERE " . $whereStr . $customWhere . ";";
        return $this->sqlc($sqlcmd);
    }

    /**
     * @description: 查詢有多少數據
     * @param String tableStr 錶名
     * @param Array<String:String> whereDic 條件字典（k:列名=v:預期內容），列名支援 '*' 和 '.' 標記，詳細見 dic2sql 的註釋。
     * @param String customWhere 自定義條件錶達式（可選，預設空，不走安全檢查註意）
     * @param String whereMode 條件判斷模式（AND/OR/...，可選，預設AND）
     * @param Boolean islike 模糊搜素（可選，預設關）
     * @return Array<Int,Array> 返回的狀態碼和內容
     */
    function scount($tableStr, $whereDic = null, $customWhere = "", $whereMode = "AND", $islike = false) {
        $this->initReadDbs();
        $whereDic = $this->safe($whereDic);
        $whereStr = $this->dic2sql($whereDic, 2, $islike);
        if ($customWhere != "" && $whereDic) $customWhere = " " . $whereMode . " " . $customWhere;
        $sqlcmd = "select count(*) from `" . $tableStr . "` WHERE " . $whereStr . $customWhere . ";";
        return $this->sqlc($sqlcmd);
    }

    /**
     * @description: 全文搜尋
     * @param String tableStr 錶名
     * @param Array columnArr 要查詢的列名陣列，支援兩種格式 ["列1","列2"] 或 [["表1","列1"],["表1","列2"]]，傳 [] 則為 *
     * @param Array searchColumn 要在哪些列中進行全文搜尋
     * @param Int mode 搜尋模式選項，決定下一項引數要輸入什麼內容。
     * 代码 功能描述      search参数的示例
     * 0  傳統搜尋模式  ["yashi"]
     * 1  模糊搜尋模式    ["yashi"]
     * 2  萬用字元模式    ["yashi*"]
     * 3  或者模式      ["miyabi","yashi"]
     * 4  自定義模式    [[1,"miyabi"],[-1,"yashi"]]
     *                  -1  不可以包含該關鍵詞
     *                   0  如果包含該關鍵詞則降低相關性
     *                   1  必須包含該關鍵詞
     *                   2  自定義表示式
     * 5  自然語言模式  ["yashi"]
     *    （如「啟動 計算機」可搜尋到「……然而當計算機啟動之後，……」）
     * @param Array search 要搜尋的內容（參考上面的示例）
     * @param Array<String,Bool> order 排序方式[排序依據,是否倒序]，[]為不使用
     * @param Array [int] / [int,int] limit 區間， [前N條] 或 [從多少,取多少]，[]為不使用
     */
    function searchWord(string $tableStr, array $columnArr = [], array $searchColumn, int $mode, array $search, array $order = [], array $limit = []): array {
        $this->initReadDbs();
        $columnStr = $this->gColumnStr($columnArr);
        $searchColumnStr = '`' . implode('`,`', $searchColumn) . '`';
        $nbMode = ($mode == 5) ? "NATURAL LANGUAGE" : "BOOLEAN";
        $searchStr = '';
        switch ($mode) {
            case 0:
                $search = $this->searchWordSafe($this->safe($search));
                $searchStr = '"' . $search[0] . '"';
                break;
            case 1:
                $search = $this->searchWordSafe($this->safe($search));
                $searchStr = '*' . $search[0] . '*';
                break;
            case 3:
                $search = $this->searchWordSafe($this->safe($search));
                $searchStr = implode(' ', $search);
                break;
            case 4:
                $search2 = [];
                for ($i = 0; $i < count($search); $i++) {
                    $nowSearchArr = $search[$i];
                    $modeChar = ['-', '~', '+', ''];
                    $nowSearchStr = $nowSearchArr[1];
                    $nowSearchStr = $this->searchWordSafe($this->safe([$nowSearchStr]));
                    array_push($search2, $modeChar[$nowSearchArr[0] + 1] . '"' . $nowSearchStr . '"');
                }
                $searchStr = implode(' ', $search2);
                break;
            default:
                $search = $this->searchWordSafe($this->safe($search));
                $searchStr = $search[0];
                break;
        }
        $orderstr = "";
        if (count($order) > 0) {
            $orderstr = " ORDER BY `" . $order[0] . "`";
            if ($order[1] === true) $orderstr .= " DESC";
        }
        if (count($limit) > 0) {
            $orderstr .= " limit ";
            if (count($limit) > 1) {
                $orderstr .= strval($limit[0]) . "," . strval($limit[1]);
            } else {
                $orderstr .= strval($limit[0]);
            }
        }
        $sqlcmd = "SELECT " . $columnStr . " FROM `" . $tableStr . "` WHERE MATCH (" . $searchColumnStr . ") AGAINST ('" . $searchStr . "' IN " . $nbMode . " MODE) ".$orderstr.";";
        return $this->sqlc($sqlcmd);
    }

    /**
     * @description: 全文搜尋的關鍵字過濾
     * @param Array<String> words 要過濾的字串或字串陣列
     * @param String 是否將空格也過濾掉
     * @return Array<String> 已經經過過濾的字串陣列
     */
    function searchWordSafe(array $words, bool $noSpace = true): array {
        for ($i = 0; $i < count($words); $i++) {
            $word = $words[$i];
            if (strlen($word) > 0) {
                $newWord = str_replace(['+', '-', '~', '"', "'"], '', substr($word, 0, 1)) . substr($word, 1);
                if ($noSpace) $newWord = str_replace(' ', '', $newWord);
                $words[$i] = $newWord;
            }
        }
        return $words;
    }

    /**
     * @description: 測試SQL連接
     * @return String mysql版本號
     */
    function sqltest() {
        $serinfo = mysqli_get_server_info($this->con);
        return $serinfo;
    }

    /**
     * @description: 執行SQL連接
     * @param String sqlcmd SQL語句
     * @return Array [Int,Int,Array,Int,String] 0狀態碼,1新建的ID,2返回的數據,3受影響的行數,4所使用的SQL語句
     */
    function sqlc($sqlcmd) {
        global $nlcore;
        $this->log("[QUERY] " . $sqlcmd);
        $result = mysqli_query($this->con, $sqlcmd);
        $rowaffected = mysqli_affected_rows($this->con);
        $this->log("[AFFRCT] " . $rowaffected);
        if ($result) {
            $insertid = mysqli_insert_id($this->con);
            if (@mysqli_num_rows($result)) {
                $result_array = array();
                $rowi = 0;
                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    $result_array[$rowi] = $row;
                    $rowi++;
                }
                if ($result_array) {
                    if (count($result_array) > 0) {
                        $this->log("[INFO] CODE:1010000, ID:" . $insertid);
                        $this->log("[RESULT] " . json_encode($result_array, true));
                        return [1010000, $insertid, $result_array, $rowaffected, $sqlcmd];
                    } else {
                        $this->log("[ERROR] arraycount == 0");
                        die($nlcore->msg->m(1, 2010102));
                    }
                } else {
                    $this->log("[ERROR] arraycount == null");
                    die($nlcore->msg->m(1, 2010104));
                }
            } else {
                $this->log("[INFO] CODE:1010001, ID:" . $insertid);
                $this->log("[RESULT] (null)");
                return [1010001, $insertid, null, $rowaffected, $sqlcmd];
            }
        } else if (mysqli_connect_errno($this->con)) {
            $this->log("[ERROR] mysqli_connect_error: " . mysqli_connect_error());
            die($nlcore->msg->m(1, 2010101));
        } else {
            if ($rowaffected <= 0) {
                $this->log("[ERROR] mysqli_connect_error.");
                if (self::SQL_DEBUG) die($nlcore->msg->m(1, 2010106, $sqlcmd));
                die($nlcore->msg->m(1, 2010106));
            } else {
                $this->log("[WARN] no_result: " . $result . mysqli_connect_error());
                // die($nlcore->msg->m(1,2010105));
                return [1010004, null, null, $rowaffected, $sqlcmd];
            }
        }
    }

    /**
     * @description: 將字典類型轉換為SQL語句
     * @param Dictionary [String:String] dic 要轉換的字典 ["表名"=>"列名"]
     *     key 中如果包含「.」，則視為 `表名`.`列名`
     *     key 中如果包含「*」，「*」及之後內容將被捨棄。用於處理要包括同名 key 的需要。
     *     val 中如果以「$」开头，则視為表示式，只用於 mode 1 。注意检查安全。
     * @param Int mode 返回字符串的格式
     *     0:  (列1, 列2) VALUES (值1, 值2)
     *     1:  `列1`='值1', `列2`='值2'
     *     2:  `列1`='值1' AND `列2`='值2'
     *     3:  `列1`='值1' OR `列2`='值2'
     *     4:  `列1` IN (值1, 值2)  *需要特殊字典格式：["列"=>[值1,值2]]
     * @param Boolean islike 模糊搜素
     * @return String 返回 SQL 語句片段，如果不提供要轉換的字典(null)，則返回通用「*」
     */
    function dic2sql($dic = null, $mode = 0, $islike = false) {
        if ($dic === null) return "*";
        else if (count($dic) == 0) return "";
        else if (strcmp(current($dic), "*") == 0 || strlen(current($dic)) == 0) return $dic[0];
        $dicKey = array_keys($dic);
        for ($i = 0; $i < count($dicKey); $i++) {
            $nowKey = $dicKey[$i];
            $nowVal = $dic[$nowKey];
            if (is_array($nowVal)) {
                global $nlcore;
                $nowVal = $nlcore->safe->multiAarray2array($nowVal);
                $dic[$nowKey] = implode("", $nowVal);
            }
        }
        if ($mode == 0) {
            $keys = "";
            $vals = "";
            foreach ($dic as $key => $val) {
                $tckey = explode(".", $key);
                if (count($tckey) > 1) {
                    $keys .= "`" . $tckey[0] . "`.`" . explode("*", $tckey[1])[0] . "`, ";
                } else {
                    $keys .= "`" . explode("*", $key)[0] . "`, ";
                }
                if ($val !== null) {
                    $vals .= "'" . $val . "', ";
                } else {
                    $vals .= "NULL, ";
                }
            }
            $keystr = substr($keys, 0, -2);
            $valstr = substr($vals, 0, -2);
            return "(" . $keystr . ") VALUES (" . $valstr . ")";
        } else if ($mode == 4) {
            foreach ($dic as $key => $value) {
                $tckey = explode(".", $key);
                if (count($tckey) > 1) {
                    return "`" . $tckey[0] . "`.`" . $tckey[1] . "` IN ('" . implode("','", $value) . "')";
                } else {
                    return "`" . $key . "` IN ('" . implode("','", $value) . "')";
                }
            }
        } else {
            $like = "=";
            if ($islike) $like = "LIKE";
            $modestr = ", ";
            if ($mode == 2) {
                $modestr = " AND ";
            } else if ($mode == 3) {
                $modestr = " OR ";
            }
            $modestrlen = strlen($modestr);
            $keyval = "";
            foreach ($dic as $key => $val) {
                $tckey = explode(".", $key);
                if (count($tckey) > 1) {
                    $keyc = "`" . $tckey[0] . "`.`" . explode("*", $tckey[1])[0] . "`";
                } else {
                    $keyc = "`" . explode("*", $key)[0] . "`";
                }
                if ($val !== null) {
                    $valqm = "'";
                    if (strlen($val) > 0 && substr($val, 0, 1) === "\$") {
                        $val = substr($val, 1);
                        $valqm = "";
                    }
                    $keyval .= $keyc . " " . $like . " " . $valqm . $val . $valqm . $modestr;
                } else {
                    if ($mode == 2) {
                        $keyval .= $keyc . " IS NULL" . $modestr;
                    } else {
                        $keyval .= $keyc . " " . $like . " NULL" . $modestr;
                    }
                }
            }
            return substr($keyval, 0, (0 - $modestrlen));
        }
    }

    /**
     * @description: 初始化 Redis 資料庫
     * @return Bool true:正常 false:功能禁用 die:失敗
     */
    function initRedis() {
        if ($this->redis) return true;
        global $nlcore;
        $redisconf = $nlcore->cfg->db->redis;
        if (!$redisconf["rdb_enable"]) return false;
        $appconf = $nlcore->cfg->app;
        if (!class_exists("Redis")) {
            die($nlcore->msg->m(1, 2010200));
        }
        $this->redis = new Redis();
        try {
            $this->redis->connect($redisconf["rdb_host"], $redisconf["rdb_port"]);
        } catch (Exception $e) {
            $this->redis = null;
            die($nlcore->msg->m(1, 2010201));
        }
        if ($redisconf["rdb_password"] != "" && !$this->redis->auth($redisconf["rdb_password"])) {
            $this->redis = null;
            die($nlcore->msg->m(1, 2010202));
        }
        $this->redis->select($redisconf["rdb_id"]);
        return true;
    }

    /**
     * @description: 結束連接
     */
    function close() {
        if ($this->conR) {
            $this->log("[CLOSE] read-only mode.");
            mysqli_close($this->conR);
            $this->conR = null;
        }
        if ($this->conW) {
            $this->log("[CLOSE] read-write mode.");
            mysqli_close($this->conW);
            $this->conW = null;
        }
        if ($this->redis) {
            $this->log("[CLOSE] redis.");
            $this->redis->close();
            $this->redis = null;
        }
    }

    /**
     * @description: 析構，結束連接，關閉日誌文件
     */
    function __destruct() {
        $this->close();
        unset($this->conR);
        unset($this->conW);
        unset($this->redis);
        $this->con = null;
        unset($this->con);
        $this->mode = null;
        unset($this->mode);
        if ($this->logfile) {
            fclose($this->logfile);
            $this->logfile = null;
        }
        unset($this->logfile);
    }
}
