<?php
    class nyadbconnect {
        private $conR = null; //只读数据库
        private $conW = null; //可写入数据库
        private $con = null; //当前 MySQL 数据库（指针变量）
        private $logtofile = false; //记录详细调试信息到文件
        public $redis = null; //当前 Redis 数据库
        /**
         * @description: 初始化可写入数据库，按需建立SQL连接
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
         * @description: 初始化只读数据库，按需建立SQL连接
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
         * @description: 初始化数据库
         * @param String selectdbs 数据库配置数组($nlcore->cfg->db->*)
         * @return mysqli_connect 数据库连接对象
         */
        function initMysqli($selectdbs) {
            global $nlcore;
            $selectdbscount = count($selectdbs);
            if ($selectdbscount > 0) {
                //TODO: 使用Redis进行顺序式数据库选择
                $dbid = rand(0, $selectdbscount-1);
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
         * @description: 清理提交数据中的注入语句
         * @param String/Array data 要进行清理的内容，支持多维数组、字符串，其他类型（如 int）不清理
         * @return String/Array 清理后的数组/字符串
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
         * @description: 将每条SQL语句和返回内容记录在日志文件中，通过 nyaconfig 中的此项设置来进行调试。
         * @param String logstr 要记录的字符串
         */
        function log($logstr) {
            global $nlcore;
            if (!isset($nlcore->cfg->db->logfile)) return;
            $logfile = $nlcore->cfg->db->logfile;
            if ($logfile) {
                $ipaddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
                $proxyaddr = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? "@".$_SERVER['HTTP_X_FORWARDED_FOR'] : "";
                $logstr = "[".$nlcore->safe->getdatetime()[1]."][".$ipaddr.$proxyaddr."]".$logstr.PHP_EOL;
                $fp = fopen($logfile,"a");
                fwrite($fp,$logstr.PHP_EOL);
                fclose($fp);
            }
        }
        /**
         * @description: 查询数据
         * @param Array<String> columnArr 要查询的列名数组
         * @param String tableStr 表名
         * @param String whereDic 条件字典（k:列名=v:预期内容）
         * @param String customWhere 自定义条件表达式（可选，默认空）
         * @param String whereMode 条件判断模式（AND/OR/...，可选，默认AND）
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function select($columnArr,$tableStr,$whereDic,$customWhere="",$whereMode="AND") {
            $this->initReadDbs();
            $columnArr = $this->safe($columnArr);
            $whereDic = $this->safe($whereDic);
            $columnStr = implode('`,`',$columnArr);
            $whereStr = $this->dic2sql($whereDic,2);
            if ($customWhere != "" && $whereDic) $customWhere = " ".$wheremode." ".$customWhere;
            $sqlcmd = "SELECT `".$columnStr."` FROM `".$tableStr."` WHERE ".$whereStr.$customWhere.";";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 插入数据
         * @param String tableStr 表名
         * @param Array<String:String> insertDic 要插入的数据字典
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function insert($tableStr,$insertDic) {
            $this->initWriteDbs();
            $insertDic = $this->safe($insertDic);
            $insertStr = $this->dic2sql($insertDic,0);
            $sqlcmd = "INSERT INTO `".$tableStr."` ".$insertStr.";";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 更新数据
         * @param Array<String:String> updateDic 要更新的数据字典
         * @param String tableStr 表名
         * @param Array<String:String> whereDic 条件字典（k:列名=v:预期内容）
         * @param String customWhere 自定义条件表达式（可选，默认空）
         * @param String whereMode 条件判断模式（AND/OR/...，可选，默认AND）
         * @return Array<Int,Array> 返回的状态码和内容
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
         * @description: 如果有则更新数据，没有则插入数据
         * @param String tableStr 表名
         * @param Array<String:String> dataDic 要更新或插入的数据字典
         * @param Array<String:String> whereDic 条件字典（k:列名=v:预期内容）
         * @param String customWhere 自定义条件表达式（可选，默认空）
         * @param String whereMode 条件判断模式（AND/OR/...，可选，默认AND）
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function updateinsert($tableStr,$dataDic,$whereDic=null,$customWhere="",$whereMode="AND") {
            $result = $this->scount($tableStr,$whereDic,$customWhere,$whereMode);
            if ($result[0] >= 2000000) return [$result[0]];
            $datacount = $result[2][0][0];
            if ($datacount == 0) {
                return $this->insert($tableStr,$dataDic);
            } else if ($datacount == 1) {
                return $this->update($dataDic,$tableStr,$whereDic,$customWhere,$whereMode);
            } else {
                return [2010200];
            }
        }
        /**
         * @description: 删除数据
         * @param String tableStr 表名
         * @param Array<String:String> whereDic 条件字典（k:列名=v:预期内容）
         * @param String customWhere 自定义条件表达式（可选，默认空）
         * @param String whereMode 条件判断模式（AND/OR/...，可选，默认AND）
         * @return Array<Int,Array> 返回的状态码和内容
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
         * @description: 查询有多少数据
         * @param String tableStr 表名
         * @param Array<String:String> whereDic 条件字典（k:列名=v:预期内容）
         * @param String customWhere 自定义条件表达式（可选，默认空）
         * @param String whereMode 条件判断模式（AND/OR/...，可选，默认AND）
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function scount($tableStr,$whereDic=null,$customWhere="",$whereMode="AND") {
            $this->initReadDbs();
            $whereDic = $this->safe($whereDic);
            $whereStr = $this->dic2sql($whereDic,2);
            if ($customWhere != "" && $whereDic) $customWhere = " ".$wheremode." ".$customWhere;
            $sqlcmd = "select count(*) from `".$tableStr."` WHERE ".$whereStr.$customWhere.";";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 测试SQL连接
         * @return String mysql版本号
         */
        function sqltest() {
            $serinfo = mysqli_get_server_info($this->con);
            return $serinfo;
        }
        /**
         * @description: 结束SQL连接
         */
        function closemysql() {
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
        }
        /**
         * @description: 执行SQL连接
         * @param String sqlcmd SQL语句
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function sqlc($sqlcmd) {
            global $nlcore;
            $this->log("[QUERY] ".$sqlcmd);
            $result = mysqli_query($this->con,$sqlcmd);
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
                            $this->log("[RESULT] ".var_export($result_array,true));
                            return [1010000,$insertid,$result_array];
                        } else {
                            $this->log("[ERROR] arraycount == 0");
                            die($nlcore->msg->m(1,2010102));
                        }
                    }
                } else {
                    $this->log("[INFO] CODE:1010001, ID:".$insertid);
                    $this->log("[RESULT] (null)");
                    return [1010001,$insertid];
                }
            } else {
                $this->log("[ERROR] ".mysqli_connect_error());
                die($nlcore->msg->m(1,2010101));
            }
        }
        /**
         * @description: 将字典类型转换为SQL语句
         * @param Dictionary<String:String> dic 要转换的字典
         * @param Int mode 返回字符串的格式
         *     0; (列1, 列2) VALUES (值1, 值2)
         *     1: `列1`='值1', `列2`='值2'
         *     2: `列1`='值1' AND `列2`='值2'
         *     3: `列1`='值1' OR `列2`='值2'
         * @return String 返回 SQL 语句片段，如果不提供要转换的字典(null)，则返回通用*号
         */
        function dic2sql($dic=null,$mode=0) {
            if ($dic == null) return "*";
            if ($mode == 0) {
                $keys = "";
                $vals = "";
                foreach ($dic as $key => $val) {
                    $keys .= "`".$key."`, ";
                    if ($val) {
                        $vals .= "'".$val."', ";
                    } else {
                        $vals .= "NULL, ";
                    }
                }
                $keystr = substr($keys, 0, -2);
                $valstr = substr($vals, 0, -2);
                return "(".$keystr.") VALUES (".$valstr.")";
            } else {
                $modestr = ", ";
                if ($mode == 2) {
                    $modestr = " AND ";
                } else if ($mode == 3) {
                    $modestr = " OR ";
                }
                $modestrlen = strlen($modestr);
                $keyval = "";
                foreach ($dic as $key => $val) {
                    if ($val) {
                        $keyval .= "`".$key."` = '".$val."'".$modestr;
                    } else {
                        $keyval .= "`".$key."` = NULL".$modestr;
                    }
                }
                return substr($keyval, 0, (0-$modestrlen));
            }
        }
        /**
         * @description: 初始化 Redis 数据库
         * @return Bool true:正常 false:功能禁用 die:失败
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
            if (!$this->redis->auth($redisconf["rdb_password"])) {
                $this->redis = null;
                die($nlcore->msg->m(1,2010202));
            }
            $this->redis->select($redisconf["rdb_id"]);
            return true;
        }
        /**
         * @description: 析构，结束连接
         */
        function __destruct() {
            $this->closemysql();
            $this->con = null; unset($this->con);
            $this->mode = null; unset($this->mode);
            $this->redis = null; unset($this->redis);
            $this->logtofile = null; unset($this->logtofile);
        }
    }
?>
