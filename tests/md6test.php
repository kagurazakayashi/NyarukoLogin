<?php
$phpfiledir = pathinfo(__FILE__)["dirname"] . DIRECTORY_SEPARATOR;
require_once $phpfiledir . ".." . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "md6.class.php";
$md6 = new md6hash;
$data = $_GET['d'] ?? die();
$size = $_GET['s'] ? intval($_GET['s']) : 256;
$key = $_GET['k'] ?? '';
$levels = $_GET['l'] ? intval($_GET['l']) : 64;
$result = $md6->hex($data, $size, $key, $levels);
header('Content-Type:text/plain');
exit($result);
