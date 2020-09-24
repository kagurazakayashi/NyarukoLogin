<?
$argv = count($_POST) > 0 ? $_POST : $_GET;
$argv = json_encode($argv);
$f = fopen("clientinfo.json", "w");
fwrite($f, $argv);
$f.close();
?>