<?php 
/*
后端：用户登录
输入：username,userpassword,userpassword2（可选）,vcode,autologin31536000,userversion
*/
    require 'yaloginGlobal.php';
    require 'yaloginSQLC.php';
    class yaloginLogin {
        private $ysqlc;
        public $hash;
        private $sqlset;
        
        //创建变量
        function init() { //__constrct()
            $this->ysqlc = new yaloginSQLC();
            $this->ysqlc->init();
            $this->sqlset = $this->ysqlc->sqlset;
        }
        
        //验证输入
        function vaild() { // -> int
            // if(is_array($_POST)&&count($_POST)>0) {
            //     return 10201;
            // }
            
            //vcode
            @session_start();
            

            return 0;
        }
        
        //创建SQL语句
        // function gensql($acode) {
        //     $sqlcmd = "SELECT `hash`,`verifymail`,`useremail` FROM `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."` WHERE `verifymailcode` = '".$acode."';";
        //     $result_array = $this->ysqlc->sqlc($sqlcmd,true,false);
        //     return $result_array;
        // }
        //激活用户
        // function actusersql() {
        //     $sqlcmd = "update `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."` set `verifymail`=null where `hash`='".$this->hash."'";
        //     $result_array = $this->ysqlc->sqlc($sqlcmd,false,true);
        //     return $result_array;
        // }

        //记录日志
        function savereg($infoid) {
            if (!($infoid >= 1000 && $infoid < 10000 && $this->ysqlc->sqlset->log_Activation_OK == true) || !($infoid >= 10000 && $infoid < 100000 && $this->ysqlc->sqlset->log_Activation_Fail == true)) {
                return -1;
            }
            $datetime = date("Y-m-d H:i:s");
            $ip = $_SERVER['REMOTE_ADDR'].":".$_SERVER['REMOTE_PORT']."/".$_SERVER['REMOTE_HOST'];
            $saveregr = $this->ysqlc->savereg($infoid,$this->hash,$datetime,$ip,4);
            $this->errinfo = "";
            return $saveregr;
        }

        //发送激活码邮件
        function sendvcodemail() {
            $sendmail = new Sendmail();
            $sendmail->init();
            $sendmail->sendverifymail($this->userobj->useremail, $this->userobj->username, $this->userobj->verifymailcode, $this->userobj->verifymail);
        }
}
?>