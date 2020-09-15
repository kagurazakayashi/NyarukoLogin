<?php
// 此檔案已棄用
// 此加密方式已經棄用
// TODO:兩步驗證時進行重寫 
require_once "src/nyacore.class.php";
require_once 'src/nyatotp.class.php';
$nyatotp = new nyatotp();
$nyatotp->newdevicetotp();
?>
