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
    /**
     * @description: 过滤字符串中的非法字符
     * @param String value 源字符串
     * @param Bool errdie 如果出错则完全中断执行，返回错误信息JSON
     * @param Bool dhtml 是否将HTML代码也视为非法字符
     * @return String 经过过滤的字符
     */
    function safestr($value,$errdie=false,$dhtml=true) {
        global $nlcore;
        $ovalue = $value;
        if (get_magic_quotes_gpc()) {
            $value = stripslashes($value);
        }
        if ($value != $ovalue && $errdie == true) {
            die($nlcore->msg->m(2020101));
        }
        if (!is_numeric($value)) {
            $value = "'" . mysql_real_escape_string($value) . "'";
        }
        if ($value != $ovalue && $errdie == true) {
            die($nlcore->msg->m(2020102));
        }
        if ($dhtml) {
            $value = htmlspecialchars($value);
            if ($value != $ovalue && $errdie == true) {
                die($nlcore->msg->m(2020103));
            }
        }
        return $value;
    }
    /**
     * @description: 检查是否为电子邮件地址
     * @param String str 源字符串
     * @return Bool 是否正确
     */
    function isEmail($str) {
        $checkmail="/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/";//定义正则表达式
        if(isset($str) && $str!=""){//判断文本框中是否有值
            if(preg_match($checkmail,$str)){//用正则表达式函数进行判断
               return true;
            }else{
               return false;
            }
        }
    }
    /**
     * @description: 检查是否为 IPv4 地址
     * @param String str 源字符串
     * @return Bool 是否正确
     */
    function isIPv4($str) {
        $checkmail="'^((25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$'";//定义正则表达式
        if(isset($str) && $str!=""){//判断文本框中是否有值
            if(preg_match($checkmail,$str)){//用正则表达式函数进行判断
               return true;
            }else{
               return false;
            }
        }
    }
    /**
     * @description: 检查是否为 IPv6 地址
     * @param String str 源字符串
     * @return Bool 是否正确
     */
    function isIPv6($str) {
        $checkmail="/^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/";
        if(isset($str) && $str!=""){//判断文本框中是否有值
            if(preg_match($checkmail,$str)){//用正则表达式函数进行判断
               return true;
            }else{
               return false;
            }
        }
    }
    /**
     * @description: 检查是否为整数数字字符串
     * @param String str 源字符串
     * @return Bool 是否正确
     */
    function isInt($str) {
        $v = is_numeric($str) ? true : false;//判断是否为数字或数字字符串
        if ($v) {
            if (strpos($str,".")) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
    /**
     * @description: 检查字符串是否仅包含字母和数字并满足指定长度条件
     * @param String str 源字符串
     * @param Int minlen 至少需要多长（可选,默认-1不限制）
     * @param Int maxlen 至多需要多长（可选,默认-1不限制）
     * @return Bool 是否正确
     */
    function isNumberOrEnglishChar($str,$minlen=-1,$maxlen=-1) {
        if (preg_match("/^[A-Za-z0-9]+$/i",$str) == 0) return false;
        $len = strlen($str);
        if ($minlen != -1 && $len < $minlen) return false;
        if ($maxlen != -1 && $len > $maxlen) return false;
        return true;
    }
    /**
     * @description: 检查是否为中国手机电话号码格式
     * @param String str 源字符串
     * @return Bool 是否正确
     */
    function isPhoneNumCN($str) {
        $checkmail="/^1[34578]\d{9}$/";//定义正则表达式
        if(isset($str) && $str!=""){//判断文本框中是否有值
            if(preg_match($checkmail,$str)){//用正则表达式函数进行判断
               return true;
            }else{
               return false;
            }
        }
    }
    /**
     * @description: 检查是否包含违禁词汇
     * @param String str 源字符串
     * @return Array<String> 发现的违禁词数组
     */
    function banWord($str) {

    }
}
class nyasafe {
    public $hash;
    public $rand;
    public $str;
    function __construct() {
        $this->hash = new nyasafe_hash();
        $this->rand = new nyasafe_rand();
        $this->str = new nyasafe_str();
    }
    function __destruct() {
        $this->hash = null; unset($this->hash);
        $this->rand = null; unset($this->rand);
        $this->str = null; unset($this->str);
    }
}
// echo "测试：";
// $nyasafe_hashobj = new nyasafe_str();
// echo $nyasafe_hashobj->isPhoneNumCN($_GET["str"]);
?>