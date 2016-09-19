<?php
/*
发送找回密码电子邮件
*/
require 'yaloginSafe.php';
require 'yaloginSendmail.php';
require 'yaloginUserInfo.php';
    class yaloginRetrieveviamail {

        private $ysqlc;
        public $hash;
        private $sqlset;
        private $safe;
        private $userobj;

        function init() { //__constrct()
            $this->userobj = new YaloginUserInfo();
            $this->ysqlc = new yaloginSQLC();
            $this->ysqlc->init();
            $this->sqlset = $this->ysqlc->sqlset;
            $this->safe = new yaloginSafe();
        }

        //发送邮件
        function retrieve($vcode,$mailaddress) {
            //校验验证码
            @session_start();
            if ($this->sqlset->vcode_verification == true) {
                if($vcode != null){
                    if ($this->safe->containsSpecialCharacters($v) != 0) {
                        return 90404;
                    }
                    if(!isset($_SESSION["authnum_session"])) {
                        return 90401;
                    }
                    $va = strtoupper($vcode);
                    $vb = strtoupper($_SESSION["authnum_session"]);
                    if($va!=$vb){
                        $_SESSION["authnum_session"] = null;
                        return 90402;
                    }
                } else {
                    return 90403;
                }
                $_SESSION["authnum_session"] = null;
            }
            //检查邮件地址合法性
            if($mailaddress == null || !is_string($mailaddress)) {
                return 10501;
            }
            if ($this->safe->containsSpecialCharacters($mailaddress) != 0) {
                return 10404;
            }
            if (strlen($mailaddress) < 5 || strlen($mailaddress) > 64) {
                return 10502;
            }
            if ( !$this->safe->isEmail($mailaddress) )
            {
                return 10503;
            }
            $this->userobj->useremail = $mailaddress;
            /*查询对应用户名
            mysql>SELECT `hash`,`username` FROM `userdb`.`yalogin_user` WHERE `useremail` = 'cxchope@163.com'
+------------------------------------------------------------------+--------------------+
| hash                                                             | username           |
+------------------------------------------------------------------+--------------------+
| 3605f7f321b49b10306074ba76c0db1a85be32a075cbb110f423e67950ffdbf9 | cxchope            |
+------------------------------------------------------------------+--------------------+
            */
            $sqlcmd = "SELECT `hash`,`username` FROM `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."` WHERE `useremail` = '".$mailaddress."';";
            $result_array = $this->ysqlc->sqlc($sqlcmd,true,false);
            if (is_int($result_array)) {
                return $result_array; //errID
            }
            $arrcount = count($result_array);
            if($arrcount <= 0) {
                return 12104;
            } else if ($arrcount > 1) {
                return 12105;
            } else {
                $resultdata = $result_array[0];
                if (!isset($resultdata["hash"]) || !isset($resultdata["username"])) {
                    return 13007;
                }
                $this->userobj->hash = $resultdata["hash"];
                $this->userobj->username = $resultdata["username"];
            }
            //计算验证码&超时时间
            $retrievepwd = date('Y-m-d H:i:s',strtotime('+1 hour')); //有效期1小时
            $retrievepwdcode = $this->safe->randhash($this->userobj->username.$mailaddress);
            //写入 找回密码邮箱验证码retrievepwdcode 和 找回密码邮箱验证截止日期retrievepwd
            //mysql>UPDATE `userdb`.`yalogin_user` SET `retrievepwd`='2016-09-18 22:19:46',`retrievepwdcode`='f423e67950ffdbf9' WHERE `useremail` = 'cxchope@163.com'
            $sqlcmd = "UPDATE `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."` SET `retrievepwd`='".$retrievepwd."',`retrievepwdcode`='".$retrievepwdcode."' WHERE `useremail` = '".$mailaddress."';";
            $result_array = $this->ysqlc->sqlc($sqlcmd,false,false);
            if (is_int($result_array)) {
                return $result_array; //errID
            }
            //发送邮件
            if ($c->ysqlc->sqlset->mail_Enable == true) {
                $sendmail = new Sendmail();
                $sendmail->init();
                $mailresult = $sendmail->sendretrievemail($mailaddress, $username, $retrievepwdcode, $retrievepwd);
                if ($mailresult != null && $mailresult != -2) {
                    return $mailresult;
                }
            }
        }

        function savetryreg($userlogininfoid) {
            if (!($userlogininfoid >= 1000 && $userlogininfoid < 10000 && $this->ysqlc->sqlset->log_TryingRetrieve_OK == true) || !($userlogininfoid >= 10000 && $userlogininfoid < 100000 && $this->ysqlc->sqlset->log_TryingRetrieve_Fail == true)) {
                return -1;
            }
            $note = $this->userobj->mailaddress;
            $datetime = date("Y-m-d H:i:s");
            $ip = $_SERVER['REMOTE_ADDR'].":".$_SERVER['REMOTE_PORT']."/".$_SERVER['REMOTE_HOST'];
            $saveregr = $this->ysqlc->savereg($userlogininfoid,$this->userobj->hash,$datetime,$ip,2,$note);
            $this->errinfo = "";
            return $saveregr;
        }

        /*收到激活码
        先设置要修改的属性：
        $this->userobj->userpassword
        $this->userobj->userpassword2
        $this->userobj->userpasswordquestion1
        $this->userobj->userpasswordanswer1
        $this->userobj->userpasswordquestion2
        $this->userobj->userpasswordanswer2
        $this->userobj->userpasswordquestion3
        $this->userobj->userpasswordanswer3
        */
        function vaild($acode) {
            //检查激活码格式
            if($acode == null){
                return 11302;
            }
            if (strlen($acode) != 64) {
                return 11303;
            }
            if (!preg_match('/^[0-9a-z]*$/',$v)) {
                return 11304;
            }
            //取激活码相关数据
            $sqlcmd = "SELECT `hash`,`retrievepwd`,`useremail` FROM `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."` WHERE `retrievepwdcode` = '".$acode."';";
            $result_array = $this->ysqlc->sqlc($sqlcmd,true,false);
            if (is_int($result_array)) {
                return $result_array; //errID
            }
            //检查数据量是否单一
            $arrcount = count($result_array);
            if($arrcount <= 0) {
                return 11401;
            }
            if ($arrcount > 1) {
                return 11402;
            }
            //检查是否已激活（retrievepwdcode为空）
            $resultdata = $result_array[0];
            if (isset($resultdata["verifymail"]) == false || $resultdata["verifymail"] == null || $resultdata["verifymail"] == "") {
                return 11403;
            }
            //检查是否过期（retrievepwd）
            $dateformat = "Y-m-d H:i:s";
            $nowstrtotime = strtotime(date($dateformat));
            $dbstrtotime = strtotime($resultdata["retrievepwd"]);
            if ($nowstrtotime >= $dbstrtotime) {
                return 11404;
            }
            $this->userobj->hash = "";
            if (!isset($resultdata["hash"])) {
                return 11406;
            }
            $this->userobj->hash = $resultdata["hash"];
            //清除使用过的码（retrievepwd）
            $sqlcmd = "update `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."` set `retrievepwd`=null where `hash`='".$this->userobj->hash."'";
            $result_array = $this->ysqlc->sqlc($sqlcmd,false,true);
            if (!isset($result_array) && is_int($result_array)) {
                return 11405;
            }
            //变更密码、二级密码、密码提示问题（新建流程）
            //记录日志和返回值（由连接器处理）
            return 1008;
        }



    }
?>