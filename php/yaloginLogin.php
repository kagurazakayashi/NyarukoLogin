<?php 
/*
后端：用户登录
输入：username,userpassword,userpassword2（可选）,vcode,autologin31536000,userversion（可选）
*/
    require 'yaloginGlobal.php';
    require 'yaloginSQLC.php';
    require 'yaloginUserInfo.php';
    require 'yaloginSendmail.php';
    require 'yaloginSafe.php';
    class yaloginLogin {
        private $ysqlc;
        public $hash;
        private $sqlset;
        private $inpuser;
        private $seruser;
        public $cookiejsonarr;
        
        //创建变量
        function init() { //__constrct()
            $this->ysqlc = new yaloginSQLC();
            $this->inpuser = new YaloginUserInfo();
            $this->seruser = new YaloginUserInfo();
            $this->safe = new yaloginSafe();
            $this->cookiejsonarr = null;
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

            $this->getseruser(); //检索用户信息
            $errid = $this->verifypassword(); //验证是否可用
            if ($errid != 0) {
                return $errid;
            }

            //记录登录token和日志
            $sessiontoken = $this->safe->randhash($this->seruser->username);
            $errid = $this->savesession($sessiontoken);
            if ($errid != 0) {
                return $errid;
            }

            return 0;
        }

        //密码错误计数器+1
        //update `yalogin_user` set `userpasserr`=1 where `id`=35;
        function passworderror() {
            $userpasserr = intval($this->seruser->userpasserr) + 1;
            $sqlcmd = "update `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."` set `userpasserr`='".strval($userpasserr)."' where `hash`='".$this->seruser->hash."';";
            if (is_int($result_array)) {
                return $result_array; //err
            }
            return 0;
        }

        function savesession($sessiontoken) {
            //update `yalogin_user` set `autologin`='789' where `id`=35;
            //记录sessiontoken到数据库
            $sqlcmd = "update `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."` set `autologin`='".$sessiontoken."' where `hash`='".$this->seruser->hash."';";
            $result_array = $this->ysqlc->sqlc($sqlcmd,false,false);
            if (is_int($result_array)) {
                return $result_array; //err
            }

            $this->logout(); //注销之前的登录
            //$this->inpuser->autologin
            session_start();
            $lifeTime = time() + intval($this->inpuser->autologin);
            $this->cookiejsonarr = array(
                'sessiontoken'=>$sessiontoken,
                'sessionname'=>session_name(),
                'sessionid'=>session_id(),
                'username'=>$this->seruser->username,
                'userhash'=>$this->seruser->hash,
                'lifetime'=>$lifeTime
                );
            $cookiejson = json_encode($this->cookiejsonarr);
            $_SESSION["logininfo"] = $cookiejson;
            setcookie($this->cookiename, $cookiejson, $lifeTime, "/");
            return 0;
        }

        function logout() {
            session_unset(); //内存登出
            session_destroy(); //文件登出
            setcookie($this->cookiename()); //cookie登出
        }

        function cookiename($key = "") {
            if (strlen($key) > 0) {
                $key = "_".$key;
            }
            return "yalogin_".$this->sqlset->db_app.$key;
        }

        //检索用户信息
        //SELECT `userpasserr`,`verifymail`,`userpasswordenabled`,`userenable`,`userjurisdiction`,`userpassword`,`userpassword2`,`autologin` FROM `userdb`.`yalogin_user` WHERE `username` = 'testuser';
        function getseruser() {
            $sqlcmd = "SELECT `userpasserr`,`verifymail`,`userpasswordenabled`,`userenable`,`userjurisdiction`,`userpassword`,`userpassword2`,`autologin`,`useremail`,`verifymailcode`,`hash` FROM `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."` WHERE `username` = '".$this->inpuser->username."';";
            $result_array = $this->ysqlc->sqlc($sqlcmd,false,false);
            if (is_int($result_array)) {
                return $result_array; //err
            }
            if (count($result_array) == 0) {
                return 12104;
            } else if (count($result_array) > 1) {
                return 12105;
            }
            $this->seruser->username = $this->inpuser->username;
            $seruser = $result_array[0];
            $this->seruser->userpasserr = isset($result_array["userpasserr"]) ? $result_array["userpasserr"] : "0";
            $this->seruser->verifymail = isset($result_array["verifymail"]) ? $result_array["verifymail"] : null;
            $this->seruser->userpasswordenabled = isset($result_array["userpasswordenabled"]) ? $result_array["userpasswordenabled"] : "0";
            $this->seruser->userenable = isset($result_array["userenable"]) ? $result_array["userenable"] : null;
            $this->seruser->userjurisdiction = isset($result_array["userjurisdiction"]) ? $result_array["userjurisdiction"] : null;
            $this->seruser->userpassword = isset($result_array["userpassword"]) ? $result_array["userpassword"] : null;
            $this->seruser->userpassword2 = isset($result_array["userpassword2"]) ? $result_array["userpassword2"] : null;
            $this->seruser->autologin = isset($result_array["autologin"]) ? $result_array["autologin"] : null;
            $this->seruser->useremail = isset($result_array["useremail"]) ? $result_array["useremail"] : null;
            $this->seruser->verifymailcode = isset($result_array["verifymailcode"]) ? $result_array["verifymailcode"] : null;
            $this->seruser->hash = isset($result_array["hash"]) ? $result_array["hash"] : "";
            return 0;
        }

        //校验密码是否可用
        function verifypassword() {
            //取 userpasserr(int) 检查密码尝试错误次数，超过一定量设密码无效，并重置为0。
            $userpasserr = intval($this->seruser->userpasserr)
            if ($userpasserr > 10) {
                return 12106;
            }
            //取 verifymail(datetime) 激活有效时间，空为已激活，已超过时间否重发邮件。verifymailcode(text)
            if ($this->seruser->verifymail != null) {
                //-过期再发：
                // $dateformat = "Y-m-d H:i:s";
                // $nowstrtotime = strtotime(date($dateformat));
                // $dbstrtotime = strtotime($this->seruser->verifymail);
                // if ($nowstrtotime >= $dbstrtotime) {
                //     //激活码已经过期，执行下面的代码
                // }
                //-无论是否过期直接发：
                $mailerr = $this->resendverifymail();
                if ($mailerr != null) {
                    return intval($mailerr);
                } else {
                    return 12107;
                }
            }
            //取 userpasswordenabled(tinyint) 检查密码是否有效，无效要求强制改密码。
            $userpasswordenabledint = intval($this->seruser->userpasswordenabled);
            if ($userpasswordenabledint == 0) {
                return 12108;
            }
            //取 userenable(datetime) 校验用户是否过期，未达启用时间为封禁期。
            //userenable 是启用时间
            $dateformat = "Y-m-d H:i:s";
            $nowstrtotime = strtotime(date($dateformat));
            $dbstrtotime = strtotime($this->seruser->verifymail);
            if ($nowstrtotime < $dbstrtotime) {
                return 12109; //用户未启用
            }
            //取 userjurisdiction(int) 权限等级，1直接视为封禁。
            if ($this->seruser->userenable == null) {
                return 12110;
            } else if (intval($this->seruser->userenable) == 1) {
                return 12111;
            }
            //取 userpassword(text) 与输入的 MD6 进行校验密码。
            if ($this->seruser->userpassword == null || $this->inpuser->userpassword == null) {
                return 12112;
            }
            if ($this->seruser->userpassword != $this->inpuser->userpassword) {
                $pwderrerr = $this->passworderror();
                if ($pwderrerr != 0) {
                    return $pwderrerr;
                }
                return 12113;
            }
            //取 userpassword2(text) ，如果需要返回输入页面增加二级密码输入框。与输入的 MD6 进行校验二级密码。
            if ($this->seruser->userpassword2 != null) {
                if ($this->inpuser->userpassword2 == null) {
                    return 12114;
                } else if ($this->seruser->userpassword2 != $this->inpuser->userpassword2) {
                    $this->passworderror();
                    if ($pwderrerr != 0) {
                        return $pwderrerr;
                    }
                    return 12115;
                }
            }
            //取 authenticatorid/authenticatortoken ：校验密保令牌，暂时不做。
            //
            //
            //

            return 0;
        }

        //重新发送激活邮件
        function resendverifymail() {
            $sendmail = new Sendmail();
            $timeout = date('Y-m-d H:i:s',strtotime('+1 hour'));
            return $sendmail->sendverifymail($this->seruser->useremail, $this->seruser->username, $this->seruser->verifymailcode, $timeout);
        }

        //记录日志
        function savereg($infoid) {
            if (!($infoid >= 1000 && $infoid < 10000 && $this->ysqlc->sqlset->log_Login_OK == true) || !($infoid >= 10000 && $infoid < 100000 && $this->ysqlc->sqlset->log_Login_Fail == true)) {
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