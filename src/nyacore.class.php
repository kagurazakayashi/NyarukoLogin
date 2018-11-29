<?php
// USE: require_once "nyacore.class.php";
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
require_once $phpfiledir."../nyaconfig.class.php";
require_once $phpfiledir."nyainfomsg.class.php";
require_once $phpfiledir."nyaconnect.class.php";
require_once $phpfiledir."nyasafe.class.php";
require_once $phpfiledir.'../vendor/phpgangsta/googleauthenticator/PHPGangsta/GoogleAuthenticator.php';
require_once $phpfiledir.'../vendor/xxtea/xxtea/xxtea.php';
class nyacore {
    public $cfg; //设置
    public $msg; //信息
    public $db; //数据库操作
    public $safe; //安全类
    function __construct() {
        $this->cfg = new nyasetting();
        $this->msg = new nyainfomsg();
        $this->db = new nyadbconnect();
        $this->safe = new nyasafe();
        if ($this->cfg->app->debug == 1) {
            error_reporting(E_ALL);
            ini_set("display_errors", "On");
        } else if ($this->cfg->app->debug == 0) {
            ini_set("display_errors", "Off");
        }
    }
    function __destruct() {
        $this->cfg = null;
        unset($this->cfg);
        $this->msg = null; unset($this->msg);
        $this->db = null; unset($this->db);
        $this->safe = null; unset($this->safe);
    }
}
global $nlcore;
if (!isset($nlcore)) $nlcore = new nyacore();
?>