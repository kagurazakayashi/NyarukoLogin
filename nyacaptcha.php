<?php
require_once "src/nyacore.class.php";
require_once "src/nyacaptcha.class.php";
$argv = count($_POST) > 0 ? $_POST : $_GET;
if (isset($argv["j"])) {
    $nyacaptcha = new nyacaptcha();
    $nyacaptcha->getcaptcha();
}
$nyacaptcha = new nyacaptcha();
$nyacaptcha->verifycaptcha("00d0d6ff9d3f0cea5ca869e09f493e25","nre9j2");
?>