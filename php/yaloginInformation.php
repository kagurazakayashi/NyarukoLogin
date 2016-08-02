<?php
    require 'yaloginUserInfo.php';
    require 'yaloginGlobal.php';
    require 'yaloginStatus.php';
    require 'yaloginSQLC.php';

    class yaloginInformation
    {
        private $user;
        private $sqlset;
        private $ysqlc;

        function init()
        {
            $this->user = new YaloginUserInfo();
            $this->ysqlc = new yaloginSQLC();
            $this->sqlset = $this->ysqlc->sqlset;
        }

        /*获取指定的用户资料
        $table 为空使用 $db_user_table
        table 使用别名
        column 列名(逗号分隔)
        $db_safetable
        $db_safecolumn
        */
        function getInformation() {
             $db = isset($_POST["db"]) ? $_POST["db"] : $this->sqlset->db_name;
             $table = isset($_POST["table"]) ? $_POST["table"] : $this->sqlset->db_user_table;
             $column = isset($_POST["column"]) ? $_POST["column"] : null;
             if ($column == null) {
                 return 13001;
             }
             $tablename = isset($this->sqlset->db_tablealias[$table]) ? $this->sqlset->db_tablealias[$table] : null;
             if ($tablename == null) {
                 return 13002;
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
             return $this->gensql($tablename,$columnarr,$table,$db,$userhash);
        }

        function gensql($tablename,$columnarr,$table,$db,$userhash) {
            $sqlstr = "SELECT `";
            $columns = implode('`,`',$columnarr);
            sqlstr = sqlstr.columns."` FROM `".$db."`.`".$table."` WHERE `hash` = '".$userhash."';";
            $result_array = $this->ysqlc->sqlc($sqlcmd,true,false);
            return $result_array;
        }
    }
    
    

?>