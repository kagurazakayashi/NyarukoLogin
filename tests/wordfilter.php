<?php
require_once "../src/nyacore.class.php";
$argv = count($_POST) > 0 ? $_POST : $_GET;
if (isset($argv["w"])) {
    global $nlcore;
    $words = $nlcore->safe->wordfilter($argv["w"]);
    if ($words) {
        echo "有敏感词！";
    } else {
        echo "没有敏感词";
    }
}
?>