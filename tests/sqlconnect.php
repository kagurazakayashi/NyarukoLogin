<?php
require_once "../src/nyacore.class.php";
require_once "../src/nyaconnect.class.php";

class sqltest {
    function starttest() {
        global $nlcore;
        $nlcore->db->initReadDbs();
        $nlcore->db->initWriteDbs();
        $nlcore->db->sqltest();
        $nlcore->msg->stopmsg(1010000,null,$nlcore->db->sqltest());
    }
}

$sqltestobj = new sqltest();
$sqltestobj->starttest();
?>
