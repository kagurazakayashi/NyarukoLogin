<?
$argv = count($_POST) > 0 ? $_POST : $_GET;
$argv = json_encode($argv);
$myfile = fopen("clientinfo.json", "w")
fwrite($myfile, $argv);
?>