<?php
/*
发送找回密码电子邮件
输入：useremail，vcodeimg
*/
    class yaloginRetrieveviamail {

        public $echomode;

        function init() {
            $this->echomode = isset($_POST["echomode"]) ? $_POST["echomode"] : "json";
        }

    }
?>