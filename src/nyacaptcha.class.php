<?php
declare(strict_types=1);

/**
 * 圖形驗證碼的建立與驗證
 *
 * 提供圖形驗證碼的生成、儲存、驗證功能，支援 Redis 與 MySQL 兩種儲存後端。
 *
 * @package NyarukoLogin
 */

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

class nyacaptcha {
    function __construct() {
    }
    /**
     * 建立圖形驗證碼
     *
     * @param bool $extnow      是否立即將資訊返回給客戶端
     * @param bool $showcaptcha 是否直接返回驗證碼明碼而非圖片
     * @param bool $showimage   是否直接輸出驗證碼圖片
     * @return ?array 驗證碼相關資訊陣列，若 $extnow 為 true 則直接輸出並返回 null
     */
    function getcaptcha(bool $extnow = true, bool $showcaptcha = false, bool $showimage = false): ?array {
        global $nlcore;
        $debug = $nlcore->cfg->verify->debug;
        $appToken = $nlcore->sess->appToken;
        $captchaconf = $nlcore->cfg->verify->captcha;
        $imgfname = $nlcore->safe->randhash();
        $phpfiledir = pathinfo(__FILE__)["dirname"] . DIRECTORY_SEPARATOR;
        $imgfile = $captchaconf["imgname"] . $imgfname . ".jpg";
        $time = $nlcore->safe->getnowtimestr();
        //生成验证码
        $phraseBuilder = new PhraseBuilder($captchaconf["codelen"], $captchaconf["charset"]);
        $builder = new CaptchaBuilder(null, $phraseBuilder);
        if (!$showcaptcha) $builder->build();
        if ($showimage) {
            header('Content-type: image/jpeg');
            exit($builder->output());
        }
        if (!$showcaptcha) {
            $imgpath = $phpfiledir . ".." . DIRECTORY_SEPARATOR . $captchaconf["imgdir"] . DIRECTORY_SEPARATOR . $imgfile;
            $imgurl = $nlcore->cfg->app->appurl . $captchaconf["imgdir"] . '/' . $imgfile;
            $builder->save($imgpath);
        }
        $vc1code = $builder->getPhrase();
        if ($nlcore->db->initRedis()) {
            // 嘗試儲存到 Redis
            $timeout = $nlcore->cfg->verify->timeout["captcha"];
            $redis = $nlcore->db->redis;
            $key = $this->redisKeyName();
            // 檢查是否有驗證碼資料
            if ($redis->exists($key)) {
                $redis->del($key); // 有則刪除
            }
            // 儲存驗證碼
            $redis->setex($key, $timeout, $vc1code);
        } else {
            // 嘗試儲存到 MySQL
            $updateDic = [
                "vc1code" => $vc1code,
                "vc1time" => $time
            ];
            $tableStr = $nlcore->cfg->db->tables["encryption"];
            $whereDic = [
                "apptoken" => $appToken
            ];
            $result = $nlcore->db->update($updateDic, $tableStr, $whereDic);
            if ($result[0] >= 2000000) {
                $nlcore->msg->stopmsg(2020601);
            }
        }
        $retuenarr = [
            "code" => 1000000,
            "img" => $imgurl
        ];
        if ($showcaptcha) {
            $retuenarr["captcha"] = $vc1code;
        }
        if ($debug) {
            $retuenarr["debug"] = $vc1code;
        }
        if ($extnow) {
            echo $nlcore->sess->encryptargv($retuenarr);
        } else {
            return $retuenarr;
        }
        return null;
    }
    /**
     * 建立 Redis 鍵名
     *
     * @return string Redis 鍵名
     */
    function redisKeyName(): string {
        global $nlcore;
        return $nlcore->cfg->db->redis_tables["vcode1"] . $nlcore->sess->appToken;
    }

    /**
     * 驗證碼驗證失敗後重新建立一個驗證碼
     *
     * @param string $code 錯誤狀態碼
     * @return array 驗證碼相關資訊（其中 code、msg 會不同）
     */
    function verifyfailgetnew(string $code): array {
        global $nlcore;
        $retuenarr = $this->getcaptcha(false);
        $retuenarr["code"] = $code;
        $retuenarr["msg"] = $nlcore->msg->imsg[$code];
        echo $nlcore->sess->encryptargv($retuenarr);
        return $retuenarr;
    }

    /**
     * 驗證圖形驗證碼是否正確
     *
     * @param string $appToken    客戶端令牌
     * @param string $captchacode 驗證碼
     * @return bool 是否可以通行
     */
    function verifycaptcha(string $appToken, string $captchacode): bool {
        if (strlen($captchacode) < 4) return false;
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["encryption"];
        if ($nlcore->db->initRedis()) {
            // 嘗試從 Redis 載入
            $redis = $nlcore->db->redis;
            $key = $this->redisKeyName();
            // 檢查是否有驗證碼資料
            if ($redis->exists($key)) {
                $saveCode = $redis->get($key);
                // 檢查驗證碼是否匹配
                if (strtolower($captchacode) != strtolower($saveCode)) {
                    $this->verifyfailgetnew(2020505); // 不匹配
                    return false;
                }
                // 從 Redis 刪除已經驗證的碼
                $redis->del($key);
            } else {
                $nlcore->msg->stopmsg(2020505);
            }
        } else {
            // 嘗試從 MySQL 載入
            $columnArr = ["id", "vc1code", "vc1time"];
            $whereDic = [
                "apptoken" => $appToken
            ];
            $dbreturn = $nlcore->db->select($columnArr, $tableStr, $whereDic);
            if ($dbreturn[0] != 1010000) {
                die($nlcore->msg->m(2020501));
            }
            $cinfo = $dbreturn[2][0];
            $vc1time = strtotime($cinfo["vc1time"]);
            $endtime = $vc1time + $nlcore->cfg->verify->timeout["captcha"];
            if (time() > $endtime) {
                $this->verifyfailgetnew(2020502);
                return false;
            }
            if (strtolower($captchacode) != strtolower($cinfo["vc1code"])) {
                $this->verifyfailgetnew(2020503);
                return false;
            }
        }
        // 從 MySQL 刪除已經驗證的碼
        $updateDic = [
            "vc1code" => null,
            "vc1time" => null
        ];
        $whereDic = [
            "apptoken" => $appToken
        ];
        $dbreturn = $nlcore->db->update($updateDic, $tableStr, $whereDic);
        return true;
    }
}
