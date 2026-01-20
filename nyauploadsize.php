<?php
declare(strict_types=1);
/**
 * 上傳大小查詢工具 - 查詢伺服器的檔案上傳大小限制，回傳原始位元組與人類可讀格式。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
define("SIZE_GB", 1073741824);
define("SIZE_MB", 1048576);
define("SIZE_KB", 1024);
define("SIZE_B", 1);

/** @var array<string, string> $argv */
$argv = count($_POST) > 0 ? $_POST : $_GET;

function hlsize2bsize(string|int $size = 0): int
{
    if ($size === 0 || $size === '0') {
        return 0;
    }
    $scan = [
        "g" => SIZE_GB,
        "gb" => SIZE_GB,
        "m" => SIZE_MB,
        "mb" => SIZE_MB,
        "k" => SIZE_KB,
        "kb" => SIZE_KB,
        "b" => SIZE_B,
    ];
    foreach ($scan as $unit => $factor) {
        if (strlen((string) $size) > strlen($unit) && strtolower(substr((string) $size, strlen((string) $size) - strlen($unit))) === $unit) {
            return (int) (substr((string) $size, 0, strlen((string) $size) - strlen($unit))) * $factor;
        }
    }
    return (int) $size;
}

function bsize2hlsize(int $size = 0): string
{
    if ($size === 0) {
        return '0';
    }
    if ($size < SIZE_KB) {
        return $size . " B";
    } elseif ($size < SIZE_MB) {
        return round($size / SIZE_KB, 2) . " KB";
    } elseif ($size < SIZE_GB) {
        return round($size / SIZE_MB, 2) . " MB";
    } else {
        return round($size / SIZE_GB, 2) . " GB";
    }
}

$upload_max_filesize = hlsize2bsize(ini_get('upload_max_filesize'));
$post_max_size = hlsize2bsize(ini_get('post_max_size'));
$min_upload_size = $upload_max_filesize > $post_max_size ? $post_max_size : $upload_max_filesize;

/** @var array<string, string|int|float> $returnarray */
$returnarray = [
    "upload_max_filesize" => $upload_max_filesize,
    "upload_max_filesize_hl" => bsize2hlsize($upload_max_filesize),
    "post_max_size" => $post_max_size,
    "post_max_size_hl" => bsize2hlsize($post_max_size),
    "max_upload_size" => $min_upload_size,
    "max_upload_size_hl" => bsize2hlsize($min_upload_size),
];
if (isset($argv["m"])) {
    if (!isset($returnarray[$argv["m"]])) {
        http_response_code(400);
        exit;
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo $returnarray[$argv["m"]];
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($returnarray);
}
