<?php
/*
发送找回密码电子邮件
*/
require 'yaloginSafe.php';
require 'yaloginSendmail.php';
    class yaloginRetrieveviamail {

        private $ysqlc;
        public $hash;
        private $sqlset;
        private $safe;
        private $hash = "";
        private $username = "";

        public $mailaddress;

        function init() { //__constrct()
            $this->ysqlc = new yaloginSQLC();
            $this->ysqlc->init();
            $this->sqlset = $this->ysqlc->sqlset;
            $this->safe = new yaloginSafe();
        }

        //发送邮件
        function retrieve($vcode) {
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
            if($this->mailaddress == null || !is_string($this->mailaddress)) {
                return 10501;
            }
            if ($this->safe->containsSpecialCharacters($v) != 0) {
                return 10404;
            }
            if (strlen($v) < 5 || strlen($v) > 64) {
                return 10502;
            }
            if ( !$this->safe->isEmail($v) )
            {
                return 10503;
            }
            /*查询对应用户名
            mysql>SELECT `hash`,`username` FROM `userdb`.`yalogin_user` WHERE `useremail` = 'cxchope@163.com'
+------------------------------------------------------------------+--------------------+
| hash                                                             | username           |
+------------------------------------------------------------------+--------------------+
| 3605f7f321b49b10306074ba76c0db1a85be32a075cbb110f423e67950ffdbf9 | cxchope            |
+------------------------------------------------------------------+--------------------+
            */
            $sqlcmd = "SELECT `hash`,`username` FROM `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."` WHERE `useremail` = '".$this->mailaddress."';";
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
                $this->hash = $resultdata["hash"];
                $this->username = $resultdata["username"];
            }
            //计算验证码&超时时间
            $retrievepwd = date('Y-m-d H:i:s',strtotime('+1 hour')); //有效期1小时
            $retrievepwdcode = $this->safe->randhash($this->username.$this->mailaddress);
            //写入 找回密码邮箱验证码retrievepwdcode 和 找回密码邮箱验证截止日期retrievepwd
            //mysql>UPDATE `userdb`.`yalogin_user` SET `retrievepwd`='2016-09-18 22:19:46',`retrievepwdcode`='f423e67950ffdbf9' WHERE `useremail` = 'cxchope@163.com'
            $sqlcmd = "UPDATE `".$this->sqlset->db_name."`.`".$this->sqlset->db_user_table."` SET `retrievepwd`='".$retrievepwd."',`retrievepwdcode`='".$retrievepwdcode."' WHERE `useremail` = '".$this->mailaddress."';";
            $result_array = $this->ysqlc->sqlc($sqlcmd,false,false);
            if (is_int($result_array)) {
                return $result_array; //errID
            }
            //发送邮件
            if ($c->ysqlc->sqlset->mail_Enable == true) {
                $sendmail = new Sendmail();
                $sendmail->init();
                $mailresult = $sendmail->sendretrievemail($this->mailaddress, $username, $retrievepwdcode, $retrievepwd);
                if ($mailresult != null && $mailresult != -2) {
                    return $mailresult;
                }
            }
        }

        function savetryreg($userlogininfoid) {
            if (!($userlogininfoid >= 1000 && $userlogininfoid < 10000 && $this->ysqlc->sqlset->log_TryingRetrieve_OK == true) || !($userlogininfoid >= 10000 && $userlogininfoid < 100000 && $this->ysqlc->sqlset->log_TryingRetrieve_Fail == true)) {
                return -1;
            }
            $note = $this->mailaddress;
            $datetime = date("Y-m-d H:i:s");
            $ip = $_SERVER['REMOTE_ADDR'].":".$_SERVER['REMOTE_PORT']."/".$_SERVER['REMOTE_HOST'];
            $saveregr = $this->ysqlc->savereg($userlogininfoid,$this->hash,$datetime,$ip,2,$note);
            $this->errinfo = "";
            return $saveregr;
        }

        //收到激活码
        function vaild() {
            //检查数据量是否单一
            //检查是否已激活（retrievepwdcode为空）
            //检查是否过期（retrievepwd）
            //清除使用过的码（retrievepwd）
            //变更密码、二级密码、密码提示问题（新建流程）
            //记录日志和返回值
        }



    }
?>