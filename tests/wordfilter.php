<?php
require_once "../src/nyacore.class.php";
$argv = count($_POST) > 0 ? $_POST : $_GET;
global $nlcore;
if (isset($argv["w"])) {
    $nlcore->safe->wordfilter($argv["w"]);
    $returnarr = $nlcore->msg->m(0,1000000);
    echo $nlcore->sess->encryptargv($returnarr);
} else {
    $nlcore->msg->stopmsg(2000101);
}
?>
