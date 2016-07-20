<?php 
/*
后端：用户登录
输入：username,userpassword,userpassword2（可选）,vcode,autologin31536000,userversion（可选）
*/
    require 'yaloginGlobal.php';
    require 'yaloginSQLC.php';
    require 'yaloginUserInfo.php';
    class yaloginLogin {
        private $ysqlc;
        public $hash;
        private $sqlset;
        private $inpuser;
        private $seruser;
        
        //创建变量
        function init() { //__constrct()
            $this->ysqlc = new yaloginSQLC();
            $this->inpuser = new YaloginUserInfo();
            $this->seruser = new YaloginUserInfo();
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
            $v = isset($_POST["vcode"]) ? $_POST["vcode"] : null;
            if($v != null){
                if ($this->safe->containsSpecialCharacters($v,$this->inputmatch) != 0) {
                    return 90404;
                }
                if(!isset($_SESSION["authnum_session"])) {
                    return 90401;
                }
                $va = strtoupper($v);
                $vb = strtoupper($_SESSION["authnum_session"]);
                if($va!=$vb){
                    return 90402;
                }
            } else {
                return 90403;
            }

            //username
            $v = isset($_POST["username"]) ? $_POST["username"] : null;
            if($v == null || !is_string($v)) {
                return 10301;
            }
            if ($this->safe->containsSpecialCharacters($v,$this->inputmatch) != 0) {
                return 10304;
            }
            if (strlen($v) < 3 || strlen($v) > 32) {
                return 10302;
            }
            $v = strtolower($v);
            $this->inpuser->username = $v;

            //userpassword
            $v = isset($_POST["userpassword"]) ? $_POST["userpassword"] : null;
            if($v == null || !is_string($v)) {
                return 10601;
            }
            if (!$this->is_md5($v)) {
                return 10603;
            }
            $this->inpuser->userpassword = $v;

            //userpassword2
            $v = isset($_POST["userpassword2"]) ? $_POST["userpassword2"] : null;
            if (strlen($v) > 0) {
                if (!$this->is_md5($v)) {
                    return 10703;
                }
            }
            $this->inpuser->userpassword2 = $v;

            //userloginapp,userversion
            $this->inpuser->userregisterapp = $this->sqlset->db_app;

            //userversion
            $v = isset($_POST["userversion"]) ? $_POST["userversion"] : 0;
            $this->inpuser->userversion = intval($v);
            if ($this->inpuser->userversion != 1) {
                return 12102;
            }

            //autologin
            $v = isset($_POST["autologin"]) ? $_POST["autologin"] : 0;
            $this->inpuser->autologin = intval($v);
            if ($this->inpuser->autologin < 0 || $this->inpuser->autologin > 157680000) {
                return 12103;
            }

            return 0;
        }

        //检索用户信息

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
}
?>