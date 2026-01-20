<?php
declare(strict_types=1);

/**
 * 安全與驗證工具類
 *
 * 提供 RSA 加密解密、雜湊函式（MD5/MD6）、隨機字串生成、
 * 字串驗證（電子郵件、IP、電話號碼等）、密碼強度檢查、
 * 違禁詞過濾、IP 封禁檢查、存取頻率限制等功能。
 *
 * @package NyarukoLogin
 */
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
     * 建構子
     *
     * @return void
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
     * 快捷補充金鑰對首尾標記，從快取區載入和儲存
     *
     * @return void
     */
    function autoRsaAddTag(): void {
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
     * 移除前部分資料
     *
     * @param string $str 在 rsaRmTag 之後的資料
     * @return string 削減後的金鑰
     */
    function rsaRmBCode(string $str): string {
        return substr($str, 32);
    }
    /**
     * 補充前部分資料
     *
     * @param string $str 在 rsaRmBCode 之後的資料
     * @param bool $isPrivateKey 是否為私鑰（預設 false）
     * @return string 用於 rsaAddTag 的全內容金鑰
     */
    function rsaAddBCode(string $str, bool $isPrivateKey = false): string {
        if ($isPrivateKey) {
            return self::PKB_PRIB_B . $str;
        } else {
            return self::PKB_PUBB_B . $str;
        }
    }
    /**
     * 進行 Base64 編碼，並取代一些符號（"+"→"-", "/"→"_", "=" 刪除）
     *
     * @param string $fdata 需要使用 Base64 編碼的資料
     * @return string 編碼後的字串
     */
    function urlb64encode(string $fdata): string {
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
     * 是否為 MD5
     *
     * @param string $str 需要判斷的字串
     * @return int|false 是否匹配：> 0 或 != false
     */
    function is_md5(string $str): int|false {
        return preg_match("/^[a-z0-9]{32}$/", $str);
    }
    /**
     * 是否為 MD6
     *
     * @param string $str 需要判斷的字串
     * @param int $length 預期的字串長度（預設 64）
     * @return int|false 是否匹配：> 0 或 != false
     */
    function is_md6(string $str, int $length = 64): int|false {
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
     * @return Bool 是否匹配
     */
    function is_rhash64(string $str, int $length = 64):bool {
        if (!ctype_alnum($str)) return false;
        if (strlen($str) != $length) return false;
        return true;
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
     * 驗證是否為包含大小寫的 MD5 變體字串
     *
     * @param string $str MD5 變體字串
     * @return bool 是否匹配
     */
    function is_rhash32(string $str): bool {
        return $this->is_rhash64($str,32);
    }
    /**
     * 生成一段隨機文字
     *
     * @param int $len 生成長度（預設 64）
     * @param string $chars 從此字串中抽取字元（預設為英數字混合）
     * @return string 新生成的隨機文字
     */
    function randstr(int $len = 64, string $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'): string {
        mt_srand($this->seed());
        $password = "";
        while (strlen($password) < $len) $password .= substr($chars, (mt_rand() % strlen($chars)), 1);
        return $password;
    }
    /**
     * 生成隨機雜湊值
     *
     * @param string $salt 鹽（可選）
     * @param bool $md6 使用 MD6 生成隨機雜湊值（64 位），否則用 MD5 生成（32 位）
     * @param bool $randstrto 將雜湊結果隨機大小寫
     * @return string 生成的雜湊字串
     */
    function randhash(string $salt = "", bool $md6 = true, bool $randstrto = true): string {
        $data = (float)microtime() . $salt . $this->randstr(16);
        $data = $md6 ? $this->md6($data) : md5($data);
        return $randstrto ? $this->randstrto($data) : $data;
    }
    /**
     * 生成亂數發生器種子
     *
     * @param string $salt 鹽（可選）
     * @return string 種子
     */
    function seed(string $salt = ""): string {
        $newsalt = (float)microtime() * 1000000 * getmypid();
        return $newsalt . $salt;
    }
    /**
     * 獲得當前時間
     *
     * @param string|null $timezone PHP 時區代碼（可選，預設為配置檔中配置的時區）
     * @param int|null $settime 自定義時間戳（秒，可選）
     * @return array [時間戳, 時間日期字串]
     */
    function getdatetime(string|null $timezone = null, int|null $settime = null): array {
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
     * 獲得毫秒級時間戳
     *
     * @return string 毫秒時間戳字串
     */
    function millisecondtimestamp(): string {
        $time = explode(" ", strval(microtime()));
        $time = $time[1] . (intval($time[0]) * 1000);
        $time2 = explode(".", $time);
        $time = $time2[0];
        return $time;
    }
    /**
     * 檢查時間戳差異是否大於配置檔中的值
     *
     * @param string|int $timestamp1 秒級時間戳 1
     * @param string|int $timestamp2 秒級時間戳 2
     * @return void 成功不返回，失敗直接返回錯誤代碼給客戶端
     */
    function timestampdiff(string|int $timestamp1, string|int $timestamp2): void {
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
     * 從字典中抽出指定的 Key，並生成一個新的字典
     *
     * @param array $dicArr 原始字典陣列
     * @param string ...$keys 要取出的所有 Key
     * @return array 新建立的字典
     */
    function dicExtract(array $dicArr, string ...$keys): array {
        $newDic = [];
        foreach ($keys as $key) {
            if (isset($dicArr[$key])) {
                $newDic[$key] = $dicArr[$key];
            }
        }
        return $newDic;
    }
    /**
     * 替換字串中某個字元的多個連續字元，轉換成一個
     *
     * @param string $str 原字串
     * @param string $chr 要合併的字元
     * @return string 替換後的字串
     */
    function mergerepeatchar(string $str, string $chr): string {
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
     * 轉換為系統路徑
     *
     * @param string $path 路徑字串
     * @return string 轉換後的路徑字串
     */
    function dirsep(string $path): string {
        $newpath = str_replace("\\", DIRECTORY_SEPARATOR, $path);
        $newpath = str_replace("/", DIRECTORY_SEPARATOR, $newpath);
        return $this->mergerepeatchar($newpath, DIRECTORY_SEPARATOR);
    }
    /**
     * 轉換為網址路徑
     *
     * @param string $path 路徑字串
     * @return string 轉換後的路徑字串
     */
    function urlsep(string $path): string {
        $newpath = str_replace("\\", "/", $path);
        return $this->mergerepeatchar($newpath, "/");
    }
    /**
     * 自動清除路徑中的資料夾字元 (../)，可以在路徑的任何位置
     *
     * @param string $path 路徑字串
     * @param int $level 如果提供此數值，會改為手動向上父級多少級（預設 -1 為自動）
     * @return string 轉換後的路徑
     */
    function parentfolder(string $path, int $level = -1): string {
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
     * 檢查一維陣列中是否都為某個值
     *
     * @param mixed $search 搜尋目標值
     * @param array $array 要檢查的陣列
     * @param bool $type 使用全等（===）進行判斷（預設 true）
     * @return bool 是否都為某個值
     */
    function allinarray(mixed $search, array $array, bool $type = true): bool {
        foreach ($array as $value) {
            if (($type && $value !== $search) || (!$type && $value != $search)) return false;
        }
        return true;
    }
    /**
     * 將父資料夾字元 (../) 移除，並返回有多少層
     *
     * @param string $path 路徑字串
     * @return array [層數, 轉換後的路徑]
     */
    function parentfolderlevel(string $path): array {
        $pdirstr = ".." . DIRECTORY_SEPARATOR;
        $newpath = str_replace($pdirstr, "", $path, $ri);
        return [$ri, $newpath];
    }
    /**
     * 過濾字串中的非法字元
     *
     * @param string $str 源字串
     * @param bool $errdie 如果出錯則完全中斷執行，返回錯誤資訊 JSON（預設 true）
     * @param bool $dhtml 是否將 HTML 代碼也視為非法字元（預設 false）
     * @return string 經過過濾的字串
     */
    function safestr(string $str, bool $errdie = true, bool $dhtml = false): string {
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
     * 只保留字串中所有的非字母和數字
     *
     * @param string $str 源字串
     * @return string 經過過濾的字串
     */
    function retainletternumber(string $str): string {
        return preg_replace('/[\W]/', '', $str);
    }
    /**
     * 只保留字串中所有的數字
     *
     * @param string $str 源字串
     * @return string 經過過濾的字串
     */
    function retainnumber(string $str): string {
        return preg_replace('/[^\d]/', '', $str);
    }
    /**
     * 檢查是否為有效的電子郵件地址
     *
     * @param string $str 源字串
     * @return bool|null 是否正確（未提供字串時返回 null）
     */
    function isEmail(string $str): bool|null {
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
     * 檢查是否為 IPv4 地址
     *
     * @param string $ip IP 地址源字串
     * @return bool 是否正確
     */
    function isIPv4(string $ip): bool {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }
    /**
     * 檢查是否為 IPv6 地址
     *
     * @param string $ip IP 地址源字串
     * @return bool 是否正確
     */
    function isIPv6(string $ip): bool {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }
    /**
     * 檢查是否為私有 IP 地址
     *
     * @param string $ip IP 地址源字串
     * @return bool 是否為私有地址
     */
    function isPubilcIP(string $ip): bool {
        if (filter_var($ip, FILTER_FLAG_NO_PRIV_RANGE) || filter_var($ip, FILTER_FLAG_NO_RES_RANGE)) return false;
        return true;
    }
    /**
     * 判斷 IP 地址型別，輸出為存入資料庫的代碼
     *
     * @param string $ip IP 地址源字串
     * @return string 型別：other, ipv4, ipv6, ipv4_local, ipv6_local
     */
    function iptype(string $ip): string {
        $iptype = "other";
        if ($this->isIPv4($ip)) $iptype = "ipv4";
        else if ($this->isIPv6($ip)) $iptype = "ipv6";
        if ($this->isPubilcIP($ip)) $iptype = $iptype . "_local";
        return $iptype;
    }
    /**
     * 檢查是否為整數數字字串
     *
     * @param mixed $str 源字串或數值
     * @return bool 是否為整數
     */
    function isInt(mixed $str): bool {
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
     * 判斷字元型別
     *
     * @param string $str 源字串（單個字元）
     * @return int 0:其他字元, 1:數字, 2:小寫字母, 3:大寫字母
     */
    function chartype(string $str): int {
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
     * 檢查字串是否僅包含字母和數字並滿足指定長度條件
     *
     * @param string $str 源字串
     * @param int $minlen 至少需要多長（可選，-1 不限制）
     * @param int $maxlen 至多需要多長（可選，-1 不限制）
     * @return bool 是否正確
     */
    function isNumberOrEnglishChar(string $str, int $minlen = -1, int $maxlen = -1): bool {
        if (preg_match("/^[A-Za-z0-9]+$/i", $str) == 0) return false;
        $len = strlen($str);
        if ($minlen != -1 && $len < $minlen) return false;
        if ($maxlen != -1 && $len > $maxlen) return false;
        return true;
    }
    /**
     * 檢查是否為中國手機號碼格式
     *
     * @param string $str 源字串
     * @return bool 是否正確
     */
    function isPhoneNumCN(string $str): bool {
        $checktel = "/^1[345789]\d{9}$/";
        if (isset($str) && $str != "") {
            if (preg_match($checktel, $str)) {
                return true;
            }
        }
        return false;
    }
    /**
     * 分離手機號碼中的國別碼（如果沒有國別碼預設 +86），並去除所有符號
     *
     * @param string $telstr 電話號碼字串
     * @return array [國別碼數字字串, 電話號碼數字字串]
     */
    function telarea(string $telstr): array {
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
     * 取出字串中的所有數字
     *
     * @param string $str 原字串
     * @return string 純數字字串
     */
    function findNum(string $str = ''): string {
        $str = trim($str);
        if (empty($str)) return '';
        $result = '';
        for ($i = 0; $i < strlen($str); $i++) {
            if (is_numeric($str[$i])) $result .= $str[$i];
        }
        return $result;
    }
    /**
     * 判斷是否為 Base64 字串
     *
     * @param string $b64string Base64 字串
     * @param bool $urlmode 是否為轉換符號後的 Base64 字串
     * @return bool 是否為 Base64 字串
     */
    function isbase64(string $b64string, bool $urlmode = false): bool {
        if ($urlmode) {
            if (preg_match("/[^0-9A-Za-z\-_]/", $b64string) > 0) return false;
        } else {
            if (preg_match("/[^0-9A-Za-z\+\/\=]/", $b64string) > 0) return false;
        }
        return true;
    }

    /**
     * 從資料庫載入違禁詞列表
     *
     * @return array 違禁詞陣列
     */
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
     * 字串轉字元陣列，可以處理中文
     *
     * @param string $str 源字串
     * @return array<string>|false 字元陣列
     */
    function mbStrSplit(string $str): array|false {
        return preg_split('/(?<!^)(?!$)/u', $str);
    }
    /**
     * 檢查 IP 位址是否處於封禁期內
     *
     * @param int $time 當前時間 time()
     * @return array [狀態碼, IP 表的 id]
     */
    function chkip(int $time): array {
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
     * 檢查 APP 是否已經註冊 appkey
     *
     * @param string $appKey 已註冊的應用金鑰 app_id
     * @return string|null 資料庫中的 ID 號碼或 null
     */
    function chkAppKey(string $appKey): string|null {
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
     * 獲得真實 IP
     *
     * @return string IP 位址
     */
    function getip(): string {
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
     * 檢查當前 IP 是否到達介面存取頻率限制
     *
     * @param string $module 功能名稱（$cfg->limittime），提供此項將覆蓋下面兩項
     * @param int $interval 在多少秒內
     * @param int $times 允許請求多少次
     * @return array [狀態碼, 第幾次請求]（如果未載入 Redis 則自動關閉，直接返回通過，請求數返回 -1）
     */
    function frequencylimitation(string $module = "", int $interval = PHP_INT_MAX, int $times = PHP_INT_MAX): array {
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
     * 檢查資料提交方式是否被允許，並自動選擇提交方式獲取資料
     *
     * @param array $allowmethod 允許的提交方式陣列（預設 ["POST", "GET"]）
     * @return array|null 客戶端提交的資料
     */
    function getarg(array $allowmethod = ["POST", "GET"]): array|null {
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
            $logstr = "[" . $this->getdatetime()[1] . "][" . $ipaddr . $proxyaddr . "][" . $_SERVER['PHP_SELF'] . "][" . $mode . "] " . $log . PHP_EOL;
            if (!$this->logfile) $this->logfile = fopen($logfilepath, "a");
            fwrite($this->logfile, $logstr);
        }
    }
    /**
     * 批次替換指定字元
     *
     * @param string $string 規定被搜尋的字串
     * @param array $findreplace 要替換的內容字典，例如：["find" => "replace"]
     * @param bool $geti 是否返回替換的數量，設定為 true 會返回陣列
     * @return string|array 經過替換後的字串，或 [字串, 替換數量]
     */
    function replacestr(string $string, array $findreplace, bool $geti = false): string|array {
        $find = array_keys($findreplace);
        $replace = array_values($findreplace);
        $newstring = str_replace($find, $replace, $string, $replacei);
        if ($geti) return [$newstring, $replacei];
        return $newstring;
    }
    /**
     * 密碼健壯性檢查（長度、特定字元長度、字元合法性）
     *
     * @param string $password 要檢查的密碼
     * @return void 有錯誤直接返回客戶端
     */
    function strongpassword(string $password): void {
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
     * 檢查陣列中是否包括指定的一組 key
     *
     * @param array $nowarray 需要被測試的陣列
     * @param array $keys 需要檢查是否有哪些 key 的陣列
     * @param bool $getcount 是否返回不匹配的數量（true），否則返回具體不匹配的 key 陣列（false）
     * @return int|array 不匹配的數量或 key 陣列
     */
    function keyinarray(array $nowarray, array $keys, bool $getcount = true): int|array {
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
     * 為陣列中的所有鍵增加一個前綴
     *
     * @param array|null $arr 要處理的陣列
     * @param string $prefix 要添加的前綴
     * @return array|null 修改後的陣列
     */
    function arraykeyprefix(array|null $arr = null, string $prefix = ""): array|null {
        if (!isset($arr) || count($arr) == 0) return $arr;
        $newarr = [];
        foreach ($arr as $key => $value) {
            $newkey = $prefix . $key;
            $newarr[$newkey] = $value;
        }
        return $newarr;
    }
    /**
     * 將 {"key1":["val1","val2"],"key2":["val1","val2"]...} 轉換為 [{"key1":"val1"},{"key2":"val2"}...]
     *
     * @param array $dic 需要整理的陣列（根為關聯陣列）
     * @return array 轉換後的陣列（根為索引陣列）
     */
    function dicvals2arrsdic(array $dic): array {
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
     * 陣列是否全都是 null
     *
     * @param array|null $nowarray 需要被測試的陣列
     * @return bool 是否全都是 null
     */
    function allnull(array|null $nowarray): bool {
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
    function multiAarray2array(array $array): array {
        $result_array = [];
        array_walk_recursive($array, function ($value, $key) use (&$result_array) {
            $result_array[$key] = $value;
        });
        return $result_array;
    }
    /**
     * 對明文密碼進行加密以便儲存到資料庫（原文+自定義鹽+註冊時間戳 的 MD6）
     *
     * @param string $password 明文密碼
     * @param int|string $timestamp 密碼到期時間時間戳或時間字串
     * @return string 加密後的密碼
     */
    function passwordhash(string $password, int|string $timestamp): string {
        global $nlcore;
        if (!is_int($timestamp)) $timestamp = strtotime($timestamp);
        $passwordhash = $password . $nlcore->cfg->app->passwordsalt . strval($timestamp);
        $passwordhash = $this->md6($passwordhash);
        return $passwordhash;
    }
    /**
     * 獲得需要使用的語言代碼
     *
     * @param string|null $language 指定一個語言代碼（可選）
     * @return string i18n 語言代碼
     */
    function getlanguage(string|null $language = null): string {
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
     * 檢查是否為媒體檔案的命名格式
     *
     * @param string $filename 檔案名稱
     * @return bool 是否為媒體檔案的命名格式
     */
    function ismediafilename(string $filename): bool {
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
     * 將以整數儲存的顏色轉換回 16 進位制
     *
     * @param int $inColor 整數顏色代碼
     * @param bool $alpha 是否包含 alpha 值（預設 false）
     * @return string 16 進位顏色字串
     */
    function dColor(int $inColor, bool $alpha = false): string {
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
     * 解構子：關閉日誌檔案
     *
     * @return void
     */
    function __destruct() {
        if ($this->logfile) {
            fclose($this->logfile);
            $this->logfile = null;
        }
        unset($this->logfile);
    }
}
