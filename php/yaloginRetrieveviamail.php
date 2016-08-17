<?php
/*
发送找回密码电子邮件
*/
    class yaloginRetrieveviamail {

        private $ysqlc;
        public $hash;
        private $sqlset;

        public $mailaddress;

        function init() { //__constrct()
            $this->ysqlc = new yaloginSQLC();
            $this->ysqlc->init();
            $this->sqlset = $this->ysqlc->sqlset;
        }

        //发送激活邮件
        function retrieve() {
            //校验验证码
            //检查邮件地址合法性
            //查询对应用户名
            //计算超时时间
            //写入 找回密码邮箱验证码retrievepwdcode 和 找回密码邮箱验证截止日期retrievepwd
            //发送邮件
            //写入历史记录
        }

        //收到激活码
        function vaild() {

        }



    }
?>