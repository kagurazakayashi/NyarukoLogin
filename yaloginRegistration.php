<?php 
    require 'yaloginUserInfo.php';
    require 'yaloginSQLSetting.php';
    require "yaloginGlobal.php";
    class yaloginRegistration {
        
        public $userobj;
        private $datetime, $ip;
        private $sqlset;
        private $inputmatch;
        private $app;
        
        //创建变量
        function init() { //__constrct()
            $this->sqlset = new YaloginSQLSetting();
            $this->userobj = new YaloginUserInfo();
            $globalsett = new YaloginGlobal();
            $this->inputmatch = $globalsett->inputmatch;
            date_default_timezone_set("PRC");
            $this->datetime = date("Y-m-d h:i:s");
            $this->ip = $_SERVER['REMOTE_ADDR'].":".$_SERVER['REMOTE_PORT']."/".$_SERVER['REMOTE_HOST'];
        }
        
        //验证输入
        function vaild() { // -> int
            if(is_array($_GET)&&count($_GET)>0) {
                return 10201;
            }
            
            //vcode
            session_start();
            $v = isset($_POST["vcode"]) ? $_POST["vcode"] : null;
            if($v != null){
                if ($this->test_input($v) != 0) {
                    return 11204;
                }
                if(!isset($_SESSION["authnum_session"])) {
                    return 11201;
                }
                $va = strtoupper($v);
                $vb = strtoupper($_SESSION["authnum_session"]);
                if($va!=$vb){
                    return 11202;
                }
            } else {
                return 11203;
            }
            
            //id
            
            //userversion
            $this->userobj->userversion = 1;
            
            //username
            $v = isset($_POST["username"]) ? $_POST["username"] : null;
            if($v == null || !is_string($v)) {
                return 10301;
            }
            if ($this->test_input($v) != 0) {
                return 10304;
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
            if($v == null || !is_string($v)) {
                return 10401;
            }
            if ($this->test_input($v) != 0) {
                return 10404;
            }
            if (strlen($v) < 3 || strlen($v) > 16) {
                return 10402;
            }
            $this->userobj->usernickname = $v;
            
            //useremail
            $v = isset($_POST["useremail"]) ? $_POST["useremail"] : null;
            if($v == null || !is_string($v)) {
                return 10501;
            }
            if ($this->test_input($v) != 0) {
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
            if (!$this->is_md5($v)) {
                return 10603;
            }
            $this->userobj->userpassword = $v;
            
            //userpassword2
            $v = isset($_POST["userpassword2"]) ? $_POST["userpassword2"] : null;
            if (strlen($v) > 0) {
                if (!$this->is_md5($v)) {
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
            if ($this->test_input($v) != 0) {
                return 10801;
            }
            if (strlen($v) > 64) {
                return 10802;
            }
            $this->userobj->userpasswordquestion1 = $v;
            $v = isset($_POST["userpasswordanswer1"]) ? $_POST["userpasswordanswer1"] : null;
            if ($this->test_input($v) != 0) {
                return 10803;
            }
            if (strlen($v) > 64) {
                return 10804;
            }
            $this->userobj->userpasswordanswer1 = $v;
            $v = isset($_POST["userpasswordquestion2"]) ? $_POST["userpasswordquestion2"] : null;
            if ($this->test_input($v) != 0) {
                return 10805;
            }
            if (strlen($v) > 64) {
                return 10806;
            }
            $this->userobj->userpasswordquestion2 = $v;
            $v = isset($_POST["userpasswordanswer2"]) ? $_POST["userpasswordanswer2"] : null;
            if ($this->test_input($v) != 0) {
                return 10807;
            }
            if (strlen($v) > 64) {
                return 10808;
            }
            $this->userobj->userpasswordanswer2 = $v;
            $v = isset($_POST["userpasswordquestion3"]) ? $_POST["userpasswordquestion3"] : null;
            if ($this->test_input($v) != 0) {
                return 10809;
            }
            if (strlen($v) > 64) {
                return 10810;
            }
            $this->userobj->userpasswordquestion3 = $v;
            $v = isset($_POST["userpasswordanswer3"]) ? $_POST["userpasswordanswer3"] : null;
            if ($this->test_input($v) != 0) {
                return 10811;
            }
            if (strlen($v) > 64) {
                return 10812;
            }
            $this->userobj->userpasswordanswer3 = $v;
            
            //usersex
            $v = isset($_POST["usersex"]) ? $_POST["usersex"] : null;
            if($v == null || !is_string($v)) {
                return 10901;
            }
            if ($this->test_input($v) != 0) {
                return 10904;
            }
            if (strlen($v) < 1 || strlen($v) > 2) {
                return 10902;
            }
            $this->userobj->usersex = $v;
            
            //userbirthday
            $v = isset($_POST["userbirthday"]) ? $_POST["userbirthday"] : null;
            if($v == null || !is_string($v)) {
                return 11001;
            }
            if ($this->test_input($v) != 0) {
                return 11004;
            }
            if ($this->checkDateIsValid($v, array("Y-m-d")) == false) {
                return 11002;
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
            // $v = $this->$this->test_input($v);
            // if($v == null || !is_string($v)) {
            //     return 11101;
            // }
            // if ($this->test_input($v) != 0) {
            //     return 11104;
            // }
            // if (strlen($v) < 1 || strlen($v) > 64) {
            //     return 11102;
            // }
            // $this->userobj->userregisterapp = $v;
            
            return 0;
        }
        
        function is_md5($password) {
            return preg_match("/^[a-z0-9]{32}$/", $password);
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
                $key = $key."`userregisterapp`";
                $val = $val."'".$v."'";
            }
            
            // $v = $this->userobj->userloginapp;
            // if ($v != null) {
            //     $key = $key."`userloginapp`";
            //     $val = $val."'".$v."'";
            // }

            $sqlcmd = "insert `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."`(".$key.") values(".$val.");";
            $result_array = $this->sqlc($sqlcmd);
            if (is_int($result_array)) {
                return $result_array; //err
            } else {
                return 0;
            }
        }
        
        //执行SQL连接
        function sqlc($sqlcmd) {
            $con=mysqli_connect($this->sqlset->db_host,$this->sqlset->db_user,$this->sqlset->db_password,$this->sqlset->db_name,$this->sqlset->db_port);
            $sqlerrno = mysqli_connect_errno($con);
            if ($sqlerrno) {
                //die($sqlerrno);
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
                //die($sqlcmd);
                return 90101;
            }
            return 90100;
        }
        
        //查询用户名是否重复
        function chkrep($key,$val) {
            //sqlset
            $sqlcmd = "SELECT count(0) FROM `".$this->sqlset->db_user_table."` WHERE `".$key."`='".$val."';";
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

        //记录日志
        function savereg($userlogininfoid) {
            if ($userlogininfoid == null || is_int($userlogininfoid) == false) {
                $userlogininfoid = 0;
            }
            $sqlcmd = "insert `".$this->sqlset->db_name."`.`".$this->sqlset->db_loginhistory_table."`(`userlogintime`,`userloginip`,`userloginapp`,`userlogininfo`,`mode`) values('".$this->datetime."','".$this->ip."','".$this->sqlset->db_app."',".$userlogininfoid.",2);";
            $result_array = $this->sqlc($sqlcmd);
            if (is_int($result_array)) {
                return 0;
            } else {
                //print_r($result_array);
                $userrep = $result_array["count(0)"];
                if ($userrep > 0) {
                    return 1;
                }
                return 0;
            }
        }

        
        //过滤特殊字符
        function test_input($data) {
            if (is_string($data) == false) {
                return 1;
            }
            if ($data == null || strlen($data) == 0) {
                return 0;
            }
            $edata = trim($data);
            $edata = stripslashes($edata);
            $edata = htmlspecialchars($edata);
            if (strcmp($data,$edata) != 0) {
                return 2;
            }
            $pattern = $this->inputmatch;
            if (!preg_match($pattern,$data)) {
                return 3;
            }
            return 0;
        }
}

//入口
//echo $_POST["userpassword"];
$registration = new yaloginRegistration();
$registration->init();
$errid = $registration->vaild();
$html = "";
if ($errid > 0) {
    //<script type='text/javascript'>alert('请返回首页登陆');window.location='index';</script>
    //<meta http-equiv=\"refresh\" content=\"5;url=hello.html\">
    $html = "<meta http-equiv=\"refresh\" content=\"1;url=YashiUser-Alert.php?errid=".strval($errid)."&backurl=YashiUser-Registration.php\">";
    if ($errid < 11200 || $errid > 11299) {
        $saved = $registration->savereg($errid);
    }
} else {
    $errid2 = $registration->gensql();
    if ($errid2 > 0) {
        $html = "<meta http-equiv=\"refresh\" content=\"1;url=YashiUser-Alert.php?errid=".strval($errid2)."&backurl=YashiUser-Registration.php\">";
    } else {
        $html = "<meta http-equiv=\"refresh\" content=\"1;url=YashiUser-Alert.php?errid=1001&backurl=YashiUser-Registration.php\">";
    }
    $saved = $registration->savereg($errid2);
}
echo $html;
?>