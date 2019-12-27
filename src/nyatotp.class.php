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
     * @param String<32> appsecret 应用密钥
     * @param Int timestamp 时间戳
     */
    function newdevicetotp() {
        global $nlcore;
        $argv = count($_POST) > 0 ? $_POST : $_GET;
        if (!isset($argv["appsecret"])) $nlcore->msg->stopmsg(2000101,$totpsecret);
        $appsecret = $argv["appsecret"];
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
        if (!$nlcore->safe->isNumberOrEnglishChar($appsecret,64,64)) $nlcore->msg->stopmsg(2020409);
        $datadic = [
            "secret" => $appsecret
        ];
        $result = $nlcore->db->scount($nlcore->cfg->db->tables["app"],$datadic);
        if ($result[0] >= 2000000 || $result[2][0]["count(*)"] == 0) $nlcore->msg->stopmsg(2020401);
        //检查APP是否已经注册 $appsecret
        $appid = $nlcore->safe->chkappsecret($appsecret);
        if ($appid == null) $nlcore->msg->stopmsg(2020401);
        //检查客户端提供的时间差异，没有问题则用客户端时间
        $timeSlice = null;
        if (isset($argv["timestamp"])) {
            $ltimestamp = $argv["timestamp"];
            $nlcore->safe->timestampdiff($time,$ltimestamp);
            $timeSlice = floor($ltimestamp / 30);
        }
        //创建新的 totp secret
        $secret = $this->ga->createSecret(64);
        $numcode = $this->ga->getCode($secret,$timeSlice);
        //创建 apptoken
        $apptoken = $nlcore->safe->randhash($secret);
        //检查 session_totp 表
        $datadic = [
            "secret" => $secret,
            "apptoken" => $apptoken
        ];
        //如果 secret 或者 apptoken 已存在则删除
        $result = $nlcore->db->delete($nlcore->cfg->db->tables["totp"],$datadic,"","OR");
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2020405);
        //写入 device 表
        $datadic = array();
        $typeenum = ['phone','phone_emu','pad','pad_emu','pc','web','debug','other'];
        $datadic["type"] = isset($argv["devtype"]) ? strtolower($nlcore->safe->retainletternumber($argv["devtype"])) : null;
        if ($datadic["type"] && !in_array($datadic["type"],$typeenum)) $nlcore->msg->stopmsg(2000104);
        $osenum = ['ios','android','windows','linux','harmony','emu','other'];
        $datadic["os"] = isset($argv["devos"]) ? strtolower($nlcore->safe->retainletternumber($argv["devos"])) : null;
        if ($datadic["os"] && !in_array($datadic["os"],$osenum)) $nlcore->msg->stopmsg(2000104);
        $datadic["device"] = isset($argv["devdevice"]) ? $nlcore->safe->retainletternumber($argv["devdevice"]) : null;
        $datadic["osver"] = isset($argv["devosver"]) ? $nlcore->safe->retainletternumber($argv["devosver"]) : null;
        $datadic["info"] = isset($argv["devinfo"]) ? $nlcore->safe->retainletternumber($argv["devinfo"]) : null;
        $deviceid = null;
        if (!$nlcore->safe->allnull($datadic)) {
            //检查条目是否存在
            $result = $nlcore->db->select(["id"],$nlcore->cfg->db->tables["device"],$datadic);
            if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2020416);
            if (isset($result[2])) {
                $resultarr = $result[2];
                if (count($resultarr) > 0 && isset($resultarr[0]["id"])) $deviceid = $resultarr[0]["id"];
            }
        }
        if (!$deviceid) {
            //如果不存在
            $result = $nlcore->db->insert($nlcore->cfg->db->tables["device"],$datadic);
            if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2020416);
            $deviceid = $result[1];
        }
        //写入 session_totp 表
        $datadic = array(
            "secret" => $secret,
            "apptoken" => $apptoken,
            "ipid" => $ipid,
            "appid" => $appid,
            "devid" => $deviceid,
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
            "timezone" => date_default_timezone_get()
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
