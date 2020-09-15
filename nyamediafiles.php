<?php
// 获取某个媒体文件路径
require_once "src/nyacore.class.php";
require_once "src/nyamediafiles.class.php";
// IP 檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("default");
// 實現功能
$mediafiles = new nyamediafiles();
$mediafiles->mediafiles($nlcore->sess->argReceived);
// 將資訊返回給客戶端
$returnClientData = $nlcore->sess->encryptargv($returnClientData);
exit($returnClientData);
?>