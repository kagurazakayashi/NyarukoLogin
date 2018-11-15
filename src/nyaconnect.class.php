<?php
    class nyadbconnect {
        private $con = null;
        private $mode = 0;
        private $debug = false;
        public $tables;
        /**
         * @description: 初始化，读入设置，建立SQL连接
         * @param Int mode 选择数据库：0只读库 1可写入库 2屏蔽词库
         */
        function init($mode=0,$debug=false) {
            global $nlcore;
            $selectdbs;
            $this->mode = $mode;
            $this->debug = $debug;
            switch ($mode) {
                case 1:
                    $selectdbs = $nlcore->cfg->db->write_dbs;
                    break;
                case 2:
                    $selectdbs = $nlcore->cfg->db->stopword_dbs;
                    break;
                default:
                    $selectdbs = $nlcore->cfg->db->read_dbs;
                    break;
            }
            $tables = $nlcore->cfg->db->tables;
            $selectdbscount = count($selectdbs);
            if ($selectdbscount > 0) {
                $dbid = rand(0, $selectdbscount-1);
                $selectdb = $selectdbs[$dbid];
                if (!$this->con) {
                    $this->con = mysqli_connect($selectdb["db_host"],$selectdb["db_user"],$selectdb["db_password"],$selectdb["db_name"],$selectdb["db_port"]);
                    if ($this->debug) echo "打开数据库连接。";
                }
                $sqlerrno = mysqli_connect_errno($this->con);
                if ($sqlerrno) {
                    die($nlcore->msg->m(2010100,true,$sqlerrno));
                }
            } else {
                die($nlcore->msg->m(2010103,true,$sqlerrno));
            }
        }
        /**
         * @description: 查询数据
         * @param Array<String> selectArr 要查询的列名数组
         * @param String tableStr 表名
         * @param String whereDic 条件字典（k:列名=v:预期内容）
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function select($columnArr,$tableStr,$whereDic) {
            $columnStr = implode('`,`',$columnArr);
            $whereStr = $this->dic2sql($whereDic,2);
            $sqlcmd = "SELECT `".$columnStr."` FROM `".$tableStr."` WHERE ".$whereStr.";";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 插入数据
         * @param Dictionary<String:String> insertDic 要插入的数据字典
         * @param String tableStr 表名
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function insert($insertDic,$tableStr) {
            $insertStr = $this->dic2sql($insertDic,0);
            $sqlcmd = "INSERT INTO `".$tableStr."` ".$insertStr.";";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 更新数据
         * @param Dictionary<String:String> updateDic 要更新的数据字典
         * @param String tableStr 表名
         * @param String whereDic 条件字典（k:列名=v:预期内容）
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function update($updateDic,$tableStr,$whereDic) {
            $update = $this->dic2sql($updateDic,1);
            $whereStr = $this->dic2sql($whereDic,2);
            $sqlcmd = "UPDATE `".$tableStr."` SET ".$update." WHERE ".$whereStr.";";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 删除数据
         * @param String tableStr 表名
         * @param String whereDic 条件字典（k:列名=v:预期内容）
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function delete($tableStr,$whereDic) {
            $whereStr = $this->dic2sql($whereDic,2);
            $sqlcmd = "DELETE FROM `".$tableStr."` WHERE ".$whereStr.";";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 查询有多少数据
         * @param String tableStr 表名
         * @param String whereDic 条件字典（k:列名=v:预期内容）
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function scount($tableStr,$whereDic=null) {
            $whereStr = $this->dic2sql($whereDic,2);
            $sqlcmd = "select count(*) from ".$tableStr."` WHERE ".$whereStr.";";
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
            if ($this->con) {
                mysqli_close($this->con);
                $this->con = null;
                if ($this->debug) echo "关闭数据库连接。";
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
                if(@mysqli_num_rows($result)) {
                    $result_array = array();
                    $rowi = 0;
                    while ($row = mysqli_fetch_array($result)) {
                        $result_array[$rowi] = $row;
                        $rowi++;
                    }
                    if($result_array) {
                        if (count($result_array) > 0) {
                            return [1100,$result_array];
                        } else {
                            die($nlcore->msg->m(2010102));
                        }
                    }
                } else {
                    return [1101]; //SQL语句成功执行，返回0值。
                }
            } else {
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
                while(list($key,$val) = each($dic)) { 
                    $keys += "`".$key."`, ";
                    $vals += "'".$val."', ";
                }
                $keystr = substr($keys, 0, -2);
                $valstr = substr($vals, 0, -2);
                return "(".$keystr.") VALUES (".$valstr.");";
            } else {
                $modestr = ", ";
                if ($modestr == 2) {
                    $modestr = " AND ";
                } else if ($modestr == 3) {
                    $modestr = " OR ";
                }
                $modestrlen = strlen($modestr);
                $keyval = "";
                while(list($key,$val) = each($dic)) {
                    $keyval += "`".$key."` = '".$val."'".$modestr;
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
            $this->tables = null;
            $this->debug = null;
            unset($this->con);
            unset($this->mode);
            unset($this->tables);
            unset($this->debug);
        }
    }
?>