<?php
// USE: require_once "nyacore.class.php";
if (count($_POST) == 0 && count($_GET) == 0) die(header("HTTP/1.1 403 Forbidden"));
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
require_once $phpfiledir."..".DIRECTORY_SEPARATOR."vendor".DIRECTORY_SEPARATOR."autoload.php";
require_once $phpfiledir."..".DIRECTORY_SEPARATOR."nyaconfig.class.php";
require_once $phpfiledir."nyainfomsg.class.php";
require_once $phpfiledir."nyasafe.class.php";
require_once $phpfiledir."nyaconnect.class.php";
require_once $phpfiledir."nyafunc.class.php";
require_once $phpfiledir."nyasession.class.php";
require_once $phpfiledir."md6.class.php";
// die(json_encode(count($_POST) > 0 ? $_POST : $_GET));
class nyacore {
    public $cfg; //设置
    public $msg; //信息
    public $db; //数据库操作
    public $safe; //安全类
    public $func; //各种通用函数
    public $sess; //用户会话
    function __construct() {
        $this->cfg = new nyasetting();
        $this->msg = new nyainfomsg();
        $this->db = new nyadbconnect();
        $this->safe = new nyasafe();
        $this->func = new nyafunc();
        $this->sess = new nyasession();
    }
    function applyconfig() {
        if ($this->cfg->app->debug == 1) {
            error_reporting(E_ALL);
            ini_set("display_errors", "On");
        } else if ($this->cfg->app->debug == 0) {
            ini_set("display_errors", "Off");
        }
        if ($this->cfg->app->timezone != "") date_default_timezone_set($this->cfg->app->timezone);
    }
    function __destruct() {
        $this->cfg = null; unset($this->cfg);
        $this->msg = null; unset($this->msg);
        $this->db = null; unset($this->db);
        $this->safe = null; unset($this->safe);
        $this->func = null; unset($this->func);
        $this->sess = null; unset($this->sess);
    }
}
global $nlcore;
if (!isset($nlcore)) $nlcore = new nyacore();
if (!isset($nlcore)) die("内核初始化失败");
$nlcore->applyconfig();
?>
