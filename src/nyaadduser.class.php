<?php
require_once "nyacore.class.php";
class adduser {
    var $nyadbconnect;
    function __construct($moduser) {
        //检查是否已有用户
    }
    function isuserempty() {
        global $nlcore;
        $sdata = $nlcore->db->scount($nlcore->cfg->db->tb_user);
        print_r($sdata);
    }
}
?>