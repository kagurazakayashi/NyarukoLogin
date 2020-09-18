<?php

/**
 * @description: 圖形驗證碼的建立和驗證
 * @package NyarukoLogin
 */

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

class nyacaptcha {
    function __construct() {
    }
    /**
     * @description: 创建验证码
     * @param Bool extnow 是否立即将信息返回给客户端
     * @param Bool showcaptcha 是否直接返回验证码明码，而不是图片
     * @param Bool showimage 是否直接输出验证码图片
     * @return Array<String> 验证码相关信息：
     * code 状态码
     * time 验证码生成时间
     * img 验证码文件名（不包括扩展名和路径）
     * captcha 验证码内容
     * file 验证码图片本地存储路径(extnow 时不输出)
     * url 验证码图片网址
     */
    function getcaptcha($extnow = true, $showcaptcha = false, $showimage = false) {
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
        //写入数据库
        $updateDic = [
            "vc1code" => $vc1code,
            "vc2time" => $time
        ];
        $tableStr = $nlcore->cfg->db->tables["encryption"];
        $whereDic = [
            "apptoken" => $appToken
        ];
        $dbreturn = $nlcore->db->update($updateDic, $tableStr, $whereDic);
        $retuenarr = [
            "code" => 1000000,
            "img" => $imgurl
        ];
        if ($showcaptcha) {
            $retuenarr["captcha"] = $vc1code;
        }
        if ($debug) {
            $retuenarr["debug"] = $debug;
        }
        if ($extnow) {
            echo $nlcore->sess->encryptargv($retuenarr);
        } else {
            return $retuenarr;
        }
        return null;
    }

    /**
     * @description: 验证码验证失败后用此函数重新创建一个
     * @param String code 错误代码
     * @return Array<String> 验证码相关信息（其中code、msg会不同）
     */
    function verifyfailgetnew($code) {
        global $nlcore;
        $retuenarr = $this->getcaptcha(false);
        $retuenarr["code"] = $code;
        $retuenarr["msg"] = $nlcore->msg->imsg[$code];
        echo $nlcore->sess->encryptargv($retuenarr);
        return $retuenarr;
    }

    /**
     * @description: 验证图形验证码是否正确
     * @param String captchacode 验证码
     * @param String totpsecret totp加密码
     * @return Bool 是否可以通行
     */
    function verifycaptcha($appToken, $captchacode) {
        global $nlcore;
        $columnArr = ["id", "vc1code", "vc1time"];
        $tableStr = $nlcore->cfg->db->tables["encryption"];
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
        //删除已经验证通过的信息
        $updateDic = [
            "vc1code" => null,
            "vc1time" => null
        ];
        $whereDic = [
            "id" => $cinfo["id"]
        ];
        $dbreturn = $nlcore->db->update($updateDic, $tableStr, $whereDic);
        return true;
    }
}
