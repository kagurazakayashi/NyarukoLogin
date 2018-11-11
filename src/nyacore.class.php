<?php
// USE: require_once "nyacore.class.php";
require_once "../nyaconfig.class.php";
require_once "nyainfomsg.class.php";
require_once "nyaconnect.class.php";
require_once "nyasafe.class.php";
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
    }
    function __destruct() {
        $this->cfg = null;
        unset($this->cfg);
        $this->msg = null; unset($this->msg);
        $this->db = null; unset($this->db);
        $this->safe = null; unset($this->safe);
    }
}
global $nya;
if (!isset($nya)) $nya = new nyacore();
?>