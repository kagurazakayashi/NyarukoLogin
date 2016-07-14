<?php 
/*
后端：用户激活
*/
    require 'yaloginGlobal.php';
    require 'yaloginSQLC.php';
    class yaloginRegistration {
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
            // if(is_array($_GET)&&count($_GET)>0) {
            //     return 10201;
            // }

            //echomode
            $this->echomode = isset($_GET["echomode"]) ? $_GET["echomode"] : "json";
            
            //acode
            $v = isset($_GET["acode"]) ? $_GET["acode"] : null;
            if($v != null){
                if (strlen($v) == 64) {
                    if (preg_match('/^[0-9a-z]*$/',$v)) {
                        $result_array = $this->gensql($v);
                        if (is_int($result_array)) {
                            return $result_array; //errID
                        } else {
                            return $this->vailda($result_array);
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
                    $dateformat = "Y-m-d H:i:s";
                    $nowstrtotime = strtotime(date($dateformat));
                    $dbstrtotime = strtotime($resultdata["verifymail"]);
                    if ($nowstrtotime < $dbstrtotime) {
                        $this->hash = "";
                        if (isset($resultdata["hash"]) == true && $resultdata["hash"] == $nowtime) {
                            $this->hash = $resultdata["hash"];
                            //激活账户（清除verifymail）
                            $aresult = $this->actusersql();
                            if (isset($aresult) && is_int($aresult)) {
                                return 11405;
                            }
                            //记录日志和返回值(在上一层继续)
                            return 1003;
                        } else {
                            return 11406;
                        }
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
        function actusersql() {
            $sqlcmd = "update `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."` set `verifymail`=null where `hash`='".$this->hash."'";
            $result_array = $this->ysqlc->sqlc($sqlcmd,false,true);
            return $result_array;
        }

        //记录日志
        function savereg($infoid) {
            $datetime = date("Y-m-d H:i:s");
            $ip = $_SERVER['REMOTE_ADDR'].":".$_SERVER['REMOTE_PORT']."/".$_SERVER['REMOTE_HOST'];
            $saveregr = $this->ysqlc->savereg($infoid,$this->hash,$datetime,$ip,3);
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