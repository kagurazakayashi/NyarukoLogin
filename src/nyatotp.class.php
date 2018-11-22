<?php
require_once '../vendor/phpgangsta/googleauthenticator/PHPGangsta/GoogleAuthenticator.php';
class nyatotp {
    private $ga;
    function __construct() {
        $this->ga = new PHPGangsta_GoogleAuthenticator();
    }
    /**
     * @description: 返回错误代码
     * @param Int code 错误代码，如果提供则返回代码及错误介绍
     */
    function http403($code=null) {
        header('HTTP/1.1 403 Forbidden');
        //出于安全考虑，非调试状态不输出错误原因
        die();
        if ($code) {
            global $nlcore;
            $json = $nlcore->msg->m($code);
            header('Content-Type:application/json;charset=utf-8');
            echo $json;
        }
        die();
    }
    /**
     * @description: 创建用户动态验证码
     * @param String appname 应用名称
     * @param String mail 用户邮箱
     * @param String username 用户名称
     * @return String otpauth URL
     */
    function newusertotp($appname,$mail,$username) {
        global $nlcore;
        $secret = $this->ga->createSecret();
        $otpauth = "otpauth://totp/".$appname.":".$mail."?secret=".$secret."&issuer=".$username;
        return $otpauth;
    }
    /**
     * @description: 创建设备动态验证码
     * @param String appname 已注册的应用名称
     * @param String<32> appsecret 应用密钥
     */
    function newdevicetotp($appname,$appsecret) {
        global $nlcore;
        //检查IP访问频率
        $result = $nlcore->safe->frequencylimitation();
        if ($result[0] >= 2000000) $this->http403($result[0]);
        //检查应用名称和密钥
        if (!$nlcore->safe->isNumberOrEnglishChar($appname,1,64) || !$nlcore->safe->isNumberOrEnglishChar($appsecret,32,32)) $this->http403(2020400);
        $datadic = [
            "app_id" => $appname,
            "app_secret" => $appsecret
        ];
        $result = $nlcore->db->scount($nlcore->cfg->db->tables["external_app"],$datadic);
        if ($result[0] >= 2000000 || $result[2][0][0] == 0) $this->http403(2020401);
        //检查 IP 是否被封禁
        $time = time(); // + 8 * 3600
        $stime = date("Y-m-d H:i:s", $time);
        $result = $nlcore->safe->chkip($time);
        if ($result[0] != 0) $this->http403($result[0]);
        $ipid = $result[1];
        //检查APP是否已经注册 $appname,$appsecret
        $appid = $nlcore->safe->chkappsecret($appname,$appsecret);
        if ($appid == null) $this->http403(2020500);
        //创建新的 totp secret
        $secret = $this->ga->createSecret();
        $numcode = $this->ga->getCode($secret);
        //检查 session_totp 表
        $datadic = [
            "secret" => $secret
        ];
        //如果 secret 已存在则删除
        $result = $nlcore->db->delete($nlcore->cfg->db->tables["session_totp"],$datadic);
        if ($result[0] >= 2000000) $this->http403(2020405);
        //写入 session_totp 表
        $datadic = array(
            "secret" => $secret,
            "ipid" => $ipid,
            "appid" => $appid,
            "time" => $stime
        );
        $result = $nlcore->db->insert($nlcore->cfg->db->tables["session_totp"],$datadic);
        if ($result[0] >= 2000000) $this->http403(2020406);
        header('Content-Type:application/json;charset=utf-8');
        echo json_encode(array(
            "code" => 1000000,
            "totp_secret" => $secret,
            "totp_code" => intval($numcode),
            "time" => intval($time),
            "appname" => $appname
        ));
    }
    /**
     * @description: 验证设备动态验证码
     * @param String<32> appsecret 应用密钥（不是 TOTP 密钥）
     * @param UInt<6> numcode 动态口令
     * @param UInt clocktolerance 允许的时钟差异（值 x 30秒）
     */
    function verificationtotp($secret,$numcode,$clocktolerance=2) {
        $garesult = $this->ga->verifyCode($secret,$numcode,$clocktolerance);
        if ($garesult) return true;
        return false;
    }
    function __destruct() {
        $this->ga = null;
        unset($this->ga);
    }
}
?>