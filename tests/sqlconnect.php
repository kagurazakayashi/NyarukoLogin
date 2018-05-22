<?php
require_once "../src/nyacore.class.php";
header('Content-Type: text/plain; charset=utf-8');
echo "【数据库连接测试】\n";
echo "引入文件 nyaconnect.class.php ... ";
require_once "../src/nyaconnect.class.php";
echo "完成。\n";
echo "初始化 nyadbconnect ... ";
$nyadbconnect = new nyadbconnect();
echo "完成。\n";
echo "执行 sqltest() ... \n";
echo "数据库版本: ".$nya->db->sqltest();
echo "\n完成。\n";
?>