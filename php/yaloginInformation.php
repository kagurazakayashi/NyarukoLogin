<?php
    //require 'yaloginUserInfo.php';
    require 'yaloginGlobal.php';
    if(class_exists('yaloginStatus') != true) {
        require 'yaloginStatus.php';
    }
    if(class_exists('YaloginSQLSetting') != true) {
        require 'yaloginSQLSetting.php';
    }
    if(class_exists('yaloginSQLC') != true) {
        require 'yaloginSQLC.php';
    }

    class yaloginInformation
    {
        private $user;
        private $sqlset;
        private $ysqlc;

        function init()
        {
            $this->user = new YaloginUserInfo();
            $this->ysqlc = new yaloginSQLC();
            $this->ysqlc->init();
            $this->sqlset = $this->ysqlc->sqlset;
        }

        /*获取指定的用户资料
        $table 为空使用 $db_user_table
        table 使用别名
        column 列名(逗号分隔)
        $db_safetable
        $db_safecolumn
        */

        function getInformation($column,$table = "",$db = "") {

             if (isset($db) && $db != "") {
                 $db = $this->aliasconv($db,1);
                 if ($db == null) {
                     return 13005;
                 }
             } else {
                 $db = $this->sqlset->db_name;
             }

             if (isset($table) && $table != "") {
                 $table = $this->aliasconv($table,2);
                 if ($table == null) {
                     return 13002;
                 }
             } else {
                 $table = $this->sqlset->db_user_table;
             }

             if ($column == null) {
                 return 13001;
             }
             if (in_array($table,$this->sqlset->db_safetable) == true) {
                 return 13003; //包含禁止查询表
             }
             $columnarr = explode(",",$column);
             $columnarrintersect = array_intersect($columnarr,$this->sqlset->db_safecolumn);
             if ($columnarrintersect != null && count($columnarrintersect) > 0) {
                 return 13004; //包含禁止查询列
             }
             $status = new YaloginStatus();
             $status->init();
             $statusarr = $status->loginuser();
             if ($statusarr["autologinby"] == "fail") {
                 return 90901;
             }
             $userhash = $statusarr["userhash"];
             $result_array = $this->subsql($columnarr,$table,$db,$userhash);
             return $result_array;
        }

        function subsql($columnarr,$table,$db,$userhash) {
            $sqlcmd = "SELECT `";
            $columns = implode('`,`',$columnarr);
            $sqlcmd = $sqlcmd.$columns."` FROM `".$db."`.`".$table."` WHERE `hash` = '".$userhash."';";
            $result_array = $this->ysqlc->sqlc($sqlcmd,true,false);
            if (count($result_array) == 0) {
                return 90903;
            }
            if (count($result_array) > 1) {
                return 90902;
            }
            return $result_array[0];
        }

        //从别名提取名字 mode 1=数据库 2=表 3=列（暂不支持）
        function aliasconv($name,$mode) {
            $alias = null;
            if ($mode == 1) {
                $alias = $this->sqlset->db_dbalias;
            } else if ($mode == 2) {
                $alias = $this->sqlset->db_tablealias;
            } else {
                return null;
            }
            $resultname = isset($alias[$name]) ? $alias[$name] : null;
            return $resultname;
        }

        function deleteautokey($array) {
            $newarr = array();
            if (is_array($array) == false) {
                return null;
            }
            while(list($key,$val)= each($array)) {
            	if (is_int($key) == false) {
                    $newarr[$key] = $val;
                }
            }
            return $newarr;
        }
    }
    
    

?>