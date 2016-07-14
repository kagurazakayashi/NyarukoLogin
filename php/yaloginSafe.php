<?php 
    require 'md6.php';
    class yaloginSafe {

        //MD6 $this->safe->md6hash($data);
        function md6hash($data) {
            $md6m = new md6hash();
            $result = $md6m->hex($data);
            return $result;
        }

        //随机文本生成 $this->safe->randstr(32);
        function randstr($len=6, $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ 
            abcdefghijklmnopqrstuvwxyz0123456789') { 
            mt_srand((double)microtime()*1000000*getmypid()); 
            $password='';
            while(strlen($password)<$len) 
            $password.=substr($chars,(mt_rand()%strlen($chars)),1); 
            return $password;
        }

        //识别是否有特殊字符 $this->safe->containsSpecialCharacters($data);
        function containsSpecialCharacters($data,$inputmatch = "/^[^\/\'\\\"#$%&\^\*]+$/") {
            if (is_string($data) == false) {
                return 90210;
            }
            if ($data == null || strlen($data) == 0) {
                return 0;
            }
            $edata = trim($data);
            $edata = stripslashes($edata);
            $edata = htmlspecialchars($edata);
            if (strcmp($data,$edata) != 0) {
                return 90200;
            }
            if ($inputmatch == null || $data == null) {
                return 0; //3;
            }
            if (!preg_match($inputmatch,$data)) {
                return 90203;
            }
            return 0;
        }

        function clearSpecialCharacters($data) {
            if ($data == null || is_string($data) == false || strlen($data) == 0) {
                return null;
            }
            $edata = trim($data);
            $edata = stripslashes($edata);
            $edata = htmlspecialchars($edata);
            return $edata;
        }
    }
?>