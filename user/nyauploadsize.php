<?php
define("SIZE_GB",1073741824);
define("SIZE_MB",1048576);
define("SIZE_KB",1024);
define("SIZE_B",1);
$argv = count($_POST) > 0 ? $_POST : $_GET;

function hlsize2bsize($size = 0) {
    if (!$size) return 0;
    $scan = ["g" => SIZE_GB, "gb" => SIZE_GB, "m" => SIZE_MB, "mb" => SIZE_MB, "k" => SIZE_KB, "kb" => SIZE_KB, "b" => SIZE_B];
    foreach ($scan as $unit => $factor) if (strlen($size) > strlen($unit) && strtolower(substr($size, strlen($size) - strlen($unit))) == $unit) return substr($size, 0, strlen($size) - strlen($unit)) * $factor;
    return $size;
}

function bsize2hlsize($size = 0) {
    if (!$size) return 0;
    if ($size < SIZE_KB) return $size." B";
    else if ($size < SIZE_MB) return round($size / SIZE_KB, 2)." KB";
    else if ($size < SIZE_GB) return round($size / SIZE_MB, 2)." MB";
    else return round($size / SIZE_GB, 2)." GB";
}

$upload_max_filesize = hlsize2bsize(ini_get('upload_max_filesize'));
$post_max_size = hlsize2bsize(ini_get('post_max_size'));
$min_upload_size = $upload_max_filesize > $post_max_size ? $post_max_size : $upload_max_filesize;

//可以使用 m 参数指定以下其中之一 key
$returnarray = [
    "upload_max_filesize" => $upload_max_filesize,
    "upload_max_filesize_hl" => bsize2hlsize($upload_max_filesize),
    "post_max_size" => $post_max_size,
    "post_max_size_hl" => bsize2hlsize($post_max_size),
    "max_upload_size" => $min_upload_size,
    "max_upload_size_hl" => bsize2hlsize($min_upload_size)
];
if (isset($argv["m"])) {
    if (!isset($returnarray[$argv["m"]])) die();
    header('Content-Type:text/plain;charset=utf-8');
    echo $returnarray[$argv["m"]];
} else {
    header('Content-Type:application/json;charset=utf-8');
    echo json_encode($returnarray);
}
?>
