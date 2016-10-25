<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>YashiUser-Debug</title>
<link href="css/YashiUser-UI.css" rel="stylesheet" type="text/css">
<!--<script src="js/require.js" data-main="js/YashiUser-Registration.js"></script>-->
</head>
<body>
<center><h2>雅诗通用用户登录后台测试页面</h2>
「YashiUser-」开头页面和此页面为测试页面，<br>请删除并加以自己的页面，此页只显示这些页面。<hr>
<?php
    $dir = dirname(__FILE__);
    $files = scandir($dir);
    //$testfiles = array();
    foreach ($files as $nowfile){
        if(strstr($nowfile,"YashiUser-")) {
            //array_push($testfiles,$nowfile);
            echo '<p><a href="'.$nowfile.'" target="_blank" class="mainbtn">'.$nowfile.'</a></p>';
        }
    }
?>
<hr><p><a href="https://github.com/cxchope/YashiLogin" target="_blank" class="mainbtn">Github</a></p>
<p>© Kagurazaka Yashi</p></center>
</body>
</html>