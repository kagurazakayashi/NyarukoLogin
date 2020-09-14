<?php
require_once "src/nyacore.class.php";
require_once "src/nyastand.class.php";
// IP檢查和解密客戶端提交的資訊
$inputInformation = $nlcore->sess->decryptargv("signup");
// 檢查用戶是否登入
$sessionInformation = $nlcore->safe->userLogged($inputInformation);
// 初始化類別
$stand = new stand();
// 獲取執行結果
$returnArray = $stand->addstand($nlcore, $inputInformation, $sessionInformation);
// 將執行結果 JSON 返回到客戶端
echo $nlcore->sess->encryptargv($returnArray);
?>