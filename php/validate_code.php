<?php
if(class_exists('YaloginSQLSetting') != true) {
    require 'yaloginSQLSetting.php';
}
class ValidateCode {
 private $charset = 'abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789';//随机因子
 private $code;//验证码
 private $codelen = 4;//验证码长度
 private $width = 130;//宽度
 private $height = 50;//高度
 private $img;//图形资源句柄
 private $font = '../font/validateimagefont.ttf';//指定的字体
 private $fontsize = 20;//指定字体大小
 private $bgchar = "poi";
 private $imageformat = "png";
 private $linedensity = 6;
 private $bgchardensity = 33;
 //构造方法初始化
 public function __construct() {
     $sqlset = new YaloginSQLSetting();
     $this->fontsize = $sqlset->vcode_fontsize;
     $this->bgchar = $sqlset->vcode_bgchar;
     $this->charset = $sqlset->vcode_charset;
     $this->codelen = $sqlset->vcode_codelen;
     $this->width = $sqlset->vcode_width;
     $this->height = $sqlset->vcode_height;
     $this->imageformat = $sqlset->vcode_imageformat;
     $this->font = $sqlset->vcode_font[array_rand($sqlset->vcode_font,1)];
     $this->linedensity = $sqlset->vcode_line_density;
     $this->bgchardensity = $sqlset->vcode_bgchar_density;
 }
 //生成随机码
 private function createCode() {
  $_len = strlen($this->charset)-1;
  for ($i=0;$i<$this->codelen;$i++) {
   $this->code .= $this->charset[mt_rand(0,$_len)];
  }
 }
 //生成背景
 private function createBg() {
  $this->img = imagecreatetruecolor($this->width, $this->height);
  $color = imagecolorallocate($this->img, mt_rand(157,255), mt_rand(157,255), mt_rand(157,255));
  imagefilledrectangle($this->img,0,$this->height,$this->width,0,$color);
 }
 //生成文字
 private function createFont() {
  $_x = $this->width / $this->codelen;
  for ($i=0;$i<$this->codelen;$i++) {
   $this->fontcolor = imagecolorallocate($this->img,mt_rand(0,156),mt_rand(0,156),mt_rand(0,156));
   imagettftext($this->img,$this->fontsize,mt_rand(-30,30),$_x*$i+mt_rand(1,5),$this->height / 1.4,$this->fontcolor,$this->font,$this->code[$i]);
  }
 }
 //生成线条、干扰字
 private function createLine() {
  //线条
  for ($i=0;$i<$this->linedensity;$i++) {
   $color = imagecolorallocate($this->img,mt_rand(0,156),mt_rand(0,156),mt_rand(0,156));
   imageline($this->img,mt_rand(0,$this->width),mt_rand(0,$this->height),mt_rand(0,$this->width),mt_rand(0,$this->height),$color);
  }
  //干扰字
  for ($i=0;$i<$this->bgchardensity;$i++) {
   $color = imagecolorallocate($this->img,mt_rand(200,255),mt_rand(200,255),mt_rand(200,255));
   imagestring($this->img,mt_rand(1,5),mt_rand(0,$this->width),mt_rand(0,$this->height),$this->bgchar,$color);
  }
 }
 //输出
 private function outPut() {
     header('Content-type:image/'.$this->imageformat);
     if ($this->imageformat == "png") {
         imagepng($this->img);
     } else if ($this->imageformat == "jpg") {
         imagejpeg($this->img);
     } else if ($this->imageformat == "gif") {
         imagegif($this->img);
     } else if ($this->imageformat == "wbmp") {
         imagewbmp($this->img);
     } else if ($this->imageformat == "xbm") {
         imagexbm($this->img);
     }
     imagedestroy($this->img);
 }
 //对外生成
 public function doimg() {
  $this->createBg();
  $this->createCode();
  $this->createLine();
  $this->createFont();
  $this->outPut();
 }
 //获取验证码
 public function getCode() {
  return strtolower($this->code);
 }
}
?>