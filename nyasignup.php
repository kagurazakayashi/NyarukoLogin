<?php
require_once "src/nyacore.class.php";
require_once "src/nyasignup.class.php";
// IP檢查和解密客戶端提交的資訊
$inputInformation = $nlcore->sess->decryptargv("signup");
// 初始化類別
$nyasignup = new nyasignup();
// 獲取執行結果
$returnArray = $nyasignup->adduser($nlcore, $inputInformation);
// 測試密碼生成器用，不用時遮蔽
// if (isset($_GET["passwordhashtest"])) {
//     $nyasignup->passwordhashtest($_GET["p"],$_GET["t"]);
// }
// 將執行結果 JSON 返回到客戶端
echo $nlcore->sess->encryptargv($returnArray);
?>