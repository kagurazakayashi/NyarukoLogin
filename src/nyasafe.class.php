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
    function randstr($len=32, $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
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
     * @description: 检查 IP 地址是否处于封禁期内
     * @param time 当前时间time()
     * @return Array<Int> 状态码 和 IP表的id
     */
    function chkip($time) {
        global $nlcore;
        //获取环境信息
        $ipaddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
        $proxyaddr = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : "";
        if ($ipaddr == "") return [2020402];
        //检查IP是否被封禁
        $datadic = ["ip_address" => $_SERVER['REMOTE_ADDR']];
        if ($proxyaddr != "") $datadic["proxy_ip_address"] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $table = $nlcore->cfg->db->tables["ip_address"];
        $result = $nlcore->db->select(["id","closing_time"],$table,$datadic,"","OR");
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
            $result = $nlcore->db->insert($table,$datadic);
            if ($result[0] != 1010001) return [2020404];
            $ipid = $result[1];
        }
        if (!$ipid) return [2020402];
        return [0,$ipid];
    }
    /**
     * @description: 检查 APP 是否已经注册 appname 和 appsecret
     * @param String appname 已注册的应用名
     * @param String appsecret 已注册的应用密钥app_id
     */
    function chkappsecret($appname,$appsecret) {
        global $nlcore;
        $table = $nlcore->cfg->db->tables["external_app"];
        $whereDic = array(
            "app_id" => $appname,
            "app_secret" => $appsecret
        );
        $result = $nlcore->db->select(["id"],$table,$whereDic);
        if (isset($result[2][0]["id"]) && intval($result[2][0]["id"]) > 0) return $result[2][0]["id"];
        return null;
    }
    /**
     * @description: 获得真实IP
     * @return String IP地址
     */
    function getip() {
        $ip = "";
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    /**
     * @description: 检查当前IP是否到达接口访问频率限制
     * @return Array<Int,Int> [状态代码,第几次请求]
     * 如果未加载Redis则自动关闭此功能，直接返回通过，请求数返回-1
     */
    function frequencylimitation() {
        global $nlcore;
        $conf = $nlcore->cfg->iplimit;
        if (!class_exists("Redis") && !$conf->frequency) {
            if ($conf->ignoreerr) return [1000000,-1];
            return [2010200];
        }
        $redis = new Redis();
        try {
            $redis->connect($conf->redis_host, $conf->redis_port);
        } catch (Exception $e){
            if ($conf->ignoreerr) return [1000000,-1];
            echo $e;
            return [2010201];
        }
        $redis->auth($conf->redis_auth);
        $key = $this->getip();
        $check = $redis->exists($key);
        if($check){
            $redis->incr($key);
            $count = $redis->get($key);
            if($count > $conf->limittime["getlinktotp"][1]){
                return [2020407,$count];
            }
        } else {
            $redis->incr($key);
            $redis->expire($key,$conf->limittime["getlinktotp"][0]);
        }
        $count = $redis->get($key);
        return [1000000,$count];
    }
    /**
     * @description: 检查数据提交方式是否被允许，并自动选择提交方式获取数据
     * @param String allowmethod 允许的提交方式数组
     * @return Array/String 客户端提交的数据
     */
    function getarg($allowmethod=["POST","GET"]) {
        global $nlcore;
        $argv = null;
        $jsonlen = -1;
        if (!isset($_SERVER['REQUEST_METHOD'])) die(header('HTTP/1.1 405 Method Not Allowed'));
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == "POST" && in_array("POST",$allowmethod)) {
            $argv = $_POST;
            $jsonlen = $nlcore->cfg->app->jsonlen_post;
        } else if ($method == "GET" && in_array("GET",$allowmethod)) {
            $argv = $_GET;
            $jsonlen = $nlcore->cfg->app->jsonlen_get;
        } else if ($method == "FILES" && in_array("FILES",$allowmethod)) {
            $argv = $_FILES;
        } else if ($method == "PUT" && in_array("PUT",$allowmethod)) {
            $argv = $_PUT;
        } else if ($method == "DELETE" && in_array("DELETE",$allowmethod)) {
            $argv = $_SERVER['REQUEST_URI'];
        } else {
            die(header('HTTP/1.1 405 Method Not Allowed'));
        }
        return $argv;
    }
    /**
     * @description: 解析、解密输入的内容
     * GET/POST参数：t=apptoken，j=JSON内容
     * @return Array 解析后的JSON内容
     */
    function decryptargv() {
        global $nlcore;
        //检查IP访问频率
        $result = $this->frequencylimitation();
        if ($result[0] >= 2000000) $nlcore->msg->http403($result[0]);
        //获取参数
        $argv = $this->getarg();
        $jsonlen = ($_SERVER['REQUEST_METHOD'] == "GET") ? $nlcore->cfg->app->jsonlen_get : $nlcore->cfg->app->jsonlen_post;
        if ((isset($argv["t"]) && !$this->is_md5($argv["t"])) || (!isset($argv["t"]) && $nlcore->cfg->app->alwayencrypt) || !isset($argv["j"]) || strlen($argv["j"]) > $jsonlen) {
            header('Content-Type:application/json;charset=utf-8');
            $nlcore->msg->http403(2020408);
        }
        //检查 IP 是否被封禁
        $time = time() + $nlcore->cfg->app->timezone;
        $stime = date("Y-m-d H:i:s", $time);
        $result = $this->chkip($time);
        if ($result[0] != 0) $nlcore->msg->http403($result[0]);
        $ipid = $result[1];
        $json = "";
        if (isset($argv["t"])) {
            //查询apptoken对应的secret
            $datadic = [
                "apptoken" => $argv["t"]
            ];
            $result = $nlcore->db->select(["secret"],$nlcore->cfg->db->tables["session_totp"],$datadic);
            //空或查询失败都视为不正确
            if (!$result || $result[0] != 1010000 || !isset($result[2][0][0])) $nlcore->msg->http403(2020409);
            $secret = $result[2][0][0];
            //使用secret生成totp数字
            $ga = new PHPGangsta_GoogleAuthenticator();
            $numcode = $ga->getCode($secret);
            //使用totp数字解密
            $xxteadata = $this->urlb64decode($argv["j"]);
            $decrypt_data = xxtea_decrypt($xxteadata, $numcode);
            if (strlen($decrypt_data) == 0) $nlcore->msg->http403(2020411);
            $json = json_decode($decrypt_data);
        } else {
            $json = json_decode($this->urlb64decode($argv["j"]));
        }
        //解析json
        if (strlen($json) == 0) $nlcore->msg->http403(2020410);
        return $json;
    }
    /**
     * @description: 进行Base64编码，并取代一些符号
     * “+”改成“-”, “/”改成“_”, “=”删除
     * @param Data fdata 需要使用Base64编码的数据
     * @return Array 加密后的字符串
     */
    function urlb64encode($fdata) {
        $data = str_replace(['+','/','='],['-','_',''],base64_encode($fdata));
        return $data;
    }
    /**
     * @description: 撤回取代的一些符号，并解析Base64编码
     * 改回，适量添加“=”
     * @param String fstring Base64编码后的字符串
     * @return Array 解析后的字符串
     */
    function urlb64decode($fstring) {
        $data = str_replace(['-','_'],['+','/'],$fstring);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
}
?>