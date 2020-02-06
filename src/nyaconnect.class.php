<?php
/**
 * @description: MySQL/Redis 資料庫語句生成和連接管理類
 * @package NyarukoLogin
*/
    class nyadbconnect {
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
                mysqli_set_charset($this->conW,$nlcore->cfg->db->charset);
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
                mysqli_set_charset($this->conR,$nlcore->cfg->db->charset);
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
                        if($redis->exists($key)){
                            $dbid = intval($redis->get($key));
                            if ($dbid > $selectdbscount) {
                                $redis->set($key,0);
                            } else {
                                $dbid++;
                                $redis->incr($key);
                            }
                        } else {
                            $redis->set($key,0);
                        }
                    } else {
                        $dbid = rand(0, $selectdbscount);
                    }
                }
                $selectdb = $selectdbs[$dbid];
                $this->log("[CONNECT] ".$selectdb["db_user"]."@".$selectdb["db_host"].":".$selectdb["db_port"]."/".$selectdb["db_name"]);
                $newcon = mysqli_connect($selectdb["db_host"],$selectdb["db_user"],$selectdb["db_password"],$selectdb["db_name"],$selectdb["db_port"]);
                $sqlerrno = mysqli_connect_errno($newcon);
                if ($sqlerrno) {
                    $this->log("[ERROR] ".$sqlerrno);
                    die($nlcore->msg->m(1,2010100,$sqlerrno));
                }
                return $newcon;
            } else {
                $this->log("[ERROR] ".$sqlerrno);
                die($nlcore->msg->m(1,2010103,$sqlerrno));
            }
            return null;
        }

        /**
         * @description: 清理提交數據中的註入語句
         * @param String/Array data 要進行清理的內容，支援多維數組、字符串，其他類型（如 int）不清理
         * @return String/Array 清理後的數組/字符串
         */
        function safe($data) {
            $newdata;
            if (is_array($data)) {
                $newdata = [];
                foreach ($data as $key => $value) {
                    $newdata[$key] = $this->safe($value);
                }
            } else if (is_string($data)) {
                $newdata = mysqli_real_escape_string($this->con,$data);
            } else {
                $newdata = $data;
            }
            return $newdata;
        }

        /**
         * @description: 將每條SQL語句和返回內容記錄在日誌文件中，通過 nyaconfig 中的此項設定來進行調試。
         * @param String logstr 要記錄的字符串
         */
        function log($logstr) {
            global $nlcore;
            if (!isset($nlcore->cfg->db->logfile_db) || $nlcore->cfg->db->logfile_db == null || $nlcore->cfg->db->logfile_db == "") return;
            $logfilepath = $nlcore->cfg->db->logfile_db;
            if ($logfilepath) {
                $ipaddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
                $proxyaddr = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? "@".$_SERVER['HTTP_X_FORWARDED_FOR'] : "";
                $logstr = "[".$nlcore->safe->getdatetime()[1]."][".$ipaddr.$proxyaddr."]".$logstr.PHP_EOL;
                if (!$this->logfile) $this->logfile = fopen($logfilepath,"a");
                fwrite($this->logfile,$logstr);
            }
        }

        /**
         * @description: 查詢數據
         * @param Array<String> columnArr 要查詢的列名數組
         * @param String tableStr 錶名
         * @param String whereDic 條件字典（k:列名=v:預期內容）
         * @param String customWhere 自定義條件錶達式（可選，預設空，不走安全檢查註意）
         * @param String whereMode 條件判斷模式（AND/OR/...，可選，預設AND）
         * @param Array<String,Bool> order 排序方式[排序依據,是否倒序]
         * @param Int/Array<Int,Int> limit 區間。純數字為前x條，用數組則為[從多少,取多少]
         * @param Boolean islike 模糊搜素（可選，預設關）
         * @return Array<Int,Array> 返回的狀態碼和內容
         */
        function select($columnArr,$tableStr,$whereDic,$customWhere="",$whereMode="AND",$islike=false,$order=null,$limit=null) {
            $this->initReadDbs();
            $columnArr = $this->safe($columnArr);
            $whereDic = $this->safe($whereDic);
            $columnStr = implode('`,`',$columnArr);
            $whereStr = "";
            if ($whereMode == "IN") {
                $whereStr = $this->dic2sql($whereDic,4,$islike);
            } else {
                $whereStr = $this->dic2sql($whereDic,2,$islike);
            }
            if ($customWhere != "" && $whereDic) $customWhere = " ".$whereMode." ".$customWhere;
            $orderstr = "";
            if ($order) {
                $orderstr = " order by ".$order[0];
                if ($order[1] === true) $orderstr.= " desc";
            }
            if ($limit) {
                $orderstr.= " limit ";
                if (is_array($limit)) {
                    $orderstr.= strval($limit[0]).",".strval($limit[1]);
                } else {
                    $orderstr.= strval($limit);
                }
            }
            $sqlcmd = "SELECT `".$columnStr."` FROM `".$tableStr."` WHERE ".$whereStr.$customWhere.$orderstr.";";
            return $this->sqlc($sqlcmd);
        }

        /**
         * @description: 插入數據
         * @param String tableStr 錶名
         * @param Array<String:String> insertDic 要插入的數據字典
         * @return Array<Int,Array> 返回的狀態碼和內容
         */
        function insert($tableStr,$insertDic) {
            $this->initWriteDbs();
            $insertDic = $this->safe($insertDic);
            $insertStr = $this->dic2sql($insertDic,0);
            $sqlcmd = "INSERT INTO `".$tableStr."` ".$insertStr.";";
            return $this->sqlc($sqlcmd);
        }

        /**
         * @description: 更新數據
         * @param Array<String:String> updateDic 要更新的數據字典
         * @param String tableStr 錶名
         * @param Array<String:String> whereDic 條件字典（k:列名=v:預期內容）
         * @param String customWhere 自定義條件錶達式（可選，預設空，不走安全檢查註意）
         * @param String whereMode 條件判斷模式（AND/OR/...，可選，預設AND）
         * @return Array<Int,Array> 返回的狀態碼和內容
         */
        function update($updateDic,$tableStr,$whereDic,$customWhere="",$whereMode="AND") {
            $this->initWriteDbs();
            $updateDic = $this->safe($updateDic);
            $whereDic = $this->safe($whereDic);
            $update = $this->dic2sql($updateDic,1);
            $whereStr = $this->dic2sql($whereDic,2);
            if ($customWhere != "" && $whereDic) $customWhere = " ".$wheremode." ".$customWhere;
            $sqlcmd = "UPDATE `".$tableStr."` SET ".$update." WHERE ".$whereStr.$customWhere.";";
            return $this->sqlc($sqlcmd);
        }

        /**
         * @description: 如果有則更新數據，冇有則插入數據
         * @param String tableStr 錶名
         * @param Array<String:String> dataDic 要更新或插入的數據字典
         * @param String customWhere 自定義條件錶達式（可選，預設空，不走安全檢查註意）
         * @param String whereMode 條件判斷模式（AND/OR/...，可選，預設AND）
         * @return Array<Int,Array> 返回的狀態碼和內容
         */
        function insertupdate($tableStr,$dataDic,$whereDic=null,$customWhere="",$whereMode="AND") {
            $result = $this->scount($tableStr,$dataDic,$customWhere,$whereMode);
            if ($result[0] >= 2000000) return [$result[0]];
            $datacount = $result[2][0][0];
            if ($datacount == 0) {
                return $this->insert($tableStr,$dataDic);
            } else if ($datacount == 1) {
                return $this->update($dataDic,$tableStr,$whereDic,$customWhere,$whereMode);
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
        function insertnull($tableStr,$dataDic,$customWhere="",$whereMode="AND") {
            $result = $this->scount($tableStr,$dataDic,$customWhere,$whereMode);
            if ($result[0] >= 2000000) return [$result[0]];
            $datacount = $result[2][0][0];
            if ($datacount == 0) {
                return $this->insert($tableStr,$dataDic);
            } else {
                return [1010002];
            }
        }

        /**
         * @description: 刪除數據
         * @param String tableStr 錶名
         * @param Array<String:String> whereDic 條件字典（k:列名=v:預期內容）
         * @param String customWhere 自定義條件錶達式（可選，預設空，不走安全檢查註意）
         * @param String whereMode 條件判斷模式（AND/OR/...，可選，預設AND）
         * @return Array<Int,Array> 返回的狀態碼和內容
         */
        function delete($tableStr,$whereDic,$customWhere="",$whereMode="AND") {
            $this->initWriteDbs();
            $whereDic = $this->safe($whereDic);
            $whereStr = $this->dic2sql($whereDic,2);
            if ($customWhere != "" && $whereDic) $customWhere = " ".$wheremode." ".$customWhere;
            $sqlcmd = "DELETE FROM `".$tableStr."` WHERE ".$whereStr.$customWhere.";";
            return $this->sqlc($sqlcmd);
        }

        /**
         * @description: 查詢有多少數據
         * @param String tableStr 錶名
         * @param Array<String:String> whereDic 條件字典（k:列名=v:預期內容）
         * @param String customWhere 自定義條件錶達式（可選，預設空，不走安全檢查註意）
         * @param String whereMode 條件判斷模式（AND/OR/...，可選，預設AND）
         * @param Boolean islike 模糊搜素（可選，預設關）
         * @return Array<Int,Array> 返回的狀態碼和內容
         */
        function scount($tableStr,$whereDic=null,$customWhere="",$whereMode="AND",$islike=false) {
            $this->initReadDbs();
            $whereDic = $this->safe($whereDic);
            $whereStr = $this->dic2sql($whereDic,2,$islike);
            if ($customWhere != "" && $whereDic) $customWhere = " ".$whereMode." ".$customWhere;
            $sqlcmd = "select count(*) from `".$tableStr."` WHERE ".$whereStr.$customWhere.";";
            return $this->sqlc($sqlcmd);
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
         * @return Array[Int,Int,Array,Int,String] 0狀態碼,1新建的ID,2返回的數據,3受影響的行數,4所使用的SQL語句
         */
        function sqlc($sqlcmd) {
            global $nlcore;
            $this->log("[QUERY] ".$sqlcmd);
            $result = mysqli_query($this->con,$sqlcmd);
            $rowaffected = mysqli_affected_rows($this->con);
            $this->log("[AFFRCT] ".$rowaffected);
            if ($result) {
                $insertid = mysqli_insert_id($this->con);
                if(@mysqli_num_rows($result)) {
                    $result_array = array();
                    $rowi = 0;
                    while ($row = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
                        $result_array[$rowi] = $row;
                        $rowi++;
                    }
                    if($result_array) {
                        if (count($result_array) > 0) {
                            $this->log("[INFO] CODE:1010000, ID:".$insertid);
                            $this->log("[RESULT] ".json_encode($result_array,true));
                            return [1010000,$insertid,$result_array,$rowaffected,$sqlcmd];
                        } else {
                            $this->log("[ERROR] arraycount == 0");
                            die($nlcore->msg->m(1,2010102));
                        }
                    } else {
                        $this->log("[ERROR] arraycount == null");
                        die($nlcore->msg->m(1,2010104));
                    }
                } else {
                    $this->log("[INFO] CODE:1010001, ID:".$insertid);
                    $this->log("[RESULT] (null)");
                    return [1010001,$insertid,null,$rowaffected,$sqlcmd];
                }
            } else if (mysqli_connect_errno($this->con)) {
                $this->log("[ERROR] mysqli_connect_error: ".mysqli_connect_error());
                die($nlcore->msg->m(1,2010101));
            } else {
                if ($rowaffected <= 0) {
                    $this->log("[ERROR] mysqli_connect_error.");
                    die($nlcore->msg->m(1,2010106));
                } else {
                    $this->log("[WARN] no_result: ".$result.mysqli_connect_error());
                    // die($nlcore->msg->m(1,2010105));
                    return [1010004,null,null,$rowaffected,$sqlcmd];
                }
            }
        }

        /**
         * @description: 將字典類型轉換為SQL語句
         * @param Dictionary<String:String> dic 要轉換的字典
         *     key 中如果包含「*」，「*」及之後內容將被捨棄。用於處理要包括同名 key 的需要。
         * @param Int mode 返回字符串的格式
         *     0:  (列1, 列2) VALUES (值1, 值2)
         *     1:  `列1`='值1', `列2`='值2'
         *     2:  `列1`='值1' AND `列2`='值2'
         *     3:  `列1`='值1' OR `列2`='值2'
         *     4:  `列1` IN (值1, 值2)  *需要特殊字典格式：["列"=>[值1,值2]]
         * @param Boolean islike 模糊搜素
         * @return String 返回 SQL 語句片段，如果不提供要轉換的字典(null)，則返回通用「*」
         */
        function dic2sql($dic=null,$mode=0,$islike=false) {
            if ($dic === null) return "*";
            else if (count($dic) == 0) return "";
            if ($mode == 0) {
                $keys = "";
                $vals = "";
                foreach ($dic as $key => $val) {
                    $keys .= "`".explode("*",$key)[0]."`, ";
                    if ($val !== null) {
                        $vals .= "'".$val."', ";
                    } else {
                        $vals .= "NULL, ";
                    }
                }
                $keystr = substr($keys, 0, -2);
                $valstr = substr($vals, 0, -2);
                return "(".$keystr.") VALUES (".$valstr.")";
            } else if ($mode == 4) {
                foreach ($dic as $key => $value) {
                    return "`".$key."` IN ('".implode("','", $value)."')";
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
                    $keyc = explode("*",$key)[0];
                    if ($val !== null) {
                        $keyval .= "`".$keyc."` ".$like." '".$val."'".$modestr;
                    } else {
                        if ($mode == 2) {
                            $keyval .= "`".$keyc."` IS NULL".$modestr;
                        } else {
                            $keyval .= "`".$keyc."` ".$like." NULL".$modestr;
                        }
                    }
                }
                return substr($keyval, 0, (0-$modestrlen));
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
                die($nlcore->msg->m(1,2010200));
            }
            $this->redis = new Redis();
            try {
                $this->redis->connect($redisconf["rdb_host"], $redisconf["rdb_port"]);
            } catch (Exception $e){
                $this->redis = null;
                die($nlcore->msg->m(1,2010201));
            }
            if ($redisconf["rdb_password"] != "" && !$this->redis->auth($redisconf["rdb_password"])) {
                $this->redis = null;
                die($nlcore->msg->m(1,2010202));
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
            $this->con = null; unset($this->con);
            $this->mode = null; unset($this->mode);
            if ($this->logfile) {
                fclose($this->logfile);
                $this->logfile = null;
            }
            unset($this->logfile);
        }
    }
?>
