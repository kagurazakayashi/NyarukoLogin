<?php
/**
 * @description: PNG单色色彩图生成工具
 * @param Int r 红色，取值范围 0 - 255（默认 0）
 * @param Int g 绿色，取值范围 0 - 255（默认 0）
 * @param Int b 蓝色，取值范围 0 - 255（默认 0）
 * @param Int a 透明度，取值范围 0 - 127（默认 0）
 * @param Int w 生成图像宽度，取值范围 1 - 10000（默认 100）
 * @param Int h 生成图像高度，取值范围 1 - 10000（默认 100）
 * @return Image PNG单色色彩图
 */
$r = isset($_GET["r"]) ? $_GET["r"] : 0; //0-255
$g = isset($_GET["g"]) ? $_GET["g"] : 0; //0-255
$b = isset($_GET["b"]) ? $_GET["b"] : 0; //0-255
$a = isset($_GET["a"]) ? $_GET["a"] : 0; //0-127
$w = isset($_GET["w"]) ? $_GET["w"] : 100;
$h = isset($_GET["h"]) ? $_GET["h"] : 100;
if (
    $r < 0 || $r > 255 ||
    $g < 0 || $g > 255 ||
    $b < 0 || $b > 255 ||
    $a < 0 || $a > 127 ||
    $w < 1 || $w > 10000 ||
    $h < 1 || $h > 10000
) {
    header('HTTP/1.0 400 Bad Request'); die();
}
$block = imagecreatetruecolor($w,$h);
$bg = imagecolorallocatealpha($block, $r, $g, $b, $a);
if (!$bg) {
    header('HTTP/1.0 400 Bad Request'); die();
}
imagealphablending($block , false);
imagefill($block , 0 , 0 , $bg);
imagesavealpha($block , true);
header("content-type:image/png");
imagepng($block);
imagedestroy($block);
?>
