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
            die($nlcore->msg->m(1,2020101));
        }
        if (!is_numeric($value)) {
            $value = "'" . mysql_real_escape_string($value) . "'";
        }
        if ($value != $ovalue && $errdie == true) {
            die($nlcore->msg->m(1,2020102));
        }
        if ($dhtml) {
            $value = htmlspecialchars($value);
            if ($value != $ovalue && $errdie == true) {
                die($nlcore->msg->m(1,2020103));
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
     * @param String ip IP地址源字符串
     * @return Bool 是否正确
     */
    function isIPv4($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }
    /**
     * @description: 检查是否为 IPv6 地址
     * @param String ip IP地址源字符串
     * @return Bool 是否正确
     */
    function isIPv6($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }
    /**
     * @description: 检查是否为私有 IP 地址
     * @param String ip IP地址源字符串
     * @return Bool 是否正确
     */
    function isPubilcIP($ip) {
        if (filter_var($ip, FILTER_FLAG_NO_PRIV_RANGE) || filter_var($ip, FILTER_FLAG_NO_RES_RANGE)) return false;
        return true;
    }
    /**
     * @description: 判断IP地址类型，输出为存入数据库的代码
     * @param String ip IP地址源字符串
     * @return Int 0:未知,40:v4,60:v6,+1:公共地址
     */
    function iptype($ip) {
        $iptypenum = 0;
        if ($this->isIPv4($ip)) $iptypenum = 40;
        else if ($this->isIPv6($ip)) $iptypenum = 60;
        if ($this->isPubilcIP($ip)) $iptypenum ++;
        return $iptypenum;
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
     * @description: 判断是否为Base64字符串
     * @param String b64string Base64字符串
     * @param Bool urlmode 是否为转换符号后的Base64字符串
     * @return Bool 是否为Base64字符串
     */
    function isbase64($b64string,$urlmode=false) {
        if ($urlmode) {
            if (preg_match("/[^0-9A-Za-z\-_]/",$b64string) > 0) return false;
        } else {
            if (preg_match("/[^0-9A-Za-z\+\/\=]/",$b64string) > 0) return false;
        }
        return true;
    }
    /**
     * @description: 检查是否包含违禁词汇
     * @param String str 源字符串
     * @return Array<Bool,String,String> [是否包含违禁词,河蟹后的字符串,触发的违禁词] ，如果不包含违禁词，返回 [false,原字符串]
     * 违禁词列表为 JSON 一维数组，每个字符串中可以加 $wordfilterpcfg["wildcardchar"] 分隔以同时满足多个条件词。
     */
    function wordfilter($str) {
        global $nlcore;
        $wordfilterpcfg = $nlcore->cfg->app->wordfilter;
        $wordjson = ""; //词库
        if ($wordfilterpcfg["enable"] == 1) { //从 Redis 读入
            if (!$nlcore->db->initRedis()) $nlcore->msg->http403(2020301);
            $wordjson = $nlcore->db->redis->get($wordfilterpcfg["rediskey"]);
        } else if ($wordfilterpcfg["enable"] == 2) { //从 file 读入
            $jfile = fopen($wordfilterpcfg["jsonfile"], "r") or $nlcore->msg->http403(2020302);
            $wordjson = fread($jfile,filesize($wordfilterpcfg["jsonfile"]));
            fclose($jfile);
        } else {
            return [false,$str];
        }
        //删除输入字符串特殊符号
        $punctuations = $this->mbStrSplit($wordfilterpcfg["punctuations"]);
        foreach($punctuations as $punctuationword) {
            $str = str_replace($punctuationword,"",$str);
        }
        //把所有字符中的大写字母转换成小写字母
        $str = strtolower($str);
        //转为数组
        $wordjson = json_decode($wordjson);
        //搜索关键词
        $nstr = $str;
        foreach($wordjson as $keyword) {
            //同时满足多条件
            $nstr = preg_replace('/'.join(explode($wordfilterpcfg["wildcardchar"], $keyword),'.{1,'.$wordfilterpcfg["maxlength"].'}').'/',$wordfilterpcfg["replacechar"],$nstr);
            if (strcmp($str,$nstr) != 0) return [true,$nstr,$keyword];
        }
        return [false,$str];
    }
    /**
     * @description: 字符串转字符数组，可以处理中文
     * @param String str 源字符串
     * @return Array 字符数组
     */
    function mbStrSplit($str){
        return preg_split('/(?<!^)(?!$)/u' , $str);
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
            //区分IP类型
            $iptype = $this->iptype($ipaddr);
            $datadic = array(
                "ip_addresscol_category" => $iptype,
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
     * @return String 数据库中的 ID 号 或 null
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
     * @param String module 功能名称（$conf->limittime）
     * @return Array<Int,Int> [状态代码,第几次请求]
     * 如果未加载Redis则自动关闭此功能，直接返回通过，请求数返回-1
     */
    function frequencylimitation($module) {
        global $nlcore;
        $conf = $nlcore->cfg->app;
        if (!$nlcore->db->initRedis()) return [1000000,-1];
        $redis = $nlcore->db->redis;
        $key = $this->getip();
        $check = $redis->exists($key);
        if($check){
            $redis->incr($key);
            $count = $redis->get($key);
            if($count > $conf->limittime[$module][1]){
                return [2020407,$count];
            }
        } else {
            $redis->incr($key);
            $redis->expire($key,$conf->limittime[$module][0]);
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
     * @description: 从数组创建JSON、加密、base64编码、变体
     * @param String dataarray 要返回到客户端的内容字典
     * @param String secret totp加密码
     * @return Array<String> [解析后的JSON内容数组,TOTP的secret]
     */
    function encryptargv($dataarray,$secret="") {
        global $nlcore;
        //转换为json
        $json = json_encode($dataarray);
        if ($secret != "") {
            //使用secret生成totp数字
            $ga = new PHPGangsta_GoogleAuthenticator();
            $numcode = $ga->getCode($secret)+$nlcore->cfg->app->totpcompensate;
            //MD5
            $numcode = md5($secret.$numcode);
            //使用totp数字加密
            $json = xxtea_encrypt($json, $numcode);
        }
        return $this->urlb64encode($json);
    }
    /**
     * @description: 解析变体、base64解码、解密、解析JSON到数组
     * GET/POST参数：t=apptoken，j=JSON内容
     * @param String module 功能名称（$conf->limittime）
     * @return Array<String> [解析后的JSON内容数组,TOTP的secret,TOTP的token]
     */
    function decryptargv($module=null) {
        global $nlcore;
        //检查IP访问频率
        if ($module) {
            $result = $this->frequencylimitation($module);
            if ($result[0] >= 2000000) $nlcore->msg->http403($result[0]);
        }
        //获取参数，验证格式（t=哈希、j=变形base64）
        $argv = $this->getarg();
        $jsonlen = ($_SERVER['REQUEST_METHOD'] == "GET") ? $nlcore->cfg->app->jsonlen_get : $nlcore->cfg->app->jsonlen_post;
        if ((isset($argv["t"]) && !$this->is_md5($argv["t"])) || (!isset($argv["t"]) && $nlcore->cfg->app->alwayencrypt) || !isset($argv["j"]) || strlen($argv["j"]) > $jsonlen || !$this->isbase64($argv["j"],true)) {
            header('Content-Type:application/json;charset=utf-8');
            $nlcore->msg->http403(2020408);
        }
        //检查是否为重放
        $this->antireplay($argv["j"]);
        //检查 IP 是否被封禁
        $time = time();
        $stime = date("Y-m-d H:i:s", $time);
        $result = $this->chkip($time);
        if ($result[0] != 0) $nlcore->msg->http403($result[0]);
        $ipid = $result[1];
        $jsonarr = null;
        $secret = "";
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
            $numcode = $ga->getCode($secret)+$nlcore->cfg->app->totpcompensate;
            //MD5
            $numcode = md5($secret.$numcode);
            //使用totp数字解密
            $xxteadata = $this->urlb64decode($argv["j"]);
            $decrypt_data = xxtea_decrypt($xxteadata, $numcode);
            if (strlen($decrypt_data) == 0) $nlcore->msg->http403(2020411);
            $jsonarr = json_decode($decrypt_data,true);
        } else {
            $jsonarr = json_decode($this->urlb64decode($argv["j"]),true);
        }
        //解析json
        if (!$jsonarr || count($jsonarr) == 0) $nlcore->msg->http403(2020410);
        //检查API版本是否一致
        if (!isset($jsonarr["apiver"]) || intval($jsonarr["apiver"]) != 1) $nlcore->msg->http403(2020412);
        //检查APP是否有效
        if (!isset($jsonarr["appid"]) || !isset($jsonarr["appsecret"]) || !$this->isNumberOrEnglishChar($jsonarr["appid"],1,64) || !$this->isNumberOrEnglishChar($jsonarr["appsecret"],32,32) || $this->chkappsecret($jsonarr["appid"],$jsonarr["appsecret"]) == null) $nlcore->msg->http403(2020401);
        return [$jsonarr,$secret,$argv["t"]];
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
        if ($mod4) $data .= substr('====', $mod4);
        return base64_decode($data);
    }

    // function getcaptcha() {
    //     global $nlcore;
    // }
    //TODO: 重放攻击防止机制，利用 Redis 记录相同的指令哈希
    function antireplay($jstr) {
        //指令MD5
        //$jhash = md5($jstr);
        //查询是否是重放
        
        //写入记录
    }
}
?>