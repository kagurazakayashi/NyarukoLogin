<?php
    require_once "../nyaconfig.class.php";
    require_once "nyainfomsg.class.php";
    class nyadbconnect {
        var $nyasetting_db;
        var $nyasetting_app;
        function __construct() {
            $this->nyasetting_db = new nyasetting_db();
            $this->nyasetting_app = new nyasetting_app();
            $this->nyainfomsg = new nyainfomsg();
        }
        //查询数据 selectArr:要查询的列名 tableStr:表名
        function nyaconnect_select($selectArr,$tableStr,$whereStr,$whereIsStr) {
            $sqlcmd = "SELECT `".implode('`,`',$selectArr)."` FROM `".$this->nyasetting_db->db_name."`.`".$tableStr."` WHERE `".$whereStr."` = '".$whereIsStr."';";
            return nyaconnect_sqlc($sqlcmd);
        }
        //插入数据
        function nyaconnect_insert($insertDic,$tableStr) {
            $keys = "";
            $vals = "";
            while(list($key,$val) = each($insertDic)) { 
                $keys += "`".$key."`,";
                $vals += "'".$val."',";
            }
            $sqlcmd = "INSERT INTO `".$this->nyasetting_db->db_name."`.`".$tableStr."`(".substr($keys, 0, -1).") VALUES(".substr($vals, 0, -1).");";
            return nyaconnect_sqlc($sqlcmd);
        }
        //更新数据
        function nyaconnect_update($updateDic,$whereStr,$whereIsStr) {
            $update = "";
            while(list($key,$val) = each($updateDic)) { 
                $update += "`".$key."` = '".$val."', ";
            }
            $sqlcmd = "UPDATE `".$this->nyasetting_db->db_name."`.`".$tableStr."` SET ".substr($update, 0, -2)." WHERE `".$whereStr."` = '".$whereIsStr."';";
            return nyaconnect_sqlc($sqlcmd);
        }
        //测试SQL连接(获得mysql版本)
        function nyaconnect_sqltest() {
            $con = mysqli_connect($this->nyasetting_db->db_host,$this->nyasetting_db->db_user,$this->nyasetting_db->db_password,$this->nyasetting_db->db_name,$this->nyasetting_db->db_port);
            $serinfo = mysqli_get_server_info($con);
            mysqli_close($con);
            return $serinfo;
        }
        //执行SQL连接
        function nyaconnect_sqlc($sqlcmd) {
            $con = mysqli_connect($this->nyasetting_db->db_host,$this->nyasetting_db->db_user,$this->nyasetting_db->db_password,$this->nyasetting_db->db_name,$this->nyasetting_db->db_port);
            $sqlerrno = mysqli_connect_errno($con);
            if ($sqlerrno) {
                die($this->nyainfomsg->m(2100,true,$sqlerrno));
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
                            die($this->nyainfomsg->m(2102));
                        }
                    }
                } else {
                    return [1101]; //SQL语句成功执行，返回0值。
                }
            } else {
                die($this->nyainfomsg->m(2101));
            }
        }
    }
?>