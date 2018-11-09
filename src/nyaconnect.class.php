<?php
    class nyadbconnect {
        /**
         * @description: 查询数据
         * @param Array<String> selectArr 要查询的列名
         * @param String tableStr 表名
         * @param String whereStr 条件
         * @param String whereIsStr 条件值
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function select($selectArr,$tableStr,$whereStr,$whereIsStr) {
            global $nya;
            $sqlcmd = "SELECT `".implode('`,`',$selectArr)."` FROM `".$nya->cfg->db->db_name."`.`".$tableStr."` WHERE `".$whereStr."` = '".$whereIsStr."';";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 插入数据
         * @param Dictionary<Key:String> insertDic 要插入的数据字典
         * @param String tableStr 表名
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function insert($insertDic,$tableStr) {
            global $nya;
            $keys = "";
            $vals = "";
            while(list($key,$val) = each($insertDic)) { 
                $keys += "`".$key."`,";
                $vals += "'".$val."',";
            }
            $sqlcmd = "INSERT INTO `".$nya->cfg->db->db_name."`.`".$tableStr."`(".substr($keys, 0, -1).") VALUES(".substr($vals, 0, -1).");";
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
            global $nya;
            $update = "";
            while(list($key,$val) = each($updateDic)) { 
                $update += "`".$key."` = '".$val."', ";
            }
            $sqlcmd = "UPDATE `".$nya->cfg->db->db_name."`.`".$tableStr."` SET ".substr($update, 0, -2)." WHERE `".$whereStr."` = '".$whereIsStr."';";
            return $this->sqlc($sqlcmd);
        }
        /**
         * @description: 查询有多少数据
         * @param String tableStr 表名
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function scount($tableStr) {
            global $nya;
            $sqlcmd = "select count(*) from ".$tableStr;
            return $this->sqlc($sqlcmd);
        }
        //
        /**
         * @description: 测试SQL连接
         * @return String mysql版本号
         */
        function sqltest() {
            global $nya;
            $con = mysqli_connect($nya->cfg->db->db_host,$nya->cfg->db->db_user,$nya->cfg->db->db_password,$nya->cfg->db->db_name,$nya->cfg->db->db_port);
            $serinfo = mysqli_get_server_info($con);
            mysqli_close($con);
            return $serinfo;
        }
        /**
         * @description: 执行SQL连接
         * @param String sqlcmd SQL语句
         * @return Array<Int,Array> 返回的状态码和内容
         */
        function sqlc($sqlcmd) {
            global $nya;
            $dbcfg = $nya->cfg->db;
            $con = mysqli_connect($dbcfg->db_host,$dbcfg->db_user,$dbcfg->db_password,$dbcfg->db_name,$dbcfg->db_port);
            $sqlerrno = mysqli_connect_errno($con);
            if ($sqlerrno) {
                die($nya->msg->m(2100,true,$sqlerrno));
            }
            $result = mysqli_query($con,$sqlcmd);
            mysqli_close($con);
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
                            die($nya->msg->m(2102));
                        }
                    }
                } else {
                    return [1101]; //SQL语句成功执行，返回0值。
                }
            } else {
                die($nya->msg->m(2101));
            }
        }
    }
?>