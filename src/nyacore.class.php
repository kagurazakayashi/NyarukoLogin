<?php
declare(strict_types=1);
// USE: require_once "nyacore.class.php";
if (!isset($nyacore403off) && count($_POST) == 0 && count($_GET) == 0) die(header("HTTP/1.1 403 Forbidden"));
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

/**
 * 核心啟動類
 *
 * 負責初始化並管理所有核心模組（設定、訊息、資料庫、安全、工具函式、會話），
 * 並在啟動時自動套用系統設定。
 *
 * @package NyarukoLogin
 */
class nyacore {
    /** @var nyasetting 系統設定物件 */
    public $cfg;
    /** @var nyainfomsg 訊息代碼字典 */
    public $msg;
    /** @var nyadbconnect 資料庫連線與查詢物件 */
    public $db;
    /** @var nyasafe 安全與加密工具類 */
    public $safe;
    /** @var nyafunc 通用工具函式類 */
    public $func;
    /** @var nyasession 使用者會話管理類 */
    public $sess;

    /**
     * 建構子
     *
     * 初始化所有核心模組實例。
     */
    function __construct() {
        $this->cfg = new nyasetting();
        $this->msg = new nyainfomsg();
        $this->db = new nyadbconnect();
        $this->safe = new nyasafe();
        $this->func = new nyafunc();
        $this->sess = new nyasession();
    }

    /**
     * 套用系統設定
     *
     * 根據設定檔中的除錯模式與時區設定，套用相關 PHP 組態。
     *
     * @return void
     */
    function applyconfig(): void {
        if ($this->cfg->app->debug == 1) {
            error_reporting(E_ALL);
            ini_set("display_errors", "On");
        } else if ($this->cfg->app->debug == 0) {
            ini_set("display_errors", "Off");
        }
        if ($this->cfg->app->timezone != "") date_default_timezone_set($this->cfg->app->timezone);
    }

    /**
     * 解構子
     *
     * 釋放所有核心模組資源。
     *
     * @return void
     */
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
