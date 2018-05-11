<?php
require_once "../nyaconnect.class.php";
class adduser {
    var $nyaconnect;
    function __construct() {
        $this->nyaconnect = new nyaconnect();
    }
}
?>