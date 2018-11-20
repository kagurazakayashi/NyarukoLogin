<?php
    class nyadbconnect {
        //TODO:RW
        private $conR = null; //只读数据库
        private $conW = null; //可写入数据库
        private $conK = null; //关键词数据库
        private $con = null; //当前数据库（指针变量）
        public $debug = true; //输出SQL语句和连接的建立与断开信息
        /**
         * @description: 初始化可写入数据库，按需建立SQL连接
         */
        function initWriteDbs() {
            global $nlcore;
            if ($this->debug) echo "[SQL-W]";
            if (!$this->conW) $this->conW = $this->initMysqli($nlcore->cfg->db->write_dbs);
            $this->con = &$this->conW;
        }
        /**
         * @description: 初始化只读数据库，按需建立SQL连接
         */
        function initReadDbs() {
            global $nlcore;
            if ($this->debug) echo "[SQL-R]";
            if (!$this->conR) $this->conR = $this->initMysqli($nlcore->cfg->db->read_dbs);
            $this->con = &$this->conR;
        }
        /**
         * @description: 初始化关键词数据库，按需建立SQL连接
         */
        function initStopwordDbs() {
            global $nlcore;
            if ($this->debug) echo "[SQL-K]";
            if (!$this->conK) $this->conK = $this->initMysqli($nlcore->cfg->db->stopword_dbs);
            $this->con = &$this->conK;
        }
        /**
         * @description: 初始化数据库
         * @param String selectdbs 数据库配置数组($nlcore->cfg->db->*)
         * @return mysqli_connect 数据库连接对象
         */
        function initMysqli($selectdbs) {
            $selectdbscount = count($selectdbs);
            if ($selectdbscount > 0) {
                $dbid = rand(0, $selectdbscount-1);
                $selectdb = $selectdbs[$dbid];
                $newcon = mysqli_connect($selectdb["db_host"],$selectdb["db_user"],$selectdb["db_password"],$selectdb["db_name"],$selectdb["db_port"]);
                $sqlerrno = mysqli_connect_errno($newcon);
                if ($sqlerrno) {
                    die($nlcore->msg->m(2010100,true,$sqlerrno));
                }
                return $newcon;
            } else {
                die($nlcore->msg->m(2010103,true,$sqlerrno));
            }
            return null;
        }
        /**
         * @description: 查询数据
         * @param Array<String> selectArr 要查询的列名数组
         * @param String tableStr 表名
         * @param String whereDic 条件字典（k:列名=v:预期内容）
         * @param String customWhere 自定义条件表达式
         * @param String whereMode 条件判断模式（AND/OR/...）
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function select($columnArr,$tableStr,$whereDic,$customWhere="",$whereMode="AND") {
            $this->initReadDbs();
            $columnStr = implode('`,`',$columnArr);
            $whereStr = $this->dic2sql($whereDic,2);
            if ($customWhere != "" && $whereDic) $customWhere = " ".$wheremode." ".$customWhere;
            $sqlcmd = "SELECT `".$columnStr."` FROM `".$tableStr."` WHERE ".$whereStr.$customWhere.";";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 插入数据
         * @param Array<String:String> insertDic 要插入的数据字典
         * @param String tableStr 表名
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function insert($insertDic,$tableStr) {
            $this->initWriteDbs();
            $insertStr = $this->dic2sql($insertDic,0);
            $sqlcmd = "INSERT INTO `".$tableStr."` ".$insertStr.";";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 更新数据
         * @param Array<String:String> updateDic 要更新的数据字典
         * @param String tableStr 表名
         * @param Array<String:String> whereDic 条件字典（k:列名=v:预期内容）
         * @param String customWhere 自定义条件表达式
         * @param String whereMode 条件判断模式（AND/OR/...）
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function update($updateDic,$tableStr,$whereDic,$customWhere="",$whereMode="AND") {
            $this->initWriteDbs();
            $update = $this->dic2sql($updateDic,1);
            $whereStr = $this->dic2sql($whereDic,2);
            if ($customWhere != "" && $whereDic) $customWhere = " ".$wheremode." ".$customWhere;
            $sqlcmd = "UPDATE `".$tableStr."` SET ".$update." WHERE ".$whereStr.$customWhere.";";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 删除数据
         * @param String tableStr 表名
         * @param Array<String:String> whereDic 条件字典（k:列名=v:预期内容）
         * @param String customWhere 自定义条件表达式
         * @param String whereMode 条件判断模式（AND/OR/...）
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function delete($tableStr,$whereDic,$customWhere="",$whereMode="AND") {
            $this->initWriteDbs();
            $whereStr = $this->dic2sql($whereDic,2);
            if ($customWhere != "" && $whereDic) $customWhere = " ".$wheremode." ".$customWhere;
            $sqlcmd = "DELETE FROM `".$tableStr."` WHERE ".$whereStr.$customWhere.";";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 查询有多少数据
         * @param String tableStr 表名
         * @param Array<String:String> whereDic 条件字典（k:列名=v:预期内容）
         * @param String customWhere 自定义条件表达式
         * @param String whereMode 条件判断模式（AND/OR/...）
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function scount($tableStr,$whereDic=null,$customWhere="",$whereMode="AND") {
            $this->initReadDbs();
            $whereStr = $this->dic2sql($whereDic,2);
            if ($customWhere != "" && $whereDic) $customWhere = " ".$wheremode." ".$customWhere;
            $sqlcmd = "select count(*) from `".$tableStr."` WHERE ".$whereStr.$customWhere.";";
            return $this->sqlc($sqlcmd);
        }
        //
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
        function close() {
            if ($this->conR) {
                if ($this->debug) echo "[/SQL-R]";
                mysqli_close($this->conR);
                $this->conR = null;
            }
            if ($this->conW) {
                if ($this->debug) echo "[/SQL-W]";
                mysqli_close($this->conW);
                $this->conW = null;
            }
            if ($this->conK) {
                if ($this->debug) echo "[/SQL-K]";
                mysqli_close($this->conK);
                $this->conK = null;
            }
        }
        /**
         * @description: 执行SQL连接
         * @param String sqlcmd SQL语句
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function sqlc($sqlcmd) {
            global $nlcore;
            if ($this->debug) echo "[SQL]".$sqlcmd."[/SQL]";
            $result = mysqli_query($this->con,$sqlcmd);
            if ($result) {
                $insertid = mysqli_insert_id($this->con);
                if(@mysqli_num_rows($result)) {
                    $result_array = array();
                    $rowi = 0;
                    while ($row = mysqli_fetch_array($result)) {
                        $result_array[$rowi] = $row;
                        $rowi++;
                    }
                    if($result_array) {
                        if (count($result_array) > 0) {
                            return [1010000,$insertid,$result_array];
                        } else {
                            die($nlcore->msg->m(2010102));
                        }
                    }
                } else {
                    return [1010001,$insertid];
                }
            } else {
                if ($this->debug) echo "[SQLERR]".mysqli_connect_error()."[/SQLERR]";
                die($nlcore->msg->m(2010101));
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
                    $vals .= "'".$val."', ";
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
                    $keyval .= "`".$key."` = '".$val."'".$modestr;
                }
                return substr($keyval, 0, (0-$modestrlen));
            }
        }
        /**
         * @description: 析构，结束SQL连接
         */
        function __destruct() {
            $this->close();
            $this->mode = null;
            $this->debug = null;
            unset($this->con);
            unset($this->mode);
            unset($this->debug);
        }
    }
?>