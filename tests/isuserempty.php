<?
header('Content-Type: text/plain; charset=utf-8');
echo "【用户数量】\n";
echo "引入文件 nyaadduser.class.php ... ";
require_once "../src/nyaadduser.class.php";
echo "完成。\n";
echo "初始化 adduser ... ";
$adduser = new adduser(0);
echo "完成。\n";
echo "执行 isuserempty() ... \n";
echo "结果: ".$adduser->isuserempty();
echo "\n完成。\n";
?>