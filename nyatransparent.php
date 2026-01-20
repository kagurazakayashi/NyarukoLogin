<?php
declare(strict_types=1);
/**
 * PNG 純色透明圖片產生工具 - 根據指定的 RGBA 參數產生單色 PNG 圖片。
 * @package NyarukoLogin
 * @author KagurazakaYashi
 * @license MIT
 */
$r = isset($_GET["r"]) ? (int) $_GET["r"] : 0; //0-255
$g = isset($_GET["g"]) ? (int) $_GET["g"] : 0; //0-255
$b = isset($_GET["b"]) ? (int) $_GET["b"] : 0; //0-255
$a = isset($_GET["a"]) ? (int) $_GET["a"] : 0; //0-127
$w = isset($_GET["w"]) ? (int) $_GET["w"] : 100;
$h = isset($_GET["h"]) ? (int) $_GET["h"] : 100;
if (
    $r < 0 || $r > 255 ||
    $g < 0 || $g > 255 ||
    $b < 0 || $b > 255 ||
    $a < 0 || $a > 127 ||
    $w < 1 || $w > 10000 ||
    $h < 1 || $h > 10000
) {
    http_response_code(400);
    exit;
}
$block = imagecreatetruecolor($w, $h);
$bg = imagecolorallocatealpha($block, $r, $g, $b, $a);
if ($bg === false) {
    http_response_code(400);
    exit;
}
imagealphablending($block, false);
imagefill($block, 0, 0, $bg);
imagesavealpha($block, true);
header("Content-Type: image/png");
imagepng($block);
imagedestroy($block);
