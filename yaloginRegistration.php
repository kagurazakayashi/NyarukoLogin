<?php 
    require 'yaloginUserInfo.php';
    class yaloginRegistration {
        
        public $userobj;
        private $datetime, $ip;
        
        function __constrct() {
            $userobj = new YaloginUserInfo();
            date_default_timezone_set("PRC");
            $this->datetime = date("Y-m-d h:i:s");
            $this->ip = $_SERVER['REMOTE_ADDR'].":".$_SERVER['REMOTE_PORT']."/".$_SERVER['REMOTE_HOST'];
        }
        
        function vaild() { // -> int
            if(is_array($_GET)&&count($_GET)>0) {
                return 2;
            }
            //id
            
            //userversion
            $this->userobj.$userversion = 1;
            
            //username
            $v = isset($_POST["username"]) ? $_POST["username"] : null;
            if($v == null || !is_string($v)) {
                return 3;
            }
            if (strlen($v) < 3 || strlen($v) > 16) {
                return 4;
            }
            $v = strtolower($v);
            $this->userobj.$username = $v;
            
            //usernickname
            $v = isset($_POST["usernickname"]) ? $_POST["usernickname"] : null;
            if($v == null || !is_string($v)) {
                return 5;
            }
            if (strlen($v) < 3 || strlen($v) > 16) {
                return 6;
            }
            $this->userobj.$usernickname = $v;
            
            //useremail
            $v = isset($_POST["useremail"]) ? $_POST["useremail"] : null;
            if($v == null || !is_string($v)) {
                return 7;
            }
            if (strlen($v) < 5 || strlen($v) > 64) {
                return 8;
            }
            $email_address = $v;
            $pattern = "/^[a-z0-9]+([\+_\-\.]?[a-z0-9]+)*/i"; ///^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i
            if ( !preg_match( $pattern, $email_address ) )
            {
                return 9;
            }
            $this->userobj.$useremail = $v;
            
            //userpassword
            $v = isset($_POST["userpassword"]) ? $_POST["userpassword"] : null;
            if($v == null || !is_string($v)) {
                return 10;
            }
            if (strlen($v) < 6 || strlen($v) > 64) {
                return 11;
            }
            if (!$this->is_md5($v)) {
                return 12;
            }
            $this->userobj.$userpassword = $v;
            
            //userpassword2
            $v = isset($_POST["userpassword2"]) ? $_POST["userpassword2"] : null;
            if($v == null || !is_string($v)) {
                return 13;
            }
            if (strlen($v) > 0) {
                if (strlen($v) < 6 || strlen($v) > 64) {
                    return 14;
                }
            }
            $this->userobj.$userpassword2 = $v;
            
            //userpasswordenabled
            $this->userobj.$userpasswordenabled = 1;
            
            //userenable
            $this->userobj.$userenable = $this->datetime;
            
            //userjurisdiction
            $this->userobj.$userjurisdiction = 5;
            
            //userpasswordquestion & userpasswordanswer
            $v = isset($_POST["userpasswordquestion1"]) ? $_POST["userpasswordquestion1"] : null;
            if($v == null || !is_string($v)) {
                return 15;
            }
            if (strlen($v) > 64) {
                return 16;
            }
            $this->userobj.$userpasswordquestion1 = $v;
            $v = isset($_POST["userpasswordanswer1"]) ? $_POST["userpasswordanswer1"] : null;
            if($v == null || !is_string($v)) {
                return 17;
            }
            if (strlen($v) > 64) {
                return 18;
            }
            $this->userobj.$userpasswordanswer1 = $v;
            $v = isset($_POST["userpasswordquestion2"]) ? $_POST["userpasswordquestion2"] : null;
            if($v == null || !is_string($v)) {
                return 19;
            }
            if (strlen($v) > 64) {
                return 20;
            }
            $this->userobj.$userpasswordquestion2 = $v;
            $v = isset($_POST["userpasswordanswer2"]) ? $_POST["userpasswordanswer2"] : null;
            if($v == null || !is_string($v)) {
                return 21;
            }
            if (strlen($v) > 64) {
                return 22;
            }
            $this->userobj.$userpasswordanswer2 = $v;
            $v = isset($_POST["userpasswordquestion3"]) ? $_POST["userpasswordquestion3"] : null;
            if($v == null || !is_string($v)) {
                return 23;
            }
            if (strlen($v) > 64) {
                return 24;
            }
            $this->userobj.$userpasswordquestion3 = $v;
            $v = isset($_POST["userpasswordanswer3"]) ? $_POST["userpasswordanswer3"] : null;
            if($v == null || !is_string($v)) {
                return 25;
            }
            if (strlen($v) > 64) {
                return 26;
            }
            $this->userobj.$userpasswordanswer3 = $v;
            
            //usersex
            $v = isset($_POST["usersex"]) ? $_POST["usersex"] : null;
            if($v == null || !is_string($v)) {
                return 32;
            }
            if (strlen($v) < 1 || strlen($v) > 2) {
                return 33;
            }
            $this->userobj.$usersex = $v;
            
            //userbirthday
            $v = isset($_POST["userbirthday"]) ? $_POST["userbirthday"] : null;
            if($v == null || !is_string($v)) {
                return 27;
            }
            if (!checkDateIsValid($v)) {
                return 28;
            }
            
            //userpasserr
            $this->userobj.$userpasserr = 0;
            
            //userregistertime & userlogintime
            $this->userobj.$userregistertime = $this->datetime;
            //$this->userobj.$userlogintime = $datetime;
            
            //userregisterip & userloginip
            $this->userobj.$userregisterip = $this->ip;
            //$this->userobj.$userloginip = $ip;
            
            //userregisterapp //& userloginapp
            $v = $_POST["userloginapp"];
            if($v == null || !is_string($v)) {
                return 34;
            }
            if (strlen($v) < 1 || strlen($v) > 64) {
                return 35;
            }
            
            //vcode
            $v = isset($_POST["vcode"]) ? $_POST["vcode"] : null;
            if($v != null){
                if(!isset($_SESSION["authnum_session"])) {
                    return 29;
                }
                $va = strtoupper($v);
                $vb = strtoupper($_SESSION["authnum_session"]);
                if($va!=$vb){
                    return 30;
                }
            } else {
                return 31;
            }
            
            return 0;
        }
        
        function is_md5($password) {
            return preg_match("/^[a-z0-9]{32}$/", $password);
        }
        
        function checkDateIsValid($date, $formats = array("Y-m-d")) { //, "Y/m/d"
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
}
$registration = new yaloginRegistration();
$errid = $registration->vaild();
if ($errid > 0) {
    echo("error " + $errid);
} else {
    echo("vaild ok");
}
?>