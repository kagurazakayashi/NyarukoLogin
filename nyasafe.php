<?php
class nyasafe_hash {
    //base64加密
    function base_encode($str) {
        $src  = array("/","+","=");
        $dist = array("_a","_b","_c");
        $old  = base64_encode($str);
        $new  = str_replace($src,$dist,$old);
        return $new;
    }
    //base64解密
    function base_decode($str) {
        $src = array("_a","_b","_c");
        $dist  = array("/","+","=");
        $old  = str_replace($src,$dist,$str);
        $new = base64_decode($old);
        return $new;
    }
    //是否为MD5
    function is_md5($md5str) {
        return preg_match("/^[a-z0-9]{32}$/", $md5str);
    }
}
class nyasafe_rand {
    //随机文本生成 randstr(32);
    function randstr($len=6, $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ 
        abcdefghijklmnopqrstuvwxyz0123456789') { 
        mt_srand((double)microtime()*1000000*getmypid()); 
        $password='';
        while(strlen($password)<$len) 
        $password.=substr($chars,(mt_rand()%strlen($chars)),1); 
        return $password;
    }
    //随机哈希值生成
    function randhash($userinfo) {
        $data = date('YmdHis').$userinfo.$this->randstr(32);
        $result = md5($data);
        return $result;
    }
}
class nyasafe_str {
    //检查字符串中是否有非法字符 errdie=出错是否终止 
    function safestr($value,$errdie=false,$dhtml=true) {
        $ovalue = $value;
        if (get_magic_quotes_gpc()) {
            $value = stripslashes($value);
        }
        if ($value != $ovalue && $errdie == true) {
            header('Content-type:text/json');
            die(json_encode(array("stat"=>2201,"msg"=>"字符格式不正确。")));
        }
        if (!is_numeric($value)) {
            $value = "'" . mysql_real_escape_string($value) . "'";
        }
        if ($value != $ovalue && $errdie == true) {
            header('Content-type:text/json');
            die(json_encode(array("stat"=>2202,"msg"=>"SQL语句不正确。")));
        }
        if ($dhtml) {
            $value = htmlspecialchars($value);
            if ($value != $ovalue && $errdie == true) {
                header('Content-type:text/json');
                die(json_encode(array("stat"=>2203,"msg"=>"不可以包含HTML代码。")));
            }
        }
        return $value;
    }
}
?>