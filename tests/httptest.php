<?php
$argv = count($_POST) > 0 ? $_POST : $_GET;
$argv["status"] = "OK";
if (isset($_SERVER['REQUEST_METHOD'])) $argv["method"] = $_SERVER['REQUEST_METHOD'];
if (isset($_SERVER['REMOTE_ADDR'])) $argv["ip"] = $_SERVER['REMOTE_ADDR'];
if (isset($_SERVER['SERVER_PORT'])) $argv["port"] = $_SERVER['SERVER_PORT'];
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $argv["proxy"] = $_SERVER['HTTP_X_FORWARDED_FOR'];
if (isset($_SERVER['SERVER_PROTOCOL'])) $argv["protocol"] = $_SERVER['SERVER_PROTOCOL'];
$argv["timezone"] = date_default_timezone_get();
$timestamp = time();
$argv["time"] = date('Y-m-d H:i:s', $timestamp);
$argv["timestamp"] = $timestamp;
if (isset($_SERVER['HTTP_USER_AGENT'])) $argv["ua"] = $_SERVER['HTTP_USER_AGENT'];
// header('Content-Type:application/json;charset=utf-8');
echo json_encode($argv);
?>
