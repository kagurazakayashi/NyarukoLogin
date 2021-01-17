<?php
class nyasafe {
    private $logfile = null; // 記錄詳細除錯資訊到檔案
    const PKBE_PRIE_B = "-----BEGIN ENCRYPTED PRIVATE KEY-----\n";
    const PKBE_PRIE_E = "\n-----END ENCRYPTED PRIVATE KEY-----";
    const PKBE_PRI_B = "-----BEGIN PRIVATE KEY-----\n";
    const PKBE_PRI_E = "\n-----END PRIVATE KEY-----";
    const PKBE_PUB_B = "-----BEGIN PUBLIC KEY-----\n";
    const PKBE_PUB_E = "\n-----END PUBLIC KEY-----";
    const PKBE_PUBR_B = "-----BEGIN RSA PUBLIC KEY-----";
    const PKBE_PUBR_E = "-----END RSA PUBLIC KEY-----";
    const PKB_PRIB_B = "MIIFDjBABgkqhkiG9w0BBQ0wMzAbBgkq";
    const PKB_PUBB_B = "MIICIjANBgkqhkiG9w0BAQEFAAOCAg8A";
    const HEADER_405 = "HTTP/1.1 405 Method Not Allowed";
    /**
     * @description: 建構函式
     */
    function __construct() {
    }
    /**
     * @description: RSA 建立私鑰和公鑰
     * @param String privateKeyPassword 私钥密码
     */
    function rsaCreateKey(string $privateKeyPassword = ""): void {
        global $nlcore;
        if (strlen($privateKeyPassword) == 0) $privateKeyPassword = null;
        try {
            // 建立公鑰和私鑰
            $rsaRes = openssl_pkey_new($nlcore->cfg->enc->pkeyConfig);
            // 獲取私鑰給 privateKey
            openssl_pkey_export($rsaRes, $nlcore->sess->privateKey, $privateKeyPassword, $nlcore->cfg->enc->pkeyConfig);
            // 獲取公鑰給 publicKey
            $nlcore->sess->publicKey = openssl_pkey_get_details($rsaRes)["key"];
            // 釋放私鑰
            openssl_pkey_free($rsaRes);
        } catch (Exception $e) {
            $nlcore->msg->stopmsg(2020419, "", $e->getMessage());
        }
    }
    /**
     * @description: 轉換公鑰格式到相容
     * @param String data  PKCS#8 公鑰(任一位數) 或 PKCS#1 公鑰(4096位)
     * @return String PKIX (X.509) 公鑰
     */
    function convertRsaHeaderInformation(string $key): string {
        if (strpos($key, self::PKB_PUBB_B) !== false) return $key;
        $key = $this->rsaRmTag($key);
        $key = self::PKB_PUBB_B . str_replace("\n", '', $key);
        $key = self::PKBE_PUB_B . wordwrap($key, 64, "\n", true) . self::PKBE_PUB_E;
        return $key;
    }
    /**
     * @description: RSA 加密
     * @param String str 明文字串
     * @param Bool isPrivateKey 是否使用私鑰加密
     * @return String 加密資料
     */
    function rsaEncrypt(string $str, bool $isPrivateKey = false): string {
        global $nlcore;
        $encrypted = null;
        try {
            if ($isPrivateKey) {
                if ($nlcore->cfg->enc->privateKeyPassword) {
                    $dkey = openssl_pkey_get_private($nlcore->sess->privateKey, $nlcore->cfg->enc->privateKeyPassword);
                    openssl_private_encrypt($str, $encrypted, $dkey, OPENSSL_PKCS1_PADDING);
                } else {
                    openssl_private_encrypt($str, $encrypted, $nlcore->sess->privateKey, OPENSSL_PKCS1_PADDING);
                }
            } else {
                openssl_public_encrypt($str, $encrypted, $nlcore->sess->publicKey, OPENSSL_PKCS1_PADDING);
            }
        } catch (Exception $e) {
            $nlcore->msg->stopmsg(2020406, "", $e->getMessage());
        }
        return $encrypted;
    }
    /**
     * @description: RSA 分段加密
     * @param String str 明文字串
     * @param Bool isPrivateKey 是否使用私鑰加密
     * @param Int chunkLen 分段位數，0為不分段  chunkLen/16-11
     * @return String 加密資料
     */
    function rsaEncryptChunk(string $str, bool $isPrivateKey = false, int $chunkLen = 245): string {
        if ($chunkLen == 0) {
            return $this->rsaEncrypt($str, $isPrivateKey);
        } else {
            $fulldata = "";
            foreach (str_split($str, $chunkLen) as $chunk) {
                $fulldata .= $this->rsaEncrypt($chunk, $isPrivateKey);
            }
            return $fulldata;
        }
    }
    /**
     * @description: RSA 解密
     * @param String data 加密資料
     * @param Bool isPrivateKey 是否使用私鑰解密
     * @return String 明文字串
     */
    function rsaDecrypt(string $data, bool $isPrivateKey = true): string {
        global $nlcore;
        // die(json_encode(["publicKey"=>$nlcore->sess->publicKey,"privateKey"=>$nlcore->sess->privateKey,"privateKeyPassword"=>$nlcore->cfg->enc->privateKeyPassword]));
        $decrypted = null;
        try {
            if ($isPrivateKey) {
                if ($nlcore->cfg->enc->privateKeyPassword) {
                    $dkey = openssl_pkey_get_private($nlcore->sess->privateKey, $nlcore->cfg->enc->privateKeyPassword);
                    // OPENSSL_PKCS1_PADDING, OPENSSL_SSLV23_PADDING, OPENSSL_PKCS1_OAEP_PADDING, OPENSSL_NO_PADDING.
                    if (!openssl_private_decrypt($data, $decrypted, $dkey)) {
                        $nlcore->msg->stopmsg(2020411, "D_PWPRI");
                    }
                } else {
                    if (!openssl_private_decrypt($data, $decrypted, $nlcore->sess->privateKey)) {
                        $nlcore->msg->stopmsg(2020411, "D_PRI");
                    }
                }
            } else {
                if (!openssl_public_decrypt($data, $decrypted, $nlcore->sess->publicKey)) {
                    $nlcore->msg->stopmsg(2020411, "D_PUB");
                }
            }
        } catch (Exception $e) {
            $nlcore->msg->stopmsg(2020411, $e->getMessage());
        }
        if (!$decrypted) $nlcore->msg->stopmsg(2020411, "D_NIL");
        return $decrypted;
    }
    /**
     * @description: RSA 分段解密
     * @param String data 加密資料
     * @param Bool isPrivateKey 是否使用私鑰解密
     * @param Int chunkLen 分段位數，0為不分段  chunkLen/8
     * @return String 明文字串
     */
    function rsaDecryptChunk(string $data, bool $isPrivateKey = true, int $chunkLen = 512): string {
        if ($chunkLen == 0) {
            return $this->rsaDecrypt($data, $isPrivateKey);
        } else {
            $fullData = "";
            foreach (str_split($data, $chunkLen) as $chunk) {
                $chunkData = $this->rsaDecrypt($chunk, $isPrivateKey);
                $fullData .= $chunkData;
            }
            return $fullData;
        }
    }
    /**
     * @description: 檢查快取的私鑰和公鑰是否正確
     * @return Bool 是否正確
     */
    function autoCheck(): bool {
        global $nlcore;
        $isRsaKey = $this->isRsaKey($nlcore->sess->publicKey);
        if ($isRsaKey != 1) {
            return false;
        }
        $privateKeyPassword = $nlcore->cfg->enc->privateKeyPassword;
        $isRsaKey = $this->isRsaKey($nlcore->sess->privateKey, false, $privateKeyPassword);
        if ($isRsaKey != 2 && $isRsaKey != 3) {
            return false;
        }
        return true;
    }
    /**
     * @description: 檢查當前字串是否為金鑰，並獲取其型別，並驗證是否正確
     * @param String key 金鑰字串
     * @param Bool check 透過模擬進行加密解密來檢查是否有效 (預設禁用)
     * @param String privateKeyPassword 如果預期是私鑰，請提供私鑰密碼 (限啟用check)
     * @return String 型別 0.未知
     *    1.是公鑰　　  2.是私鑰　　  3.是加密私鑰
     *   -1.是無效公鑰 -2.是無效私鑰 -3.是無效加密私鑰 (本行限啟用check)
     *   -4.長度不正確 -5.標識錯誤　 -6.字元不匹配
     */
    function isRsaKey(string $key, bool $check = false, string $privateKeyPassword = ""): int {
        if (strlen($key) < 54) return -4;
        $noTag = $this->rsaRmTag($key, "");
        if (strcmp(base64_encode(base64_decode($noTag, true)), $noTag) != 0) {
            return -6;
        }
        $iskey = 0;
        $keyarr =  explode("\n", $key);
        $keyarrIndex = count($keyarr) - 2;
        if (strlen($keyarr[count($keyarr) - 1]) > 0) {
            $keyarrIndex += 1;
        }
        if (strlen($keyarr[$keyarrIndex - 1]) % 4 == 0) {
            if (count(explode("PUBLIC", $keyarr[0])) > 1 && count(explode("PUBLIC", $keyarr[$keyarrIndex])) > 1) {
                $iskey = 1;
                if ($check) {
                    try {
                        if (!openssl_public_encrypt("t", $encrypted, $key)) $iskey *= -1;
                    } catch (Exception $e) {
                        $iskey *= -1;
                    }
                }
            } elseif (count(explode("PRIVATE", $keyarr[0])) > 1 && count(explode("PRIVATE", $keyarr[$keyarrIndex])) > 1) {
                if (count(explode("ENCRYPTED", $keyarr[0])) > 1 && count(explode("ENCRYPTED", $keyarr[$keyarrIndex])) > 1) {
                    $iskey = 3;
                    if ($check) {
                        try {
                            $dkey = openssl_pkey_get_private($key, $privateKeyPassword);
                            openssl_private_encrypt("t", $encrypted, $dkey);
                        } catch (Exception $e) {
                            $iskey *= -1;
                        }
                    }
                } else {
                    $iskey = 2;
                    if ($check) {
                        try {
                            openssl_private_encrypt("t", $encrypted, $key);
                        } catch (Exception $e) {
                            $iskey *= -1;
                        }
                    }
                }
            } else {
                $iskey = -5;
            }
        } else {
            $iskey = -4;
        }
        // if ($iskey < 0) die("RsaKeyErr=".strval($iskey));
        return $iskey;
    }
    /**
     * @description: 移除金鑰對首尾標記
     * @param String rsaStr 金鑰對
     * @param String implodeChar 再次合并后的字符串行间隔字符
     * @param String explodeChar 使用此字符分隔行
     * @return String 移除首尾標記的金鑰對
     */
    function rsaRmTag(string $rsaStr, string $implodeChar = "\n", string $explodeChar = "\n"): string {
        $newRsaArr = [];
        $lines = explode($explodeChar, $rsaStr);
        for ($i = 0; $i < count($lines); $i++) {
            $nowLine = $lines[$i];
            if (strlen($nowLine) == 0 || strcmp(substr($nowLine, 0, 5), "-----") == 0) continue;
            array_push($newRsaArr, $nowLine);
        }
        return implode($implodeChar, $newRsaArr);
    }
    /**
     * @description: 補充金鑰對首尾標記
     * @param String str 移除首尾標記的金鑰對
     * @param Bool isPrivateKey 是否為私鑰
     * @return String 金鑰對
     */
    function rsaAddTag(string $str, bool $isPrivateKey = false): string {
        if (strlen($str) < 4) return "";
        if ($isPrivateKey) {
            return self::PKBE_PRIE_B . $str . self::PKBE_PRIE_E;
        } else {
            return self::PKBE_PUB_B . $str . self::PKBE_PUB_E;
        }
    }
    /**
     * @description: 快捷補充金鑰對首尾標記，從快取區載入和儲存
     * @return String 金鑰對
     */
    function autoRsaAddTag() {
        global $nlcore;
        $pubKey = $nlcore->sess->publicKey;
        if (strcmp(substr($pubKey, 0, 5), "-----") != 0) {
            $nlcore->sess->publicKey = $this->rsaAddTag($pubKey, false);
        }
        $priKey = $nlcore->sess->privateKey;
        if (strcmp(substr($priKey, 0, 5), "-----") != 0) {
            $nlcore->sess->privateKey = $this->rsaAddTag($priKey, true);
        }
    }
    /**
     * @description: 移除前部分資料
     * @param String str 在 rsaRmTag 之後的資料
     * @return String 削減後的金鑰
     */
    function rsaRmBCode(string $str) {
        return substr($str, 32);
    }
    /**
     * @description: 補充前部分資料
     * @param String str 在 rsaRmBCode 之後的資料
     * @return String 用於 rsaAddTag 的全內容金鑰
     */
    function rsaAddBCode(string $str, bool $isPrivateKey = false) {
        if ($isPrivateKey) {
            return self::PKB_PRIB_B . $str;
        } else {
            return self::PKB_PUBB_B . $str;
        }
    }
    /**
     * @description: 進行Base64編碼，並取代一些符號
     * “+”改成“-”, “/”改成“_”, “=”刪除
     * @param Data fdata 需要使用Base64編碼的資料
     * @return Array 加密後的字串
     */
    function urlb64encode($fdata) {
        $data = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($fdata));
        return $data;
    }
    /**
     * @description: 撤回取代的一些符號，並解析Base64編碼
     * 改回，將“=”添加回來
     * @param String fstring Base64編碼後的字串
     * @return String 解析後的字串
     */
    function urlb64decode(string $fstring): string {
        $data = str_replace(['-', '_'], ['+', '/'], $fstring);
        $mod4 = strlen($data) % 4;
        if ($mod4) $data .= substr('====', $mod4);
        $data = base64_decode($data);
        return ($data === FALSE) ? "" : $data;
    }
    /**
     * @description: gzflate 壓縮字串到字串
     * @param String str 明文
     * @return String 加密後資料的 base64
     */
    function gzdeflateString(string $str): string {
        $compressed = gzdeflate($str, 9);
        $compressedStr = base64_encode($compressed);
        return $compressedStr;
    }
    /**
     * @description: gzflate 解壓字串到字串
     * @param String compressedStr 加密後資料的 base64
     * @return String 明文
     */
    function gzinflateString(string $compressedStr): string {
        $compressed = base64_decode($compressedStr);
        $uncompressed = gzinflate($compressed);
        return $uncompressed;
    }
    /**
     * @description: 是否為MD5
     * @param String str 需要判斷的字串
     * @return Int 是否匹配： > 0 或 != false
     */
    function is_md5(string $str) {
        return preg_match("/^[a-z0-9]{32}$/", $str);
    }
    /**
     * @description: 是否為MD6
     * @param String str 需要判斷的字串
     * @param String length 預期的字串長度
     * @return Int 是否匹配： > 0 或 != false
     */
    function is_md6(string $str, int $length = 64) {
        return preg_match("/^[a-z0-9]{" . $length . "}$/", $str);
    }
    /**
     * @description: 取MD6雜湊值
     * @param String data 要進行雜湊的字串
     * @param Int size 長度
     * @param String key 金鑰
     * @param Int levels 級別
     * @return String MD6雜湊值
     */
    function md6(string $data, int $size = 256, string $key = '', int $levels = 64): string {
        $md6 = new md6hash();
        if (strlen($data) == 0) return '';
        return $md6->hex($data, $size, $key, $levels);
    }
    /**
     * @description: 隨機將字串中的每個字轉換為大寫或小寫
     * @param String str 要進行隨機大小寫轉換的字串
     * @return String 轉換後的字串
     */
    function randstrto(string $str): string {
        $strarr = str_split($str);
        for ($i = 0; $i < count($strarr); $i++) {
            if (rand(0, 1) == 1) {
                $strarr[$i] = strtoupper($strarr[$i]);
            } else {
                $strarr[$i] = strtolower($strarr[$i]);
            }
        }
        return implode("", $strarr);
    }
    /**
     * @description: 進行MD6後進行隨機大小寫轉換
     * @param String data 要進行雜湊的字串
     * @param Int size 長度
     * @param String key 金鑰
     * @param Int levels 級別
     * @return String 隨機大小寫MD6雜湊值
     */
    function rhash64(string $data, int $size = 256, string $key = '', int $levels = 64): string {
        return $this->randstrto($this->md6($data, $size, $key, $levels));
    }
    /**
     * @description: 驗證是否為包含大小寫的MD6變體字串
     * @param String str MD6變體字串
     * @param String length 預期的字串長度
     * @return Int 是否匹配： > 0 或 != false
     */
    function is_rhash64(string $str, int $length = 64) {
        return preg_match("/^[A-Za-z0-9]{" . $length . "}$/", $str);
    }
    /**
     * @description: 進行MD5後進行隨機大小寫轉換
     * @param String str 明文
     * @return String 轉換後的字串
     */
    function rhash32(string $str): string {
        return $this->randstrto(md5($str));
    }
    /**
     * @description: 驗證是否為包含大小寫的MD5變體字串
     * @param String str MD5變體字串
     * @return Int 是否匹配 > 0 || != false
     */
    function is_rhash32(string $str) {
        return preg_match("/^[A-Za-z0-9]{32}$/", $str);
    }
    /**
     * @description: 生成一段随机文本
     * @param Int len 生成长度
     * @param String chars 从此字符串中抽取字符
     * @return String 新生成的随机文本
     */
    function randstr($len = 64, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
        mt_srand($this->seed());
        $password = "";
        while (strlen($password) < $len) $password .= substr($chars, (mt_rand() % strlen($chars)), 1);
        return $password;
    }
    /**
     * @description: 生成随机哈希值
     * @param String salt 盐（可选）
     * @param Boolean md6 使用 MD6 生成随机哈希值（64位），否则用 MD5 生成随机哈希值（32位）
     * @param Boolean randstrto 将哈希结果随机大小写
     * @return String 生成的字符串
     */
    function randhash($salt = "", $md6 = true, $randstrto = true) {
        $data = (float)microtime() . $salt . $this->randstr(16);
        $data = $md6 ? $this->md6($data) : md5($data);
        return $randstrto ? $this->randstrto($data) : $data;
    }
    /**
     * @description: 生成随机数发生器种子
     * @param String salt 盐（可选）
     * @return String 种子
     */
    function seed($salt = "") {
        $newsalt = (float)microtime() * 1000000 * getmypid();
        return $newsalt . $salt;
    }
    /**
     * @description: 获得当前时间
     * @param String timezone PHP时区代码（可选，默认为配置文件中配置的时区）
     * @param Int settime 自定义时间戳（秒）
     * @return Array [Datetime,String] 返回时间日期对象和时间日期字符串
     */
    function getdatetime($timezone = null, $settime = null) {
        if ($timezone) {
            $timezone_out = date_default_timezone_get();
            date_default_timezone_set($timezone);
        }
        $timestamp = $settime ? $settime : time();
        $timestr = date('Y-m-d H:i:s', $timestamp);
        if ($timezone) date_default_timezone_set($timezone_out);
        return [$timestamp, $timestr];
    }
    /**
     * @description: 獲得當前時間（可直接用於SQL語句）
     * @param Int settime 自定義時間戳（秒）
     * @return String 時間日期字串
     */
    function getnowtimestr(int $settime = -1): string {
        $timestamp = ($settime > 0) ? $settime : time();
        return date('Y-m-d H:i:s', $timestamp);
    }
    /**
     * @description: 获得毫秒级时间戳
     */
    function millisecondtimestamp() {
        $time = explode(" ", strval(microtime()));
        $time = $time[1] . (intval($time[0]) * 1000);
        $time2 = explode(".", $time);
        $time = $time2[0];
        return $time;
    }
    /**
     * @description: 检查时间戳差异是否大于配置文件中的值
     * 差异太大直接返回错误信息到客户端
     * @param String timestamp1 秒级时间戳1
     * @param String timestamp2 秒级时间戳2
     * @return Void 成功不返回，失败直接返回错误代码给客户端
     */
    function timestampdiff($timestamp1, $timestamp2) {
        global $nlcore;
        $timestampabs = abs($timestamp1 - $timestamp2);
        if ($timestampabs > $nlcore->cfg->app->timestamplimit) {
            $nlcore->msg->stopmsg(2020413, null, $timestamp1 . "-" . $timestamp2);
        }
    }
    /**
     * @description: 將多個字串變數轉換為整數並增加數值
     * @param Int add 要進行加法的數字
     * @param String *numString... 要進行轉換並運算的多個字串指標
     */
    function intStringAdd(int $add = 1, string &...$numString): void {
        foreach ($numString as &$nowVal) {
            $nowVal = intval($nowVal) + $add;
        }
    }
    /**
     * @description: 將多個整數轉換為字串並確保最小值
     * @param Int min 最小值
     * @param Int *nums... 要進行轉換並運算的多個整數指標
     */
    function stringGreaterThanNum(int $min = 0, int &...$nums): void {
        $minstr = strval($min);
        foreach ($nums as &$num) {
            $num = $num > $min ? strval($num) : $minstr;
        }
    }
    /**
     * @description: 從字典中抽出指定的 Key ，並生成一個新的字典。
     * @param Array add 原始字典陣列
     * @param String keys... 要取出的所有 Key
     * @return Array 新建立的字典
     */
    function dicExtract(array $dicArr, string ...$keys) {
        $newDic = [];
        foreach ($keys as $key) {
            if (isset($dicArr[$key])) {
                $newDic[$key] = $dicArr[$key];
            }
        }
        return $newDic;
    }
    /**
     * @description: 替换字符串中某个字符的多个连续字符，转换成一个
     * 例如： "///" -> "/"
     * @param String str 原字符串
     * @param String chr 要合并的字符
     * @return String 替换后的字符
     */
    function mergerepeatchar($str, $chr) {
        $chararr = str_split($str);
        $newchararr = [];
        $oldchar = "";
        foreach ($chararr as $nowchar) {
            if (!($nowchar == $oldchar && $chr == $nowchar)) {
                array_push($newchararr, $nowchar);
                $oldchar = $nowchar;
            }
        }
        return implode('', $newchararr);
    }
    /**
     * @description: 转换为系统路径
     * 将路径字符 '/' 和 '\' 统一转换为当前系统使用的路径字符
     * @param String path 路径字符串
     * @return String 转换后的路径字符串
     */
    function dirsep($path) {
        $newpath = str_replace("\\", DIRECTORY_SEPARATOR, $path);
        $newpath = str_replace("/", DIRECTORY_SEPARATOR, $newpath);
        return $this->mergerepeatchar($newpath, DIRECTORY_SEPARATOR);
    }
    /**
     * @description: 转换为网址路径
     * 将路径字符 '\' 转换为 URL 用的 '/'
     * @param String path 路径字符串
     * @return String 转换后的路径字符串
     */
    function urlsep($path) {
        $newpath = str_replace("\\", "/", $path);
        return $this->mergerepeatchar($newpath, "/");
    }
    /**
     * @description: 自动清除路径中的文件夹字符(../)，可以在路径的任何位置。
     * @param String path 路径字符串
     * @param Int level 如果提供此数值，会改为手动向上父级多少级
     * @return String 转换后的路径
     */
    function parentfolder($path, $level = -1) {
        $newpath = $this->dirsep($path);
        $endchar = substr($newpath, -1);
        if ($endchar != DIRECTORY_SEPARATOR) $endchar = "";
        $startchar = substr($newpath, 0, 1);
        if ($startchar != DIRECTORY_SEPARATOR) $startchar = "";
        $newpath = substr($newpath, strlen($startchar), strlen($newpath) - 1 - strlen($endchar));
        $patharr = explode(DIRECTORY_SEPARATOR, $newpath);
        $newpatharr = [];
        if ($level == -1) {
            foreach ($patharr as $dir) {
                if ($dir == "..") {
                    array_pop($newpatharr);
                } else {
                    array_push($newpatharr, $dir);
                }
            }
        } else {
            $newpatharr = $patharr;
            for ($i = 0; $i < $level; $i++) {
                array_pop($newpatharr);
            }
        }
        if (count($newpatharr) == 0) return DIRECTORY_SEPARATOR;
        $newpath = implode(DIRECTORY_SEPARATOR, $newpatharr);
        return $startchar . $newpath . $endchar;
    }
    /**
     * @description: 检查一维数组中是否都为某个值
     * @param Object search 对象
     * @param Array array 要检查的数组
     * @param Bool type 使用全等进行判断
     * @return Bool 是否都为某个值
     */
    function allinarray($search, $array, $type = true) {
        foreach ($array as $value) {
            if (($type && $value !== $search) || (!$type && $value != $search)) return false;
        }
        return true;
    }
    /**
     * @description: 将父文件夹字符(../)移除，并返回有多少层
     * 例如： "/server/path/../../" -> [2,"/server/path/"]
     * @param String path 路径字符串
     * @return Array<Int,String> [层数,转换后的路径]
     */
    function parentfolderlevel($path) {
        $pdirstr = ".." . DIRECTORY_SEPARATOR;
        $newpath = str_replace($pdirstr, "", $path, $ri);
        return [$ri, $newpath];
    }
    /**
     * @description: 过滤字符串中的非法字符
     * @param String str 源字符串
     * @param Bool errdie 如果出错则完全中断执行，返回错误信息JSON
     * @param Bool dhtml 是否将HTML代码也视为非法字符
     * @return String 经过过滤的字符
     */
    function safestr($str, $errdie = true, $dhtml = false) {
        global $nlcore;
        $ovalue = $str;
        $str = stripslashes($str);
        if ($str != $ovalue && $errdie == true) {
            $nlcore->msg->stopmsg(2020101);
        }
        if ($dhtml) {
            $str = htmlspecialchars($str);
            if ($str != $ovalue && $errdie == true) {
                $nlcore->msg->stopmsg(2020103);
            }
        }
        return $str;
    }
    /**
     * @description: 只保留字符串中所有的非字母和数字
     * @param String str 源字符串
     * @return String 经过过滤的字符
     */
    function retainletternumber($str) {
        return preg_replace('/[\W]/', '', $str);
    }
    /**
     * @description: 只保留字符串中所有的数字
     * @param String str 源字符串
     * @return String 经过过滤的字符
     */
    function retainnumber($str) {
        return preg_replace('/[^\d]/', '', $str);
    }
    /**
     * @description: 检查是否为电子邮件地址
     * @param String str 源字符串
     * @return Bool 是否正确
     */
    function isEmail($str) {
        if (strlen($str) > 64) return false;
        $checkmail = "/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/";
        if (isset($str) && $str != "") {
            if (preg_match($checkmail, $str)) {
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
        $iptype = "other";
        if ($this->isIPv4($ip)) $iptype = "ipv4";
        else if ($this->isIPv6($ip)) $iptype = "ipv6";
        if ($this->isPubilcIP($ip)) $iptype = $iptype . "_local";
        return $iptype;
    }
    /**
     * @description: 检查是否为整数数字字符串
     * @param String str 源字符串
     * @return Bool 是否正确
     */
    function isInt($str) {
        $v = is_numeric($str) ? true : false; //判断是否为数字或数字字符串
        if ($v) {
            if (strpos($str, ".")) {
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
        if (preg_match("/^\d*$/", $str)) {
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
    function isNumberOrEnglishChar($str, $minlen = -1, $maxlen = -1) {
        if (preg_match("/^[A-Za-z0-9]+$/i", $str) == 0) return false;
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
        $checktel = "/^1[345789]\d{9}$/";
        if (isset($str) && $str != "") {
            if (preg_match($checktel, $str)) {
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
        if (!preg_match("/^[\+a-z\d\-( )]*$/i", $telstr)) {
            return ["", ""];
        }
        if (substr($telstr, 0, 1) == '+' || substr($telstr, 0, 2) == '00') {
            $telarr = explode(" ", $telstr);
            $area = array_shift($telarr);
            $tel = implode("", $telarr);
        } else {
            $tel = $telstr;
        }
        return [$this->findNum($area), $this->findNum($tel)];
    }
    /**
     * @description: 取出字符串中的所有数字
     * @param String str 原字符串
     * @return String 纯数字字符串
     */
    function findNum($str = '') {
        $str = trim($str);
        if (empty($str)) return '';
        $result = '';
        for ($i = 0; $i < strlen($str); $i++) {
            if (is_numeric($str[$i])) $result .= $str[$i];
        }
        return $result;
    }
    /**
     * @description: 判断是否为Base64字符串
     * @param String b64string Base64字符串
     * @param Bool urlmode 是否为转换符号后的Base64字符串
     * @return Bool 是否为Base64字符串
     */
    function isbase64($b64string, $urlmode = false) {
        if ($urlmode) {
            if (preg_match("/[^0-9A-Za-z\-_]/", $b64string) > 0) return false;
        } else {
            if (preg_match("/[^0-9A-Za-z\+\/\=]/", $b64string) > 0) return false;
        }
        return true;
    }

    function wordfilterDbLoad(): array {
        global $nlcore;
        $columnArr = ["sw0", "sw1", "sw2", "sw3", "chrint"];
        $tableStr = $nlcore->cfg->db->tables["stopword"];
        $dbreturn = $nlcore->db->select($columnArr, $tableStr, []);
        $words = $dbreturn[2];
        for ($i = 0; $i < count($words); $i++) {
            $wordDic = $words[$i];
            $nowWordArr = [intval($wordDic["chrint"]), $wordDic["sw0"]];
            if (strlen($wordDic["sw1"]) > 0) array_push($nowWordArr, $wordDic["sw1"]);
            if (strlen($wordDic["sw2"]) > 0) array_push($nowWordArr, $wordDic["sw2"]);
            if (strlen($wordDic["sw3"]) > 0) array_push($nowWordArr, $wordDic["sw3"]);
            $words[$i] = $nowWordArr;
        }
        if ($dbreturn[0] >= 2000000) {
            die($nlcore->msg->m(2020303));
        }
        return $words;
    }
    /**
     * @description: 檢查是否包含違禁詞彙
     * @param String str 源字串
     * @param Bool errdie 如果出錯則完全中斷執行，直接返回錯誤資訊JSON給客戶端
     * @return Bool 是否為違禁詞
     */
    function wordfilter($str, $errdie = true): bool {
        global $nlcore;
        if (strlen($str) == 0) return false;
        $timeout = 86400;
        $wordsArr = [];
        $isRedisData = false;
        $rediskey = $nlcore->cfg->app->wordfilterRedisKey;
        $reloadSqlToRedis = $nlcore->cfg->app->wordfilterSQLtoRedis;
        if ($nlcore->db->initRedis()) {
            if (!$reloadSqlToRedis) {
                if ($nlcore->db->redis->exists($rediskey)) {
                    $words = $nlcore->db->redis->get($rediskey);
                    if (strlen($words) > 0) {
                        $wordsArr = json_decode($words);
                        $isRedisData = true;
                    } else {
                        $wordsArr = $this->wordfilterDbLoad();
                    }
                } else {
                    $wordsArr = $this->wordfilterDbLoad();
                }
            } else {
                $wordsArr = $this->wordfilterDbLoad();
            }
            if (!$isRedisData) {
                $nlcore->db->redis->setex($rediskey, $timeout, json_encode($wordsArr));
            }
        } else {
            $wordsArr = $this->wordfilterDbLoad();
        }
        for ($groupi = 0; $groupi < count($wordsArr); $groupi++) {
            $nowWordArr = $wordsArr[$groupi];
            $charSize = intval($nowWordArr[0]);
            $tmpStr = $str;
            $findi = 0;
            $wordlog = "";
            $nowWordArrCount = count($nowWordArr);
            for ($wordi = 1; $wordi < $nowWordArrCount; $wordi++) {
                $nowWord = $nowWordArr[$wordi];
                $pos = mb_strpos($tmpStr, $nowWord);
                if ($pos >= $charSize) {
                    $findi = 0;
                }
                if ($pos !== false && $pos >= 0) {
                    $tmpStr = mb_substr($tmpStr, $pos);
                    $wordlog .= "|" . $tmpStr . "->" . $nowWord;
                    $findi++;
                }
                if ($findi == $nowWordArrCount - 1) {
                    if ($errdie) {
                        $nlcore->func->log("W/StopWord", "触发敏感词: " . $wordlog);
                        $nlcore->msg->stopmsg(2020300);
                    } else {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    /**
     * @description: 字符串转字符数组，可以处理中文
     * @param String str 源字符串
     * @return Array 字符数组
     */
    function mbStrSplit($str) {
        return preg_split('/(?<!^)(?!$)/u', $str);
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
        $result = $nlcore->db->select(["id", "enabletime"], $table, $datadic, "", "OR");
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
            $result = $nlcore->db->insert($table, $datadic);
            if ($result[0] != 1010001) return [2020404];
            $ipid = $result[1];
        }
        if ($ipid == null) return [2020402];
        return [0, $ipid];
    }
    /**
     * @description: 检查 APP 是否已经注册 appname 和 appsecret
     * @param String appsecret 已注册的应用密钥app_id
     * @return String 数据库中的 ID 号 或 null
     */
    function chkAppKey($appKey) {
        global $nlcore;
        $table = $nlcore->cfg->db->tables["app"];
        $whereDic = array(
            "appkey" => $appKey
        );
        $result = $nlcore->db->select(["id"], $table, $whereDic);
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
     * @description: 檢查當前IP是否到達介面訪問頻率限制
     * @param String module 功能名稱（$cfg->limittime），提供此項將覆蓋下面兩項
     * @param Int interval 在多少秒內
     * @param Int times 允許請求多少次
     * @param Array<String,String> module 自定義次數限制參數
     * @return Array<Int,Int> [狀態碼,第幾次請求]
     * 如果未載入Redis則自動關閉此功能，直接返回通過，請求數返回-1
     */
    function frequencylimitation(string $module = "", int $interval = PHP_INT_MAX, int $times = PHP_INT_MAX) {
        global $nlcore;
        if (strlen($module) > 0) {
            $conf = $nlcore->cfg->app->limittime[$module] ?? $nlcore->cfg->app->limittime["default"];
            $interval = $conf[0];
            $times = $conf[1];
        }
        if (!$nlcore->db->initRedis()) return [1000000, -1];
        $redis = $nlcore->db->redis;
        $key = $nlcore->cfg->db->redis_tables["frequencylimitation"] . $this->getip();
        $check = $redis->exists($key);
        if ($check) {
            $redis->incr($key);
            $count = $redis->get($key);
            if ($count > $times) {
                return [2020407, $count];
            }
        } else {
            $redis->incr($key);
            $redis->expire($key, $interval);
        }
        $count = $redis->get($key);
        return [1000000, $count];
    }
    /**
     * @description: 检查数据提交方式是否被允许，并自动选择提交方式获取数据
     * @param String allowmethod 允许的提交方式数组
     * @return Array 客户端提交的数据
     */
    function getarg($allowmethod = ["POST", "GET"]) {
        global $nlcore;
        $argvs = null;
        if (!isset($_SERVER['REQUEST_METHOD'])) die(header(self::HEADER_405));
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == "POST" && in_array("POST", $allowmethod)) {
            $argvs = $_POST;
        } else if ($method == "GET" && in_array("GET", $allowmethod)) {
            $argvs = $_GET;
        } else if ($method == "FILES" && in_array("FILES", $allowmethod)) {
            $argvs = $_FILES;
            // } else if ($method == "PUT" && in_array("PUT",$allowmethod)) {
            //     $argvs = $_PUT;
        } else if ($method == "DELETE" && in_array("DELETE", $allowmethod)) {
            $argvs = $_SERVER['REQUEST_URI'];
        } else {
            die(header(self::HEADER_405));
        }
        return $argvs;
    }
    /**
     * @description: 数据发送和接收时进行记录
     * @param String mode 记录类型
     * @param Array logarr 信息数组
     */
    function log(string $mode, array $logarr): void {
        global $nlcore;
        $logfilepath = $nlcore->cfg->db->logfile_ud;
        if ($logfilepath == null || $logfilepath == "") return;
        if ($logfilepath) {
            $ipaddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
            $proxyaddr = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? "@" . $_SERVER['HTTP_X_FORWARDED_FOR'] : "";
            // 防止日志文件泄露密码
            if (isset($logarr["password"]) && is_string($logarr["password"])) {
                $newpassword = "";
                for ($i = 0; $i < strlen($logarr["password"]); $i++) {
                    $newpassword .= "*";
                }
                $logarr["password"] = $newpassword;
            }
            $log = json_encode($logarr);
            if (!$log) $log = "(DATA)";
            $logstr = "[" . $this->getdatetime()[1] . "][" . $ipaddr . $proxyaddr . "][" . $mode . "] " . $log . PHP_EOL;
            if (!$this->logfile) $this->logfile = fopen($logfilepath, "a");
            fwrite($this->logfile, $logstr);
        }
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
    function replacestr($string, $findreplace, $geti = false) {
        $find = array_keys($findreplace);
        $replace = array_values($findreplace);
        $newstring = str_replace($find, $replace, $string, $replacei);
        if ($geti) return [$newstring, $replacei];
        return $newstring;
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
        $typei = [0, 0, 0, 0]; //0:其他字符 1:数字 2:小写字母 3:大写字母
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
    function keyinarray($nowarray, $keys, $getcount = true) {
        $novalkey = array();
        foreach ($keys as $nowkey) {
            if (!isset($nowarray[$nowkey])) {
                array_push($novalkey, $nowkey);
            }
        }
        if ($getcount) return count($novalkey);
        return $novalkey;
    }
    /**
     * @description: 为数组中的所有键增加一个前缀
     * @param Array arr 要处理的数组
     * @param String prefix 要添加的前缀
     * @return Array 修改后的数组
     */
    function arraykeyprefix($arr = null, $prefix = "") {
        if (!isset($arr) || count($arr) == 0) return $arr;
        $newarr = [];
        foreach ($arr as $key => $value) {
            $newkey = $prefix . $key;
            $newarr[$newkey] = $value;
        }
        return $newarr;
    }
    /**
     * @description: 将
     * {"key1":["val1","val2"],"key2":["val1","val2"]...}
     * 转换为
     * [{"key1":"val1"},{"key2":"val2"}...]
     * @param Array 需要整理的数组(根为关联数组)
     * @return Array 转换后的数组(根为索引数组)
     */
    function dicvals2arrsdic($dic) {
        $newarr = [];
        if (!$dic || count($dic) == 0) return $newarr;
        $keys = array_keys($dic);
        $datacount = count($dic[$keys[0]]);
        for ($i = 0; $i < $datacount; $i++) {
            $nowdata = [];
            foreach ($keys as $nowkey) {
                $nowdata[$nowkey] = $dic[$nowkey][$i];
            }
            array_push($newarr, $nowdata);
        }
        return $newarr;
    }
    /**
     * @description: 数组是否全都是 null
     * @param Array nowarray 需要被测试的数组
     * @return Bool 是否全都是 null
     */
    function allnull($nowarray) {
        if (!$nowarray || count($nowarray) == 0) return false;
        foreach ($nowarray as $key => $value) {
            if ($value != null) return false;
        }
        return true;
    }
    /**
     * @method 多维数组转一维数组
     * @param Array array 多维数组
     * @return Array array 一维数组
     */
    function multiAarray2array($array) {
        static $result_array = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->multiAarray2array($value);
            } else
                $result_array[$key] = $value;
        }
        return $result_array;
    }
    /**
     * @description: 对明文密码进行加密以便存储到数据库
     * 原文+自定义盐+注册时间戳 的 MD6
     * @param String password 明文密码
     * @param Int timestamp 密码到期时间时间戳
     * @param String timestamp 密码到期时间字符串（将自动转时间戳）
     * @return 加密后的密码
     */
    function passwordhash($password, $timestamp) {
        global $nlcore;
        if (!is_int($timestamp)) $timestamp = strtotime($timestamp);
        $passwordhash = $password . $nlcore->cfg->app->passwordsalt . strval($timestamp);
        $passwordhash = $this->md6($passwordhash);
        return $passwordhash;
    }
    /**
     * @description: 获得需要使用的语言
     * 根据配置中设定的语言支持情况和当前浏览器语言，决定当前要使用的语言代码。
     * 如果找不到所需语言，则采用默认语言。
     * @param String language 指定一个语言
     * @return String i18n 语言代码
     */
    function getlanguage($language = null) {
        global $nlcore;
        $applanguages = $nlcore->cfg->app->language;
        if ($language) {
            foreach ($applanguages as $nowapplang) {
                if ($language == $nowapplang) {
                    return $language;
                }
            }
        } else {
            $browserlanguages = explode(",", strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
            foreach ($browserlanguages as $nowbwrlang) {
                foreach ($applanguages as $nowapplang) {
                    if ($nowbwrlang == $nowapplang) {
                        return $nowbwrlang;
                    }
                }
            }
        }
        return $applanguages[0];
    }
    /**
     * @description: 检查是否为媒体文件的命名格式
     * @param String filename 指定一个语言
     * @return Bool 是否为媒体文件的命名格式
     */
    function ismediafilename($filename) {
        return preg_match("/[\w\/]*[\d]{11}_[\w]{32}/", $filename);
    }
    /**
     * @description: 将一个整数中的每一位数字转换为数组
     * 例如输入: 12345, 输出: [5,4,3,2,1]
     * @param Int num 一个整数
     * @param Bool inverted 倒序输出 [1,2,3,4,5]
     * @return Array 单个数字数组
     */
    function intExtract(int $num, bool $inverted = false): array {
        $numI = 0;
        $numArr = [];
        while ($num > 0) {
            $numI = $num % 10;
            if ($inverted) {
                array_unshift($numArr, $numI);
            } else {
                array_push($numArr, $numI);
            }
            $num = intval($num / 10);
        }
        return $numArr;
    }
    /**
     * @description: 检查颜色格式，并转换为 ARGB 数字字符串。
     * @param String inColor 输入一个 ARGB/RGB 颜色，支持以下 6 种格式字符串：
     * - 16 进制: '0xFFFFFFFF', 'FFFFFFFF', 'FFFFFF', 'FFF'
     * - 10 进制: '255,255,255,255', '255,255,255'
     * @param Int returnMode 返回信息模式
     * @param Bool useHEX 是否使用 16 进制，例如
     * - T: 十六进制字符串
     *     - 例如 'FFFFFFFF' : A=FF R=FF G=FF B=FF
     * - F: 数字字符串（单色不足位补0）
     *     - 例如 '255255255255' : A=255 R=255 G=255 B=255
     * @param Bool alpha 是否保留 Alpha 值
     * - T: RGB 固定 12 位 数字字符串 或 固定 8 位 十六进制字符串
     * - F: RGB 固定  9 位 数字字符串 或 固定 6 位 十六进制字符串
     * @return String 按以上设置返回字符串
     */
    function eColor(string $inColor = "000", bool $useHEX = false, bool $alpha = false): string {
        global $nlcore;
        $color = [0, 0, 0, 0]; // ARGB
        if (strstr($inColor, ',') !== false) {
            $colorArr = explode(',', $inColor);
            $colorArrCount = count($colorArr);
            if ($colorArrCount >= 3 && $colorArrCount <= 4) {
                $modeARGB = $colorArrCount - 3;
                $color[0] = $modeARGB == 0 ? 255 : intval($colorArr[0]);
                $color[1] = intval($colorArr[0 + $modeARGB]);
                $color[2] = intval($colorArr[1 + $modeARGB]);
                $color[3] = intval($colorArr[2 + $modeARGB]);
            } else {
                $nlcore->msg->stopmsg(2020208, $inColor);
            }
        } else {
            if (strlen($inColor) == 3) {
                $colorArr = str_split($inColor);
                for ($i = 0; $i < count($colorArr); $i++) {
                    $nowColor = $colorArr[$i];
                    $colorArr[$i] = $nowColor . $nowColor;
                }
                $inColor = implode('', $colorArr);
            }
            $colorArr = str_split($inColor, 2);
            $colorArrCount = count($colorArr);
            if ($colorArrCount >= 3 && $colorArrCount <= 5) {
                if (strcmp($colorArr[0], "0x") == 0) {
                    array_shift($colorArr);
                    $colorArrCount--;
                }
                $modeARGB = $colorArrCount - 3;
                $color[0] = $modeARGB == 0 ? 255 : hexdec($colorArr[0]);
                $color[1] = hexdec($colorArr[0 + $modeARGB]);
                $color[2] = hexdec($colorArr[1 + $modeARGB]);
                $color[3] = hexdec($colorArr[2 + $modeARGB]);
            } else {
                $nlcore->msg->stopmsg(2020208, $inColor);
            }
        }
        $saveStr = ""; // 12
        $rmAlpha = $alpha ? 0 : 1;
        for ($i = $rmAlpha; $i < count($color); $i++) {
            $nowColor = $color[$i];
            if ($nowColor < 0 || $nowColor > 255) {
                $nlcore->msg->stopmsg(2020208, strval($nowColor));
            } else {
                if ($useHEX) {
                    $saveStr .= strtoupper(dechex($nowColor));
                } else {
                    $saveStr .= str_pad(strval($nowColor), 3, '0', STR_PAD_LEFT);
                }
            }
        }
        return $saveStr;
    }
    /**
     * @description: 將以整數儲存的顏色轉換會16進位制
     * @param Int inColor 整數顏色程式碼（上面的函式按預設值返回的結果樣式）
     * @param Bool alpha 是否包含 alpha 值
     * @return String 16進位制顏色
     */
    function dColor(int $inColor, bool $alpha = false) {
        $fullLength = $alpha ? 12 : 9;
        $colorStr = str_pad(strval($inColor), $fullLength, '0', STR_PAD_LEFT);
        $colorArr = str_split($colorStr, 3);
        for ($i = 0; $i < count($colorArr); $i++) {
            $color = intval($colorArr[$i]);
            $colorArr[$i] = dechex($color);
        }
        return strtoupper(implode('', $colorArr));
    }
    /**
     * @description: TODO: 檢查使用者是否擁有某項許可權
     */
    function permission():bool {
        return true;
    }
    /**
     * @description: 析構，關閉日誌檔案
     */
    function __destruct() {
        if ($this->logfile) {
            fclose($this->logfile);
            $this->logfile = null;
        }
        unset($this->logfile);
    }
}
