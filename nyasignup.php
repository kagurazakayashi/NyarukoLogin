<?php
require_once "src/nyacore.class.php";
require_once "src/nyasignup.class.php";
$nyasignup = new nyasignup();
if (isset($_GET["passwordhashtest"])) {
    header('HTTP/1.1 403 Forbidden');
    // $nyasignup->passwordhashtest($_GET["p"],$_GET["t"]); //测试用，不用时屏蔽
} else {
    $nyasignup->adduser();
}
?>
