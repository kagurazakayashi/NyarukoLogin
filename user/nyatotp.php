<?php
require_once "src/nyacore.class.php";
require_once 'src/nyatotp.class.php';
$nyatotp = new nyatotp();
$nyatotp->newdevicetotp();
?>
