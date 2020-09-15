<?php
// 使用者資料修改
require_once "src/nyacore.class.php";
require_once "src/nyauserinfoedit.class.php";
// IP檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("signup");
// 檢查用戶是否登入
$nlcore->sess->userLogged();
// 實現功能
$userinfoedit = new userInfoEdit($nlcore->sess->argReceived,$nlcore->sess->userHash);
// 批量檢查並加入更新計劃
$userinfoedit->batchUpdate();
// 執行資料庫更新
$updated = $userinfoedit->sqlc();
// 將執行結果 JSON 返回到客戶端
$returnArray = $nlcore->msg->m(0,1000000);
$returnArray["updated"] = implode(",", $updated);
exit($nlcore->sess->encryptargv($returnArray));
?>