<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>YashiUser-Debug</title>
<link href="css/YashiUser-UI.css" rel="stylesheet" type="text/css">
<!--<script src="js/require.js" data-main="js/YashiUser-Registration.js"></script>-->
<style>.indexbtn{line-height:43px;height:43px;width:300px;color:#0008ff;background-color:#ededed;font-size:16px;font-weight:normal;font-family:Arial;background:-webkit-gradient(linear, left top, left bottom, color-start(0.05, #fbd0fa), color-stop(1, #ff91fc));background:-moz-linear-gradient(top, #fbd0fa 5%, #ff91fc 100%);background:-o-linear-gradient(top, #fbd0fa 5%, #ff91fc 100%);background:-ms-linear-gradient(top, #fbd0fa 5%, #ff91fc 100%);background:linear-gradient(to bottom, #fbd0fa 5%, #ff91fc 100%);background:-webkit-linear-gradient(top, #fbd0fa 5%, #ff91fc 100%);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fbd0fa', endColorstr='#ff91fc',GradientType=0);border:1px solid #e9afff;-webkit-border-top-left-radius:5px;-moz-border-radius-topleft:5px;border-top-left-radius:5px;-webkit-border-top-right-radius:5px;-moz-border-radius-topright:5px;border-top-right-radius:5px;-webkit-border-bottom-left-radius:5px;-moz-border-radius-bottomleft:5px;border-bottom-left-radius:5px;-webkit-border-bottom-right-radius:5px;-moz-border-radius-bottomright:5px;border-bottom-right-radius:5px;-moz-box-shadow:3px 4px 0 0 #fce6ff;-webkit-box-shadow:3px 4px 0 0 #fce6ff;box-shadow:3px 4px 0 0 #fce6ff;text-align:center;display:inline-block;text-decoration:none}.indexbtn:hover{background-color:#f5f5f5;background:-webkit-gradient(linear, left top, left bottom, color-start(0.05, #ff91fc), color-stop(1, #fbd0fa));background:-moz-linear-gradient(top, #ff91fc 5%, #fbd0fa 100%);background:-o-linear-gradient(top, #ff91fc 5%, #fbd0fa 100%);background:-ms-linear-gradient(top, #ff91fc 5%, #fbd0fa 100%);background:linear-gradient(to bottom, #ff91fc 5%, #fbd0fa 100%);background:-webkit-linear-gradient(top, #ff91fc 5%, #fbd0fa 100%);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff91fc', endColorstr='#fbd0fa',GradientType=0)}</style>
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
            echo '<p><a href="'.$nowfile.'" target="_blank" class="indexbtn">'.$nowfile.'</a></p>';
        }
    }
?>
<hr><p><a href="https://github.com/cxchope/YashiLogin" target="_blank" class="indexbtn">Github</a></p>
<p>© Kagurazaka Yashi</p></center>
</body>
</html>