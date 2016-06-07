<?php
die("disable");
require 'sendmail.php';
require 'YaloginSQLSetting.php';
echo "开始执行测试……";
$sendmail = new Sendmail();
$sendmail->init();
echo $sendmail->sendtestmail("");
?>