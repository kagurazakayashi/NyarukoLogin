<?php
// 傳送一封測試郵件到 $nlcore->cfg->verify->debugmail
// 如果傳送了 GET / POST 內容，會將收到的資訊以 JSON 填入郵件中
require_once "../src/nyacore.class.php";
require_once "../src/nyavcode.class.php";
global $nlcore;
$nyavcode = new nyavcode();
$returnClientData = [];
$argv = count($_POST) > 0 ? $_POST : $_GET;
if (count($argv) > 0) {
    $returnClientData = $nyavcode->sendtestmailtxt(json_encode($argv));
} else {
    $returnClientData = $nyavcode->sendtestmailhtm();
}
echo json_encode($returnClientData);
