<?php
    //require 'yaloginUserInfo.php';
    if(class_exists('yaloginGlobal') != true) {
        require 'yaloginGlobal.php';
    }
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
        public $limitfrom = 0;
        public $limitnum = 1;
        public $specificuserhash = "";

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
        column 列名(逗号分隔字符串或者数组均可)
        $db_safetable
        $db_safecolumn
        */

        function getInformation($column,$table = "",$db = "") {
             $va = $this->vaild($column,$table,$db);
             if (is_int($va)) {
                 return $va;
             }
             $result_array = $this->getInformation2($va["columnarr"],$table,$db,$va["userhash"]);
             return $result_array;
        }

        /*设定指定的用户资料
        $table 为空使用 $db_user_table
        table 使用别名
        column 列名(字典)(必须全为字符串不能有int)
        $db_safetable
        $db_safecolumn
        */

        function setInformation($column,$table = "",$db = "") {
            $va = $this->vaild($column,$table,$db);
             if (is_int($va)) {
                 return $va;
             }
             $result_array = $this->setInformation2($va["columnarr"],$table,$db,$va["userhash"]);
             return $result_array;
        }

        function setInformation2($columnarr,$table,$db,$userhash) {
            $sqlcmd = "";
            foreach ($columnarr as $key=>$val) {
                if (!is_int($key)) {
                    if ($sqlcmd != "") {
                        $sqlcmd = $sqlcmd.",";
                    }
                    $sqlcmd = "`".$key."`='".$val."'";
                }
            }
            $sqlcmd = "UPDATE `".$db."`.`".$table."` SET ".$sqlcmd." WHERE `hash`= '".$userhash."';";
            $result_array = $this->ysqlc->sqlc($sqlcmd,true,false);
            return $result_array;
        }

        function getInformation2($columnarr,$table,$db,$userhash) {
            $sqlcmd = "SELECT `";
            $columns = implode('`,`',$columnarr);
            $sqlcmd = $sqlcmd.$columns."` FROM `".$db."`.`".$table."` WHERE `hash` = '".$userhash."'";
            $oneUser = true;
            if ($this->limitfrom < 0 || ($this->limitnum < 1 && $this->limitnum != -1)) {
                return 90905;
            }
            if ($this->limitfrom > 0 || $this->limitnum > 1 || $this->limitnum == -1) {
                if ($this->limitnum > 1) {
                    $sqlcmd = $sqlcmd." LIMIT ".$this->limitfrom.",".$this->limitnum;
                }
                $oneUser = false;
            }
            $sqlcmd = $sqlcmd.";";
            $result_array = $this->ysqlc->sqlc($sqlcmd,true,false);
            if (count($result_array) == 0) {
                return 90903;
            }
            if ($oneUser == true) {
                // if (count($result_array) > 1) {
                //     return 90902;
                // }
                return $result_array[0];
            }
            return $result_array;
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

        function vaild($column,$table,$db) {
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
             $columnarr;
             if (is_array($column)) {
                 $columnarr = $column; //字典/数组
             } else {
                 $columnarr = explode(",",$column);
             }
             $columnarrintersect = array_intersect($columnarr,$this->sqlset->db_safecolumn);
             if ($columnarrintersect != null && count($columnarrintersect) > 0) {
                 return 13004; //包含禁止查询列
             }
             if ($this->specificuserhash != "") {
                 $userhash = $this->specificuserhash;
             } else {
                 $status = new YaloginStatus();
                 $status->init();
                 $statusarr = $status->loginuser();
                 if ($statusarr["autologinby"] == "fail") {
                     return 90901;
                 }
                 $userhash = $statusarr["userhash"];
             }
             return array($columnarr,$userhash);
        }

    }
    
    

?>