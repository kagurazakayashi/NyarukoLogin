<?php
class nyatotp {
    private $ga;
    function __construct() {
        $this->ga = new PHPGangsta_GoogleAuthenticator();
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
     * @param Int timestamp 时间戳
     */
    function newdevicetotp($appname,$appsecret,$timestamp=null) {
        global $nlcore;
        //检查IP访问频率
        $result = $nlcore->safe->frequencylimitation("getlinktotp");
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg($result[0]);
        //检查 IP 是否被封禁
        $time = $nlcore->safe->getdatetime();
        $stime = $time[1];
        $time = $time[0];
        $result = $nlcore->safe->chkip($time);
        if ($result[0] != 0) $nlcore->msg->stopmsg($result[0]);
        $ipid = $result[1];
        //检查应用名称和密钥
        if (!$nlcore->safe->isNumberOrEnglishChar($appname,1,64) || !$nlcore->safe->isNumberOrEnglishChar($appsecret,32,32)) $nlcore->msg->stopmsg(2020400);
        $datadic = [
            "app_id" => $appname,
            "app_secret" => $appsecret
        ];
        $result = $nlcore->db->scount($nlcore->cfg->db->tables["app"],$datadic);
        if ($result[0] >= 2000000 || $result[2][0][0] == 0) $nlcore->msg->stopmsg(2020401);
        //检查APP是否已经注册 $appname,$appsecret
        $appid = $nlcore->safe->chkappsecret($appname,$appsecret);
        if ($appid == null) $nlcore->msg->stopmsg(2020401);
        //检查客户端提供的时间差异，没有问题则用客户端时间
        $timeSlice = null;
        if ($timestamp) {
            $nlcore->safe->timestampdiff($time,$timestamp);
            $timeSlice = floor($timestamp / 30);
        }
        //创建新的 totp secret
        $secret = $this->ga->createSecret(64);
        $numcode = $this->ga->getCode($secret,$timeSlice);
        //创建 apptoken
        $apptoken = $nlcore->safe->randhash($secret,true);
        //检查 session_totp 表
        $datadic = [
            "secret" => $secret,
            "apptoken" => $apptoken
        ];
        //如果 secret 或者 apptoken 已存在则删除
        $result = $nlcore->db->delete($nlcore->cfg->db->tables["totp"],$datadic,"","OR");
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2020405);
        //写入 session_totp 表
        $datadic = array(
            "secret" => $secret,
            "apptoken" => $apptoken,
            "ipid" => $ipid,
            "appid" => $appid,
            "time" => $stime
        );
        $result = $nlcore->db->insert($nlcore->cfg->db->tables["totp"],$datadic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2020406);
        header('Content-Type:application/json;charset=utf-8');
        echo json_encode(array(
            "code" => 1000000,
            "totp_secret" => $secret,
            "totp_code" => intval($numcode),
            "totp_token" => $apptoken,
            "time" => $stime,
            "timestamp" => $time,
            "timezone" => date_default_timezone_get(),
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
    //验证加密信息
    function encrypttest() {
        global $nlcore;
        $argvarr = $nlcore->safe->decryptargv("encrypttest");
        $dataarray = $argvarr[0];
        $secret = $argvarr[1];
        $nlcore->safe->encryptargv($dataarray,$secret);
    }
    function __destruct() {
        $this->ga = null;
        unset($this->ga);
    }
}
?>
