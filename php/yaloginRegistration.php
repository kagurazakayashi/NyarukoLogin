<?php 
/*
后端：用户注册
输入：username，usernickname，useremail，userpassword
可选输入：userpassword2，userpasswordquestion1，userpasswordanswer1，userpasswordquestion2，userpasswordanswer2，userpasswordquestion3，userpasswordanswer3，userbirthday，usersex
*/
    require 'yaloginUserInfo.php';
    require 'yaloginGlobal.php';
    require 'yaloginSendmail.php';
    require 'yaloginSQLC.php';
    require 'yaloginSafe.php';
    class yaloginRegistration {
        
        public $userobj;
        private $datetime, $ip;
        private $sqlset;
        private $inputmatch;
        private $app;
        private $safe;
        private $errinfo = "";
        public $ysqlc;
        public $globalsett;
        
        //创建变量
        function init() { //__constrct()
            $this->userobj = new YaloginUserInfo();
            $this->globalsett = new YaloginGlobal();
            $this->safe = new yaloginSafe();
            $this->ysqlc = new yaloginSQLC();
            $this->ysqlc->init();
            $this->sqlset = $this->ysqlc->sqlset;
            $this->inputmatch = $this->inputmatch;
            date_default_timezone_set("PRC");
            $this->datetime = date("Y-m-d H:i:s");
            $this->ip = $_SERVER['REMOTE_ADDR'].":".$_SERVER['REMOTE_PORT']."/".$_SERVER['REMOTE_HOST'];
        }
        
        //验证输入
        function vaild() { // -> int
            if(is_array($_GET)&&count($_GET)>0) {
                return 10201;
            }
            
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
            
            //id
            
            //userversion
            $this->userobj->userversion = 1;
            
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
            $this->userobj->username = $v;
            
            if ($this->chkrep("username",$v) > 0) {
                return 10303;
            }
            
            //usernickname
            $v = isset($_POST["usernickname"]) ? $_POST["usernickname"] : null;
            if($v == null || !is_string($v)) {
                $v = $_POST["username"]; //return 10401;
            }
            if ($this->safe->containsSpecialCharacters($v,$this->inputmatch) != 0) {
                return 10404;
            }
            if (strlen($v) < 3 || strlen($v) > 32) {
                return 10402;
            }
            $this->userobj->usernickname = $v;
            
            //useremail
            $v = isset($_POST["useremail"]) ? $_POST["useremail"] : null;
            if($v == null || !is_string($v)) {
                return 10501;
            }
            if ($this->safe->containsSpecialCharacters($v,$this->inputmatch) != 0) {
                return 10404;
            }
            if (strlen($v) < 5 || strlen($v) > 64) {
                return 10502;
            }
            $email_address = $v;
            $pattern = "/^[a-z0-9]+([\+_\-\.]?[a-z0-9]+)*/i"; ///^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i
            if ( !preg_match( $pattern, $email_address ) )
            {
                return 10503;
            }
            $this->userobj->useremail = $v;
            
            //userpassword
            $v = isset($_POST["userpassword"]) ? $_POST["userpassword"] : null;
            if($v == null || !is_string($v)) {
                return 10601;
            }
            if (!$this->safe->is_md5($v)) {
                return 10603;
            }
            $this->userobj->userpassword = $v;
            
            //userpassword2
            $v = isset($_POST["userpassword2"]) ? $_POST["userpassword2"] : null;
            if (strlen($v) > 0) {
                if (!$this->safe->is_md5($v)) {
                    return 10703;
                }
            }
            $this->userobj->userpassword2 = $v;
            
            //userpasswordenabled
            $this->userobj->userpasswordenabled = 1;
            
            //userenable
            $this->userobj->userenable = $this->datetime;
            
            //userjurisdiction
            $this->userobj->userjurisdiction = 5;
            
            //userpasswordquestion & userpasswordanswer
            $v = isset($_POST["userpasswordquestion1"]) ? $_POST["userpasswordquestion1"] : null;
            if ($this->safe->containsSpecialCharacters($v,$this->inputmatch) != 0) {
                return 10801;
            }
            if (strlen($v) > 64) {
                return 10802;
            }
            $this->userobj->userpasswordquestion1 = $v;
            $v = isset($_POST["userpasswordanswer1"]) ? $_POST["userpasswordanswer1"] : null;
            if ($this->safe->containsSpecialCharacters($v,$this->inputmatch) != 0) {
                return 10803;
            }
            if (strlen($v) > 64) {
                return 10804;
            }
            $this->userobj->userpasswordanswer1 = $v;
            $v = isset($_POST["userpasswordquestion2"]) ? $_POST["userpasswordquestion2"] : null;
            if ($this->safe->containsSpecialCharacters($v,$this->inputmatch) != 0) {
                return 10805;
            }
            if (strlen($v) > 64) {
                return 10806;
            }
            $this->userobj->userpasswordquestion2 = $v;
            $v = isset($_POST["userpasswordanswer2"]) ? $_POST["userpasswordanswer2"] : null;
            if ($this->safe->containsSpecialCharacters($v,$this->inputmatch) != 0) {
                return 10807;
            }
            if (strlen($v) > 64) {
                return 10808;
            }
            $this->userobj->userpasswordanswer2 = $v;
            $v = isset($_POST["userpasswordquestion3"]) ? $_POST["userpasswordquestion3"] : null;
            if ($this->safe->containsSpecialCharacters($v,$this->inputmatch) != 0) {
                return 10809;
            }
            if (strlen($v) > 64) {
                return 10810;
            }
            $this->userobj->userpasswordquestion3 = $v;
            $v = isset($_POST["userpasswordanswer3"]) ? $_POST["userpasswordanswer3"] : null;
            if ($this->safe->containsSpecialCharacters($v,$this->inputmatch) != 0) {
                return 10811;
            }
            if (strlen($v) > 64) {
                return 10812;
            }
            $this->userobj->userpasswordanswer3 = $v;
            
            //usersex
            $v = isset($_POST["usersex"]) ? $_POST["usersex"] : null;
            if($v == null || !is_string($v)) {
                $v = "0";//return 10901;
            }
            if ($this->safe->containsSpecialCharacters($v,$this->inputmatch) != 0) {
                return 10904;
            }
            if (strlen($v) < 1 || strlen($v) > 2) {
                return 10902;
            }
            $this->userobj->usersex = $v;
            
            //userbirthday
            $v = isset($_POST["userbirthday"]) ? $_POST["userbirthday"] : null;
            if($v == null || !is_string($v)) {
                $v == null; //return 11001;
            } else {
                if ($this->safe->containsSpecialCharacters($v,$this->inputmatch) != 0) {
                    return 11004;
                }
                if ($this->checkDateIsValid($v, array("Y-m-d")) == false) {
                    return 11002;
                }
            }
            
            $this->userobj->userbirthday = $v;
            
            //userpasserr
            $this->userobj->userpasserr = 0;
            
            //userregistertime & userlogintime
            $this->userobj->userregistertime = $this->datetime;
            //$this->userobj->userlogintime = $datetime;
            
            //userregisterip & userloginip
            $this->userobj->userregisterip = $this->ip;
            //$this->userobj->userloginip = $ip;
            
            //userregisterapp //& userloginapp
            $this->userobj->userregisterapp = $this->sqlset->db_app;
            // $v = isset($_POST["userregisterapp"]) ? $_POST["userregisterapp"] : null;
            // $v = $this->$this->safe->containsSpecialCharacters($v,$this->inputmatch);
            // if($v == null || !is_string($v)) {
            //     return 11101;
            // }
            // if ($this->safe->containsSpecialCharacters($v,$this->inputmatch) != 0) {
            //     return 11104;
            // }
            // if (strlen($v) < 1 || strlen($v) > 64) {
            //     return 11102;
            // }
            // $this->userobj->userregisterapp = $v;

            //邮件验证码
            //$time = date('Y-m-d H:i:s',strtotime('+1 day'));
            $this->userobj->verifymailcode = $this->userhash();
            $this->userobj->verifymail = date('Y-m-d H:i:s',strtotime('+1 hour')); //有效期1小时

            //唯一哈希码
            $this->userobj->hash = $this->userhash();
            
            return 0;
        }

        function userhash() {
            return $this->safe->randhash($this->userobj->username.$this->userobj->useremail);
        }
        
        //检查日期格式
        function checkDateIsValid($date, $formats) { // = array("Y-m-d", "Y/m/d"
            $unixTime = strtotime($date);
            if (!$unixTime) {
                return false;
            }
            foreach ($formats as $format) {
                if (date($format, $unixTime) == $date) {
                    return true;
                }
            }
            return false;
        }
        
        //创建SQL语句
        function gensql() {
            $key = "";
            $val = "";
            
            $v = $this->userobj->userversion;
            if ($v != null) {
                $key = $key."`userversion`,";
                $val = $val.$v.",";
            }
            
            $v = $this->userobj->username;
            if ($v != null) {
                $key = $key."`username`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->usernickname;
            if ($v != null) {
                $key = $key."`usernickname`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->useremail;
            if ($v != null) {
                $key = $key."`useremail`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->userpassword;
            if ($v != null) {
                $key = $key."`userpassword`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->userpassword2;
            if ($v != null) {
                $key = $key."`userpassword2`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->userpasswordenabled;
            if ($v != null) {
                $key = $key."`userpasswordenabled`,";
                $val = $val.$v.",";
            }
            
            $v = $this->userobj->userenable;
            if ($v != null) {
                $key = $key."`userenable`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->userjurisdiction;
            if ($v != null) {
                $key = $key."`userjurisdiction`,";
                $val = $val.$v.",";
            }
            
            $v = $this->userobj->userpasswordquestion1;
            if ($v != null) {
                $key = $key."`userpasswordquestion1`,";
                $val = $val."'".$v."',";
            }
            $v = $this->userobj->userpasswordquestion2;
            if ($v != null) {
                $key = $key."`userpasswordquestion2`,";
                $val = $val."'".$v."',";
            }
            $v = $this->userobj->userpasswordquestion3;
            if ($v != null) {
                $key = $key."`userpasswordquestion3`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->userpasswordanswer1;
            if ($v != null) {
                $key = $key."`userpasswordanswer1`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->userpasswordanswer2;
            if ($v != null) {
                $key = $key."`userpasswordanswer2`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->userpasswordanswer3;
            if ($v != null) {
                $key = $key."`userpasswordanswer3`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->usersex;
            if ($v != null) {
                $key = $key."`usersex`,";
                $val = $val.$v.",";
            }
            
            $v = $this->userobj->userbirthday;
            if ($v != null) {
                $key = $key."`userbirthday`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->userpasserr;
            if ($v != null) {
                $key = $key."`userpasserr`,";
                $val = $val.$v.",";
            }
            
            $v = $this->userobj->userregistertime;
            if ($v != null) {
                $key = $key."`userregistertime`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->userregisterip;
            if ($v != null) {
                $key = $key."`userregisterip`,";
                $val = $val."'".$v."',";
            }
            
            // $v = $this->userobj->userlogintime;
            // if ($v != null) {
            //     $key = $key."`userlogintime`,";
            //     $val = $val."'".$v."',";
            // }
            
            // $v = $this->userobj->userloginip;
            // if ($v != null) {
            //     $key = $key."`userloginip`,";
            //     $val = $val."'".$v."',";
            // }
            
            $v = $this->userobj->userregisterapp;
            if ($v != null) {
                $key = $key."`userregisterapp`,";
                $val = $val."'".$v."',";
            }
            
            // $v = $this->userobj->userloginapp;
            // if ($v != null) {
            //     $key = $key."`userloginapp`";
            //     $val = $val."'".$v."'";
            // }

            $v = $this->userobj->verifymail;
            if ($v != null) {
                $key = $key."`verifymail`,";
                $val = $val."'".$v."',";
            }
            $v = $this->userobj->verifymailcode;
            if ($v != null) {
                $key = $key."`verifymailcode`,";
                $val = $val."'".$v."',";
            }

            $v = $this->userobj->hash;
            if ($v != null) {
                $key = $key."`hash`";
                $val = $val."'".$v."'";
            }

            $sqlcmd = "insert `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."`(".$key.") values(".$val.");";
            $result_array = $this->ysqlc->sqlc($sqlcmd,false,true);
            if (is_int($result_array)) {
                return $result_array; //err
            } else {
                return 0;
            }
        }
        
        //查询用户名是否重复
        function chkrep($key,$val) {
            //sqlset
            $sqlcmd = "SELECT count(0) FROM `".$this->sqlset->db_user_table."` WHERE `".$key."`='".$val."';";
            $result_array = $this->ysqlc->sqlc($sqlcmd,true,true);
            if (is_int($result_array)) {
                return $result_array;
            } else {
                //print_r($result_array);
                $userrep = $result_array["count(0)"];
                if ($userrep > 0) {
                    return 1;
                }
                return 0;
            }
            //if ($result_array[count(0)] == 0)
        }

        //记录日志
        function savereg($userlogininfoid) {
            if (!($userlogininfoid >= 1000 && $userlogininfoid < 10000 && $this->ysqlc->sqlset->log_Registration_OK == true) || !($userlogininfoid >= 10000 && $userlogininfoid < 100000 && $this->ysqlc->sqlset->log_Registration_Fail == true)) {
                return -1;
            }
            $saveregr = $this->ysqlc->savereg($userlogininfoid,$this->userobj->hash,$this->datetime,$this->ip,2);
            $this->errinfo = "";
            return $saveregr;
        }

        //发送激活码邮件
        function sendvcodemail() {
            $sendmail = new Sendmail();
            $sendmail->init();
            return $sendmail->sendverifymail($this->userobj->useremail, $this->userobj->username, $this->userobj->verifymailcode, $this->userobj->verifymail);
        }
}
?>