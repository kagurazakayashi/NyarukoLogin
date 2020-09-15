<?php
require_once "src/nyacore.class.php";
require_once "src/nyauploadfile.class.php";
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("upload");
// 實現功能
$uploadfile = new nyauploadfile();
$returnClientData = $uploadfile->getuploadfile($nlcore->sess->argReceived);
// 將資訊返回給客戶端
$returnClientData = $nlcore->sess->encryptargv($returnClientData);
exit($returnClientData);
?>