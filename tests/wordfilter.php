<?php
require_once "../src/nyacore.class.php";
$argv = count($_POST) > 0 ? $_POST : $_GET;
global $nlcore;
if (isset($argv["w"])) {
    $words = $nlcore->safe->wordfilter($argv["w"],false);
    $returnarr = $nlcore->msg->m(0,1000000);
    if ($words[0]) $returnarr = $nlcore->msg->m(0,2020300);
    $returnarr = array_merge($returnarr,$words);
    echo $nlcore->sess->encryptargv($returnarr);
} else {
    $nlcore->msg->stopmsg(2000101);
}
?>
