<?php
/*
发送找回密码电子邮件
输入：useremail，vcodeimg
*/
    class yaloginRetrieveviamail {

        private $ysqlc;
        public $hash;
        private $sqlset;

        function init() { //__constrct()
            $this->ysqlc = new yaloginSQLC();
            $this->ysqlc->init();
            $this->sqlset = $this->ysqlc->sqlset;
        }

        function vaild() {
            
        }



    }
?>