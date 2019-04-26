<?php
class nyasafe {
    /**
     * @description: 是否为MD5
     * @param String 需要判断的字符串
     * @return Int 是否匹配 > 0 || != false
     */
    function is_md5($str) {
        return preg_match("/^[a-z0-9]{32}$/", $str);
    }
    /**
     * @description: 是否为MD6
     * @param String 需要判断的字符串
     * @return Int 是否匹配 > 0 || != false
     */
    function is_md6($str) {
        return preg_match("/^[a-z0-9]{64}$/", $str);
    }
    /**
     * @description: 取MD6哈希值
     * @param String 要进行哈希的字符串
     * @return String 64位MD6哈希值
     */
    function md6($str) {
        $md6 = new md6hash();
        return $md6->hex($str);
    }
    /**
     * @description: 生成一段随机文本
     * @param Int len 生成长度
     * @param String chars 从此字符串中抽取字符
     * @return String 新生成的随机文本
     */
    function randstr($len=32, $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
        mt_srand($this->seed());
        $password = "";
        while(strlen($password)<$len) $password .= substr($chars, (mt_rand()%strlen($chars)), 1);
        return $password;
    }
    /**
     * @description: 生成随机哈希值
     * @param String salt 盐（可选）
     * @param Boolean md6 使用MD6进行哈希（可选）
     * @return String 生成的随机MD5/MD6码
     */
    function randhash($salt="",$md6=false) {
        $data = (double)microtime().$salt.$this->randstr(32);
        return md5($data);
    }
    /**
     * @description: 生成随机数发生器种子
     * @param String salt 盐（可选）
     * @return String 种子
     */
    function seed($salt="") {
        $newsalt = (double)microtime()*1000000*getmypid();
        return $newsalt.$salt;
    }
    /**
     * @description: 获得当前时间
     * @param String timezone PHP时区代码（可选，默认为配置文件中配置的时区）
     * @param Int settime 自定义时间戳（秒）
     * @return Array[Datetime,String] 返回时间日期对象和时间日期字符串
     */
    function getdatetime($timezone=null,$settime=null) {
        if ($timezone) {
            $timezone_out = date_default_timezone_get();
            date_default_timezone_set($timezone);
        }
        $timestamp = $settime ? $settime : time();
        $timestr = date('Y-m-d H:i:s', $timestamp);
        if ($timezone) date_default_timezone_set($timezone_out);
        return [$timestamp,$timestr];
    }
    /**
     * @description: 检查时间戳差异是否大于配置文件中的值
     * 差异太大直接返回错误信息到客户端
     */
    function timestampdiff($timestamp1,$timestamp2) {
        global $nlcore;
        $timestampabs = abs($timestamp1 - $timestamp2);
        if ($timestampabs > $nlcore->cfg->app->timestamplimit) {
            $nlcore->msg->stopmsg(2020413);
        }
    }
    /**
     * @description: 过滤字符串中的非法字符
     * @param String value 源字符串
     * @param Bool errdie 如果出错则完全中断执行，返回错误信息JSON
     * @param Bool dhtml 是否将HTML代码也视为非法字符
     * @param String totpsecret 加密传输密钥（可选,留空不加密）
     * @return String 经过过滤的字符
     */
    function safestr($value,$errdie=true,$dhtml=false,$totpsecret=null) {
        global $nlcore;
        $ovalue = $value;
        if (get_magic_quotes_gpc()) {
            $value = stripslashes($value);
        }
        if ($value != $ovalue && $errdie == true) {
            $nlcore->msg->stopmsg(2020101,$totpsecret);
        }
        if ($dhtml) {
            $value = htmlspecialchars($value);
            if ($value != $ovalue && $errdie == true) {
                $nlcore->msg->stopmsg(2020103,$totpsecret);
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
        if (strlen($str) > 64) return false;
        $checkmail="/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/";
        if(isset($str) && $str!="") {
            if (preg_match($checkmail,$str)) {
                return true;
            } else {
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
     * @description: 判断字符类型
     * @param String str 源字符串
     * @return Int 0:其他字符 1:数字 2:小写字母 3:大写字母
     */
    function chartype($str) {
        if (preg_match("/^\d*$/",$str)) {
            return 1;
        } else if (preg_match('/^[a-z]+$/', $str)) {
            return 2;
        } else if (preg_match('/^[A-Z]+$/', $str)) {
            return 3;
        } else {
            return 0;
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
        $checktel="/^1[345789]\d{9}$/";
        if(isset($str) && $str != ""){
            if(preg_match($checktel,$str)){
                return true;
            }
        }
        return false;
    }
    /**
     * @description: 分离手机号码中的国别码（如果没有国别码默认+86），并去除所有符号
     * @param String telstr 电话号码字符串
     * @return Array[String,String] 国别码字符串和电话号码字符串
     */
    function telarea($telstr) {
        $area = "86";
        $tel = "";
        if (!preg_match("/^[\+a-z\d\-( )]*$/i",$telstr)) {
            return ["",""];
        }
        if (substr($telstr, 0, 1) == '+' || substr($telstr, 0, 2) == '00') {
            $telarr = explode(" ", $telstr);
            $area = array_shift($telarr);
            $tel = implode("", $telarr);
        } else {
            $tel = $telstr;
        }
        return [$this->findNum($area),$this->findNum($tel)];
    }
    /**
     * @description: 取出字符串中的所有数字
     * @param String str 原字符串
     * @return String 纯数字字符串
     */
    function findNum($str='') {
        $str = trim($str);
        if (empty($str)) return '';
        $result = '';
        for($i = 0; $i < strlen($str); $i++) {
            if (is_numeric($str[$i])) $result.=$str[$i];
        }
        return $result;
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
     * @param Bool errdie 如果出错则完全中断执行，返回错误信息JSON
     * @param String totpsecret 加密传输密钥（可选,留空不加密）
     * @return Array<Bool,String,String> [是否包含违禁词,河蟹后的字符串,触发的违禁词] ，如果不包含违禁词，返回 [false,原字符串]
     * 违禁词列表为 JSON 一维数组，每个字符串中可以加 $wordfilterpcfg["wildcardchar"] 分隔以同时满足多个条件词。
     */
    function wordfilter($str,$errdie=true,$totpsecret=null) {
        global $nlcore;
        $wordfilterpcfg = $nlcore->cfg->app->wordfilter;
        $wordjson = ""; //词库
        if ($wordfilterpcfg["enable"] == 1) { //从 Redis 读入
            if (!$nlcore->db->initRedis()) {
                // 没有启用 Redis ，跳过敏感词检查
                // $nlcore->msg->stopmsg(2020301);
                return false;
            }
            $wordjson = $nlcore->db->redis->get($wordfilterpcfg["rediskey"]);
        } else if ($wordfilterpcfg["enable"] == 2) { //从 file 读入
            $jfile = fopen($wordfilterpcfg["jsonfile"], "r") or $nlcore->msg->stopmsg(2020302,$totpsecret);
            $wordjson = fread($jfile,filesize($wordfilterpcfg["jsonfile"]));
            fclose($jfile);
        } else {
            return [false,$str];
        }
        if (!$wordjson || $wordjson == [] || $wordjson == "") $nlcore->msg->stopmsg(2020303,$totpsecret);
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
            if (strcmp($str,$nstr) != 0) {
                if ($errdie) $nlcore->msg->stopmsg(2020300,$totpsecret);
                return [true,$nstr,$keyword];
            }
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
        $datadic = ["ip" => $_SERVER['REMOTE_ADDR']];
        if ($proxyaddr != "") $datadic["proxy"] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $table = $nlcore->cfg->db->tables["ip"];
        $result = $nlcore->db->select(["id","enabletime"],$table,$datadic,"","OR");
        $ipid = null;
        if ($result[0] == 1010000) {
            //如果有数据则比对时间,取ID
            $onedata = $result[2][0];
            $datatime = strtotime($onedata["enabletime"]);
            if ($time < $datatime) return [2020403];
            $ipid = $onedata["id"];
        } else if ($result[0] == 1010001) {
            //如果没有则写入IP记录,取ID
            //区分IP类型
            $iptype = $this->iptype($ipaddr);
            $datadic = array(
                "type" => $iptype,
                "ip" => $ipaddr,
                "proxy" => $proxyaddr
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
        $table = $nlcore->cfg->db->tables["app"];
        $whereDic = array(
            "name" => $appname,
            "secret" => $appsecret
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
        if (!isset($_SERVER['REQUEST_METHOD'])) die(header('HTTP/1.1 405 Method Not Allowed'));
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == "POST" && in_array("POST",$allowmethod)) {
            $argv = $_POST;
        } else if ($method == "GET" && in_array("GET",$allowmethod)) {
            $argv = $_GET;
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
     * @description: [数据发送]从数组创建JSON、加密、base64编码、变体
     * @param String dataarray 要返回到客户端的内容字典
     * @param String secret totp加密码（可选，不加不进行加密）
     * @return Array<String> [解析后的JSON内容数组,TOTP的secret]
     */
    function encryptargv($dataarray,$secret=null) {
        global $nlcore;
        //转换为json
        $json = json_encode($dataarray);
        if ($secret) {
            //使用secret生成totp数字
            $ga = new PHPGangsta_GoogleAuthenticator();
            $numcode = $ga->getCode($secret)+$nlcore->cfg->app->totpcompensate;
            //MD5
            $numcode = md5($secret.$numcode);
            //使用totp数字加密
            $json = xxtea_encrypt($json, $numcode);
            $returndata = $this->urlb64encode($json);
            header('Content-Type:text/plain;charset=utf-8');
            return $returndata;
        }
        header('Content-Type:application/json;charset=utf-8');
        return $json;
    }
    /**
     * @description: [数据接收]解析变体、base64解码、解密、解析JSON到数组
     * GET/POST参数：t=apptoken，j=JSON内容
     * @param String module 功能名称（$conf->limittime）
     * @return Array<String> [解析后的JSON内容数组,TOTP的secret,TOTP的token,IP地址ID,APPID]
     */
    function decryptargv($module=null) {
        global $nlcore;
        //检查IP访问频率
        if ($module) {
            $result = $this->frequencylimitation($module);
            if ($result[0] >= 2000000) $nlcore->msg->stopmsg($result[0]);
        }
        //获取参数，验证格式（t=哈希、j=变形base64）
        $argv = $this->getarg();
        //被要求强制进行 TOTP/XXTEA 加密
        if (!isset($argv["j"]) && $nlcore->cfg->app->alwayencrypt) {
            $nlcore->msg->stopmsg(2020415);
        }
        if (!isset($argv["t"])) { //检查应用令牌
            $nlcore->msg->stopmsg(2020408);
        }
        if (!$this->is_md5($argv["t"])) { //检查应用令牌格式
            $nlcore->msg->stopmsg(2020409);
        }
        //检查数据超长
        $jsonlen = ($_SERVER['REQUEST_METHOD'] == "GET") ? $nlcore->cfg->app->maxlen_get : $nlcore->cfg->app->maxlen_post;
        $arglen = strlen(implode("", $argv));
        if ($arglen > $jsonlen) $nlcore->msg->stopmsg(2020414,null,$arglen);
        //检查 IP 是否被封禁
        $stime = $this->getdatetime();
        $result = $this->chkip($stime[0]);
        $stime = $stime[1];
        if ($result[0] != 0) $nlcore->msg->stopmsg($result[0]);
        $ipid = $result[1];
        $jsonarr = null;
        $secret = null;
        if (isset($argv["j"])) { //已加密，需要解密
            //检查加密字串是否有非法字符
            if (!$this->isbase64($argv["j"],true)) {
                $nlcore->msg->stopmsg(2020410);
            }
            //查询apptoken对应的secret
            $datadic = [
                "apptoken" => $argv["t"]
            ];
            $result = $nlcore->db->select(["secret"],$nlcore->cfg->db->tables["totp"],$datadic);
            //空或查询失败都视为不正确
            if (!$result || $result[0] != 1010000 || !isset($result[2][0][0])) $nlcore->msg->stopmsg(2020409);
            $secret = $result[2][0][0];
            //使用secret生成totp数字
            $ga = new PHPGangsta_GoogleAuthenticator();
            $gaisok = false;
            $timestamp = time();
            for ($oldcode=0; $oldcode < $nlcore->cfg->app->totptimeslice; $oldcode++) {
                $exsecond = time() - ($oldcode * 30);
                $timeSlice = floor($exsecond / 30);
                $numcode = $ga->getCode($secret,$timeSlice)+$nlcore->cfg->app->totpcompensate;
                //MD5
                $numcode = md5($secret.$numcode);
                //解密base64
                $xxteadata = $this->urlb64decode($argv["j"]);
                //使用totp数字解密
                $decrypt_data = xxtea_decrypt($xxteadata, $numcode);
                if (strlen($decrypt_data) > 0) {
                    $gaisok = true;
                    break;
                }
            }
            if (!$gaisok) $nlcore->msg->stopmsg(2020411,null,$timestamp);
            $jsonarr = json_decode($decrypt_data,true);
        } else { //未加密
            $jsonarr = $argv;
            unset($jsonarr["t"]);
        }
        //解析json
        if (!$jsonarr || count($jsonarr) == 0) $nlcore->msg->stopmsg(2020400);
        //检查API版本是否一致
        if (!isset($jsonarr["apiver"]) || intval($jsonarr["apiver"]) != 1) $nlcore->msg->stopmsg(2020412);
        //检查APP是否有效
        $appid = $this->chkappsecret($jsonarr["appid"],$jsonarr["appsecret"]);
        if (!isset($jsonarr["appid"]) || !isset($jsonarr["appsecret"]) || !$this->isNumberOrEnglishChar($jsonarr["appid"],1,64) || !$this->isNumberOrEnglishChar($jsonarr["appsecret"],32,32) || $appid == null) $nlcore->msg->stopmsg(2020401);
        return [$jsonarr,$secret,$argv["t"],$ipid,$appid];
    }
    /**
     * @description: 批量替换指定字符
     * @param String string 规定被搜索的字符串
     * @param Array<String:String> findreplace 要替换的内容字典
     * 例如：Array("find" => "replace")
     * @param Bool geti 是否返回替换的数量，设置为 true 会返回数组
     * @return String 经过替换后的字符串
     * @return String,Int 经过替换后的字符串和替换的数量
     */
    function replacestr($string,$findreplace,$geti=false) {
        $find = array_keys($findreplace);
        $replace = array_values($findreplace);
        $newstring = str_replace($find,$replace,$string,$replacei);
        if ($geti) return [$newstring,$replacei];
        return $newstring;
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
    /**
     * @description: 密码健壮性检查（长度、特定字符长度、字符合法性）
     * @param String 要检查的密码
     * @return Null 有错误直接返回客户端
     */
    function strongpassword($password) {
        global $nlcore;
        $passwordchar = $this->mbStrSplit($password);
        $passwordcount = count($passwordchar);
        $passwordlengthcfg = $nlcore->cfg->verify->passwordlength;
        if ($passwordcount < $passwordlengthcfg[0] || $passwordcount > $passwordlengthcfg[1]) $nlcore->msg->stopmsg(2030202);
        $strongpassword = $nlcore->cfg->verify->strongpassword;
        $passwordsymbol = $nlcore->cfg->verify->passwordsymbol;
        $passwordsymbol = $this->mbStrSplit($passwordsymbol);
        $typei = [0,0,0,0]; //0:其他字符 1:数字 2:小写字母 3:大写字母
        foreach ($passwordchar as $char) {
            $type = $this->chartype($char);
            $typei[$type] += 1;
            if ($type == 0) {
                $symbolok = false;
                foreach ($passwordsymbol as $symbol) {
                    if ($char == $symbol) {
                        $symbolok = true;
                        break;
                    }
                }
                if (!$symbolok) $nlcore->msg->stopmsg(2030200); //发现不允许的符号
            }
        }
        if ($typei[0] < $strongpassword["symbol"] || $typei[1] < $strongpassword["num"] || $typei[2] < $strongpassword["lower"] || $typei[3] < $strongpassword["upper"]) {
            $nlcore->msg->stopmsg(2030101);
        }
    }
    /**
     * @description: 检查数组中是否包括指定的一组 key
     * @param Array nowarray 需要被测试的数组
     * @param Array<String> keys 需要检查是否有哪些 key 的数组
     * @param Bool getcount 是否返回不匹配的数量，否则返回具体不匹配的 key 数组
     * @return Array<String> 不匹配的 key 数组
     * @return Int 不匹配的数量
     */
    function keyinarray($nowarray,$keys,$getcount=true) {
        $novalkey = array();
        foreach($keys as $nowkey){
            if (!isset($nowarray[$nowkey])) {
                array_push($novalkey,$nowkey);
            }
        }
        if ($getcount) return count($novalkey);
        return $novalkey;
    }
    /**
     * @description: 获得需要使用的语言
     * 根据配置中设定的语言支持情况和当前浏览器语言，决定当前要使用的语言代码。
     * 如果找不到所需语言，则采用默认语言。
     * @param String language 指定一个语言
     * @return String i18n 语言代码
     */
    function getlanguage($language=null) {
        global $nlcore;
        $applanguages = $nlcore->cfg->app->language;
        if ($language) {
            foreach($applanguages as $nowapplang){
                if ($language == $nowapplang) {
                    return $language;
                }
            }
        } else {
            $browserlanguages = explode(",", strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
            foreach($browserlanguages as $nowbwrlang) {
                foreach($applanguages as $nowapplang) {
                    if ($nowbwrlang == $nowapplang) {
                        return $nowbwrlang;
                    }
                }
            }
        }
        return $applanguages[0];
    }

}
?>
