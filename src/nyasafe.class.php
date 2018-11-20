<?php
class nyasafe {
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

    /**
     * @description: 检查IP地址是否处于封禁期内
     * @param time 当前时间time()
     * @return Array<Int> 状态码 和 IP表的id
     */
    function chkip($time) {
        global $nlcore;
        //获取环境信息
        $stime = date("Y-m-d H:i:s", $time);
        $ipaddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
        $proxyaddr = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : "";
        if ($ipaddr == "") return [2020402];
        //检查IP是否被封禁
        $datadic = ["ip_address" => $_SERVER['REMOTE_ADDR']];
        if ($proxyaddr != "") $datadic["proxy_ip_address"] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $ipaddresstable = $nlcore->cfg->db->tables["ip_address"];
        $result = $nlcore->db->select(["id","closing_time"],$ipaddresstable,$datadic,"","OR");
        $ipid = null;
        if ($result[0] == 1010000) {
            //如果有数据则比对时间,取ID
            $onedata = $result[2][0];
            $datatime = strtotime($onedata["closing_time"]); 
            if ($time < $datatime) return [2020403];
            $ipid = $onedata["id"];
        } else if ($result[0] == 1010001) {
            //如果没有则写入IP记录,取ID
            $datadic = array(
                "ip_address" => $ipaddr,
                "proxy_ip_address" => $proxyaddr
            );
            $result = $nlcore->db->insert($datadic,$ipaddresstable);
            if ($result[0] != 1010001) return [2020404];
            $ipid = $result[1];
        }
        if (!$ipid) return [2020402];
        return [0,$ipid];
    }
}
?>