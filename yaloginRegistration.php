<?php 
    require 'yaloginUserInfo.php';
    require 'yaloginSQLSetting.php';
    class yaloginRegistration {
        
        public $userobj;
        private $datetime, $ip;
        private $sqlset;
        
        function init() { //__constrct()
            $this->sqlset = new YaloginSQLSetting();
            $this->userobj = new YaloginUserInfo();
            date_default_timezone_set("PRC");
            $this->datetime = date("Y-m-d h:i:s");
            $this->ip = $_SERVER['REMOTE_ADDR'].":".$_SERVER['REMOTE_PORT']."/".$_SERVER['REMOTE_HOST'];
        }
        
        function vaild() { // -> int
            if(is_array($_GET)&&count($_GET)>0) {
                return 10201;
            }
            //id
            
            //userversion
            $this->userobj->userversion = 1;
            
            //username
            $v = isset($_POST["username"]) ? $_POST["username"] : null;
            $v = $this->test_input($v);
            if($v == null || !is_string($v)) {
                return 10301;
            }
            if (strlen($v) < 3 || strlen($v) > 16) {
                return 10302;
            }
            $v = strtolower($v);
            $this->userobj->username = $v;
            
            if ($this->chkrep("username",$v) > 0) {
                return 10303;
            }
            
            //usernickname
            $v = isset($_POST["usernickname"]) ? $_POST["usernickname"] : null;
            $v = $this->test_input($v);
            if($v == null || !is_string($v)) {
                return 10401;
            }
            if (strlen($v) < 3 || strlen($v) > 16) {
                return 10402;
            }
            $this->userobj->usernickname = $v;
            
            //useremail
            $v = isset($_POST["useremail"]) ? $_POST["useremail"] : null;
            $v = $this->test_input($v);
            if($v == null || !is_string($v)) {
                return 10501;
            }
            if (strlen($v) < 5 || strlen($v) > 64) {
                return 10502;
            }
            $email_address = $v;
            $pattern = "/^[a-z0-9]+([\+_\-\.]?[a-z0-9]+)*/i"; ///^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i
            if ( !preg_match( $pattern, $email_address ) )
            {
                return 10601;
            }
            $this->userobj->useremail = $v;
            
            //userpassword
            $v = isset($_POST["userpassword"]) ? $_POST["userpassword"] : null;
            $v = $this->test_input($v);
            if($v == null || !is_string($v)) {
                return 10701;
            }
            if (strlen($v) < 6 || strlen($v) > 64) {
                return 10702;
            }
            if (!$this->is_md5($v)) {
                return 10703;
            }
            $this->userobj->userpassword = $v;
            
            //userpassword2
            $v = isset($_POST["userpassword2"]) ? $_POST["userpassword2"] : null;
            $v = $this->test_input($v);
            if($v == null || !is_string($v)) {
                return 10801;
            }
            if (strlen($v) > 0) {
                if (strlen($v) < 6 || strlen($v) > 64) {
                    return 10802;
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
            $v = $this->test_input($v);
            if($v == null || !is_string($v)) {
                return 10901;
            }
            if (strlen($v) > 64) {
                return 10902;
            }
            $this->userobj->userpasswordquestion1 = $v;
            $v = isset($_POST["userpasswordanswer1"]) ? $_POST["userpasswordanswer1"] : null;
            $v = $this->test_input($v);
            if($v == null || !is_string($v)) {
                return 11001;
            }
            if (strlen($v) > 64) {
                return 11002;
            }
            $this->userobj->userpasswordanswer1 = $v;
            $v = isset($_POST["userpasswordquestion2"]) ? $_POST["userpasswordquestion2"] : null;
            $v = $this->test_input($v);
            if($v == null || !is_string($v)) {
                return 11101;
            }
            if (strlen($v) > 64) {
                return 11102;
            }
            $this->userobj->userpasswordquestion2 = $v;
            $v = isset($_POST["userpasswordanswer2"]) ? $_POST["userpasswordanswer2"] : null;
            $v = $this->test_input($v);
            if($v == null || !is_string($v)) {
                return 11201;
            }
            if (strlen($v) > 64) {
                return 11202;
            }
            $this->userobj->userpasswordanswer2 = $v;
            $v = isset($_POST["userpasswordquestion3"]) ? $_POST["userpasswordquestion3"] : null;
            $v = $this->test_input($v);
            if($v == null || !is_string($v)) {
                return 11301;
            }
            if (strlen($v) > 64) {
                return 11302;
            }
            $this->userobj->userpasswordquestion3 = $v;
            $v = isset($_POST["userpasswordanswer3"]) ? $_POST["userpasswordanswer3"] : null;
            $v = $this->test_input($v);
            if($v == null || !is_string($v)) {
                return 11401;
            }
            if (strlen($v) > 64) {
                return 11402;
            }
            $this->userobj->userpasswordanswer3 = $v;
            
            //usersex
            $v = isset($_POST["usersex"]) ? $_POST["usersex"] : null;
            $v = $this->test_input($v);
            if($v == null || !is_string($v)) {
                return 11501;
            }
            if (strlen($v) < 1 || strlen($v) > 2) {
                return 11502;
            }
            $this->userobj->usersex = $v;
            
            //userbirthday
            $v = isset($_POST["userbirthday"]) ? $_POST["userbirthday"] : null;
            $v = $this->test_input($v);
            if($v == null || !is_string($v)) {
                return 11601;
            }
            if ($this->checkDateIsValid($v, array("Y-m-d")) == false) {
                return 11602;
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
            $v = isset($_POST["userloginapp"]) ? $_POST["userloginapp"] : null;
            $v = $this->test_input($v);
            if($v == null || !is_string($v)) {
                return 11701;
            }
            if (strlen($v) < 1 || strlen($v) > 64) {
                return 11702;
            }
            $this->userobj->userloginapp = $v;
            
            //vcode
            session_start();
            $v = isset($_POST["vcode"]) ? $_POST["vcode"] : null;
            $v = $this->test_input($v);
            if($v != null){
                if(!isset($_SESSION["authnum_session"])) {
                    return 11801;
                }
                $va = strtoupper($v);
                $vb = strtoupper($_SESSION["authnum_session"]);
                if($va!=$vb){
                    return 11802;
                }
            } else {
                return 11803;
            }
            
            return 0;
        }
        
        function is_md5($password) {
            return preg_match("/^[a-z0-9]{32}$/", $password);
        }
        
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
            
            $v = $this->userobj->userlogintime;
            if ($v != null) {
                $key = $key."`userlogintime`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->userloginip;
            if ($v != null) {
                $key = $key."`userloginip`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->userregisterapp;
            if ($v != null) {
                $key = $key."`userregisterapp`,";
                $val = $val."'".$v."',";
            }
            
            $v = $this->userobj->userloginapp;
            if ($v != null) {
                $key = $key."`userloginapp`";
                $val = $val."'".$v."'";
            }
            
            $sqlcmd = "insert `userdb`.`yalogin_user`(".$key.") values(".$val.");";
            $result_array = $this->sqlc($sqlcmd);
            if (is_int($result_array)) {
                return $result_array; //err
            } else {
                return 0;
            }
        }
        
        function sqlc($sqlcmd) {
            $con=mysqli_connect($this->sqlset->db_host,$this->sqlset->db_user,$this->sqlset->db_password,$this->sqlset->db_name,$this->sqlset->db_port);
            if (mysqli_connect_errno($con)) {
                return 90000;
                //echo "Failed to connect to MySQL: " . mysqli_connect_error();
            }
            $result = mysqli_query($con,$sqlcmd);
            mysqli_close($con);
            if ($result) {
                    if(@mysqli_num_rows($result)) {
                    $result_array = mysqli_fetch_array($result); //err
                    if($result_array) {
                        if (count($result_array) > 0) {
                            return $result_array;
                        } else {
                            return 90104;
                        }
                        //foreach($result_array as $result_row) {
                            //print_r($result_array);
                        //}
                    } else {
                        return 90103;
                    }
                } else {
                    return 0; // 0 / 90102;
                }
            } else {
                return 90101;
            }
            return 90100;
        }
        
        function chkrep($key,$val) {
            //sqlset
            $sqlcmd = "SELECT count(0) FROM `yalogin_user` WHERE `".$key."`='".$val."';";
            $result_array = $this->sqlc($sqlcmd);
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
        
        function test_input($data) {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
        }
}
$registration = new yaloginRegistration();
$registration->init();
$errid = $registration->vaild();
if ($errid > 0) {
    echo("error");
    echo(strval($errid));
} else {
    //chkequser
    $errid2 = $registration->gensql();
    if ($errid2 > 0) {
        echo("error");
        echo(strval($errid2));
    } else {
        echo("用户注册成功。");
    }
}
?>