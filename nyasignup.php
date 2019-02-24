<?php
require_once "src/nyacore.class.php";
require_once "src/nyasignup.class.php";
$argv = count($_POST) > 0 ? $_POST : $_GET;
if (isset($argv["j"])) {
    $nyasignup = new nyasignup();
    $nyasignup->adduser();
}
?>