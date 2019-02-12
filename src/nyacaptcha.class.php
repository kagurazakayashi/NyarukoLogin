<?php
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
class nyacaptcha {
    var $nyadbconnect;
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
    function getcaptcha($extnow=true,$showcaptcha=false,$showimage=false) {
        global $nlcore;
        $jsonarrTotpsecret = $nlcore->safe->decryptargv("captcha");
        $jsonarr = $jsonarrTotpsecret[0];
        $totpsecret = $jsonarrTotpsecret[1];
        $totptoken = $jsonarrTotpsecret[2];
        $captchaconf = $nlcore->cfg->verify->captcha;
        $c_time = date('Y-m-d h:i:s', time());
        $c_img = $nlcore->safe->randhash();
        $phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
        $imgfile = $captchaconf["imgname"].$c_img.".jpg";

        //生成验证码
        $phraseBuilder = new PhraseBuilder($captchaconf["codelen"],$captchaconf["charset"]);
        $builder = new CaptchaBuilder(null, $phraseBuilder);
        if (!$showcaptcha) $builder->build();
        if ($showimage) {
            header('Content-type: image/jpeg');
            die($builder->output());
        }
        if (!$showcaptcha) {
            $imgpath = $phpfiledir."..".DIRECTORY_SEPARATOR.$captchaconf["imgdir"].DIRECTORY_SEPARATOR.$imgfile;
            $imgurl = $nlcore->cfg->app->appurl.$captchaconf["imgdir"].'/'.$imgfile;
            $builder->save($imgpath);
        }
        $c_code = $builder->getPhrase();

        //写入数据库
        $updateDic = [
            "c_code" => $c_code,
            "c_time" => $c_time
        ];
        if (!$showcaptcha) {
            $updateDic["c_img"] = $c_img;
        }
        $tableStr = $nlcore->cfg->db->tables["session_totp"];
        $whereDic = [
            "apptoken" => $totptoken
        ];
        $dbreturn = $nlcore->db->update($updateDic,$tableStr,$whereDic);
        $retuenarr = [
            "code" => 1000000,
            "img" => $imgurl,
            "time" => $c_time
        ];
        if ($showcaptcha) {
            $retuenarr["captcha"] = $c_code;
        } else {
            if (!$extnow) $retuenarr["file"] = $imgpath;
        }
        if ($extnow) {
            echo $nlcore->safe->encryptargv($retuenarr,$totpsecret);
        } else {
            return $retuenarr;
        }
        return null;

        // if($builder->testPhrase($userInput)) {}
    }

    /**
     * @description: 验证码没有验证失败后用此函数重新创建一个
     * @param String code 验证码内容
     * @param String totpsecret totp关联码
     * @return Array<String> 验证码相关信息（其中code、msg会不同）
     */
    function verifyfailgetnew($code,$totpsecret) {
        $retuenarr = $this->getcaptcha(false);
        $retuenarr["code"] = $code;
        $retuenarr["msg"] = $nlcore->msg->imsg[$code];
        echo $nlcore->safe->encryptargv($retuenarr,$totpsecret);
    }

    /**
     * @description: 验证图形验证码是否正确
     * @param String captchacode 验证码
     * @param String totpsecret totp关联码
     * @return Bool 是否可以通行
     */
    function verifycaptcha($captchacode,$totpsecret) {
        global $nlcore;
        $columnArr = ["id","c_code","c_time"];
        $tableStr = $nlcore->cfg->db->tables["session_totp"];
        $whereDic = [
            "apptoken" => $totpsecret
        ];
        $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        if ($dbreturn[0] != 1010000) {
            die($nlcore->msg->m(2020501));
        }
        $cinfo = $dbreturn[2][0];
        $c_time = strtotime($cinfo["c_time"]);
        $endtime = $c_time+$nlcore->cfg->verify->captcha["validtime"];
        if (time() > $endtime) {
            $this->verifyfailgetnew(2020502,$totpsecret);
            return false;
        }
        if ($captchacode != $cinfo["c_code"]) {
            $this->verifyfailgetnew(2020503,$totpsecret);
            return false;
        }
        //删除已经验证通过的信息
        $updateDic = [
            "c_code" => null,
            "c_time" => null,
            "c_img" => null
        ];
        $whereDic = [
            "id" => $cinfo["id"]
        ];
        $dbreturn = $nlcore->db->update($updateDic,$tableStr,$whereDic);
        return true;
    }

    //TODO: 检索用户名是否需要验证
    
}
?>