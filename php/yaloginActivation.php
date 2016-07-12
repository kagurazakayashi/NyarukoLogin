<?php 
/*
后端：用户注册
输入：
*/
    require 'yaloginUserInfo.php';
    require 'yaloginGlobal.php';
    require 'yaloginSendmail.php';
    require 'yaloginSQLC.php';
    require 'yaloginSafe.php';
    class yaloginRegistration {
        
        // public $userobj;
        // private $datetime, $ip;
        // private $sqlset;
        // private $inputmatch;
        // private $app;
        // private $safe;
        // private $errinfo = "";
        private $ysqlc;
        // public $echomode;
        // public $globalsett;
        
        //创建变量
        function init() { //__constrct()
            // $this->userobj = new YaloginUserInfo();
            // $this->globalsett = new YaloginGlobal();
            // $this->safe = new yaloginSafe();
            $this->ysqlc = new yaloginSQLC();
            $this->ysqlc->init();
            // $this->sqlset = $this->ysqlc->sqlset;
            // $this->inputmatch = $this->inputmatch;
            // date_default_timezone_set("PRC");
            // $this->datetime = date("Y-m-d h:i:s");
            // $this->ip = $_SERVER['REMOTE_ADDR'].":".$_SERVER['REMOTE_PORT']."/".$_SERVER['REMOTE_HOST'];
        }
        
        //验证输入
        function vaild() { // -> int
            if(is_array($_GET)&&count($_GET)>0) {
                return 10201;
            }

            //echomode
            $this->echomode = isset($_POST["echomode"]) ? $_POST["echomode"] : "json";
            
            //acode
            $v = isset($_POST["acode"]) ? $_POST["acode"] : null;
            if($v != null){
                if (strlen($v) == 32) {
                    if (preg_match('/^[0-9a-z]*$/g',$v)) {
                        $result_array = $this->gensql($v);
                        if (is_int($result_array)) {
                            return $result_array; //errID
                        } else {
                            return vailda($result_array);
                        }
                    } else {
                        return 11304;
                    }
                } else {
                    return 11303;
                }
            } else {
                return 11302;
            }
            return 0;
        }

        function vailda($result_array) {
            //检查数据量是否单一
            $arrcount = count($result_array);
            if($arrcount <= 0) {
                return 11401;
            } else if ($arrcount > 1) {
                return 11402;
            } else {
                //检查是否已激活（verifymail为空）
                $resultdata = $result_array[0];
                if (isset($resultdata["verifymail"]) == false || $resultdata["verifymail"] == null || $resultdata["verifymail"] == "") {
                    return 11403;
                } else {
                    //检查是否过期（verifymail）
                    $nowtime = date("Y-m-d H:i:s");
                    if (isset($resultdata["verifymail"]) == true && $resultdata["verifymail"] == $nowtime) {
                        //激活账户（清除verifymail）
                        $aresult = $this->actusersql($hash);
                        if (isset($aresult) && is_int($aresult)) {
                            return 11405;
                        }
                        //记录日志和返回值(在上一层继续)
                        return 1003;
                    } else {
                        return 11404;
                    }
                }
            }
        }
        
        //创建SQL语句
        /*
mysql>SELECT `hash`,`verifymail`,`useremail` FROM `userdb`.`yalogin_user` WHERE `verifymailcode` = '5538a389dee5b32ce59e2ad0d3be13dc0c217ef0b7a2561d2455ceb263c31094'
+------------------------------------------------------------------+----------------------+---------------------+
| hash                                                             | verifymail           | useremail           |
+------------------------------------------------------------------+----------------------+---------------------+
| 5135459c167eef60c7d12df1c9d71a72821129984ba0984a9bb9e12bd33b094b | 2016-06-22 01:26:35  | test@test.test      |
+------------------------------------------------------------------+----------------------+---------------------+
        */
        function gensql($acode) {
            $sqlcmd = "SELECT `hash`,`verifymail`,`useremail` FROM `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."` WHERE `verifymailcode` = '".$acode."';";
            $result_array = $this->ysqlc->sqlc($sqlcmd,true,false);
            return $result_array;
        }
        //激活用户
        function actusersql($hash) {
            $sqlcmd = "update `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."` set `verifymail`=null where `hash`='".$hash."'";
            $result_array = $this->ysqlc->sqlc($sqlcmd,false,true);
            return $result_array;
        }

        //记录日志
        function savereg($userlogininfoid) {
            $saveregr = $this->ysqlc->savereg($userlogininfoid,$this->userobj->hash,$this->datetime,$this->ip,2);
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