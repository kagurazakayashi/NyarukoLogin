<?php
class nyasafe_hash {
    /**
     * @description: base64 加密
     * @param String 明文
     * @return String 密文
     */
    function base_encode($str) {
        $src  = array("/","+","=");
        $dist = array("_a","_b","_c");
        $old  = base64_encode($str);
        $new  = str_replace($src,$dist,$old);
        return $new;
    }
    /**
     * @description: base64 解密
     * @param String 密文
     * @return String 明文
     */
    function base_decode($str) {
        $src = array("_a","_b","_c");
        $dist  = array("/","+","=");
        $old  = str_replace($src,$dist,$str);
        $new = base64_decode($old);
        return $new;
    }
    /**
     * @description: 是否为MD5
     * @param String 需要判断的字符串 
     * @return Int 是否匹配 > 0 || != false
     */
    function is_md5($md5str) {
        return preg_match("/^[a-z0-9]{32}$/", $md5str);
    }
}
class nyasafe_rand {
    /**
     * @description: 生成一段随机文本
     * @param Int len 生成长度
     * @param String chars 从此字符串中抽取字符
     * @return String 新生成的随机文本
     */
    function randstr($len=32, $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ 
        abcdefghijklmnopqrstuvwxyz0123456789') {
        mt_srand($this->seed());
        $password='';
        while(strlen($password)<$len) 
            $password.=substr($chars,(mt_rand()%strlen($chars)),1); 
        return $password;
    }
    /**
     * @description: 生成随机哈希值
     * @param String salt 盐（可选信息）
     * @return String 生成的随机MD5码
     */
    function randhash($salt="") {
        $data = (double)microtime().$salt.$this->randstr(32);
        return md5($data);
    }
    /**
     * @description: 生成随机数发生器种子
     * @param String salt 盐（可选信息）
     * @return String 种子
     */
    function seed($salt="") {
        $newsalt = (double)microtime()*1000000*getmypid();
        return $newsalt.$salt;
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