<?php
    class nyadbconnect {
        private $con = null;
        private $db_host;
        private $db_port;
        private $db_name;
        private $db_user;
        private $db_password;
        public $tables;
        /**
         * @description: 初始化，读入设置
         * @param Int mode 选择数据库：0只读库 1可写入库 2屏蔽词库
         */
        function init($mode=0) {
            global $nya;
            $selectdbs;
            switch ($mode) {
                case 1:
                    $selectdbs = $nya->cfg->db->write_dbs;
                    break;
                case 2:
                    $selectdbs = $nya->cfg->db->stopword_dbs;
                    break;
                default:
                    $selectdbs = $nya->cfg->db->read_dbs;
                    break;
            }
            $tables = $nya->cfg->db->tables;
            $selectdbscount = count($selectdbs);
            if ($selectdbscount > 0) {
                $dbid = rand(0, $selectdbscount-1);
                $selectdb = $selectdbs[$dbid];
                $this->db_host = $selectdb["db_host"];
                $this->db_port = $selectdb["db_port"];
                $this->db_name = $selectdb["db_name"];
                $this->db_user = $selectdb["db_user"];
                $this->db_password = $selectdb["db_password"];
            } else {
                throw new Exception("还没有配置数据库设置。");
            }
        }
        /**
         * @description: 查询数据
         * @param Array<String> selectArr 要查询的列名
         * @param String tableStr 表名
         * @param String whereStr 条件
         * @param String whereIsStr 条件值
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function select($selectArr,$tableStr,$whereStr,$whereIsStr) {
            $sqlcmd = "SELECT `".implode('`,`',$selectArr)."` FROM `".$this->db_name."`.`".$tableStr."` WHERE `".$whereStr."` = '".$whereIsStr."';";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 插入数据
         * @param Dictionary<Key:String> insertDic 要插入的数据字典
         * @param String tableStr 表名
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function insert($insertDic,$tableStr) {
            $keys = "";
            $vals = "";
            while(list($key,$val) = each($insertDic)) { 
                $keys += "`".$key."`,";
                $vals += "'".$val."',";
            }
            $sqlcmd = "INSERT INTO `".$this->db_name."`.`".$tableStr."`(".substr($keys, 0, -1).") VALUES(".substr($vals, 0, -1).");";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 更新数据
         * @param Dictionary<Key:String> updateDic 要更新的数据字典
         * @param String tableStr 表名
         * @param String whereStr 条件
         * @param String whereIsStr 条件值
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function update($updateDic,$tableStr,$whereStr,$whereIsStr) {
            $update = "";
            while(list($key,$val) = each($updateDic)) { 
                $update += "`".$key."` = '".$val."', ";
            }
            $sqlcmd = "UPDATE `".$this->db_name."`.`".$tableStr."` SET ".substr($update, 0, -2)." WHERE `".$whereStr."` = '".$whereIsStr."';";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 删除数据
         * @param String tableStr 表名
         * @param String whereStr 条件
         * @param String whereIsStr 条件值
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function delete($tableStr,$whereStr,$whereIsStr) {
            $sqlcmd = "DELETE FROM `".$this->db_name."`.`".$tableStr."` WHERE `".$whereStr."` = '".$whereIsStr."';";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 查询有多少数据
         * @param String tableStr 表名
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function scount($tableStr) {
            $sqlcmd = "select count(*) from ".$tableStr;
            return $this->sqlc($sqlcmd);
        }
        //
        /**
         * @description: 测试SQL连接
         * @return String mysql版本号
         */
        function sqltest() {
            if (!$this->con) $this->con = mysqli_connect($this->db_host,$this->db_user,$this->db_password,$this->db_name,$this->db_port);
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
            }
        }
        /**
         * @description: 执行SQL连接
         * @param String sqlcmd SQL语句
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function sqlc($sqlcmd) {
            global $nya;
            if (!$this->con) $this->con = mysqli_connect($this->db_host,$this->db_user,$this->db_password,$this->db_name,$this->db_port);
            $sqlerrno = mysqli_connect_errno($this->con);
            if ($sqlerrno) {
                die($nya->msg->m(2100,true,$sqlerrno));
            }
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
                            die($nya->msg->m(2010102));
                        }
                    }
                } else {
                    return [1101]; //SQL语句成功执行，返回0值。
                }
            } else {
                die($nya->msg->m(2010101));
            }
        }
        /**
         * @description: 析构，结束SQL连接
         */
        function __destruct() {
            $this->close();
            unset($this->con);
        }
    }
?>