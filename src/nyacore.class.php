<?php
// USE: require_once "nyacore.class.php";
require_once "../nyaconfig.class.php";
require_once "nyainfomsg.class.php";
require_once "nyaconnect.class.php";
class nyacore {
    var $cfg; //设置
    var $msg; //信息
    var $db; //数据库操作
    function __construct() {
        $this->cfg = new nyasetting();
        $this->msg = new nyainfomsg();
        $this->db = new nyadbconnect();
    }
}
global $nya;
if (!isset($nya)) $nya = new nyacore();
?>