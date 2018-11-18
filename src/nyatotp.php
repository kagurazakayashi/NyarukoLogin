<?php
require_once "nyacore.class.php";
require_once 'nyatotp.class.php';
$nyatotp = new nyatotp();
$nyatotp->newdevicetotp($_GET["n"],$_GET["s"]);
?>