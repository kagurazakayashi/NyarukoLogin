<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>YashiUser-Error</title>
<link href="YashiUser-Registration.css" rel="stylesheet" type="text/css">
<script type="text/ecmascript" src="md5.js"></script>
<script language="JavaScript" src="YashiUser-Registration.js"></script>
</head>

<body>
<center><h2>雅诗通用用户后台测试接口</h2>
<?php 
function showerr() {
    require "yaloginGlobal.php";
    $showdetailedinformation = true;
    $errid = -2;
    if (isset($_GET["errid"])) {
        $errid = test_input($_GET["errid"]);
    } else if (isset($_POST["errid"])) {
        $errid = test_input($_POST["errid"]);
    }
    if (isset($_GET["backurl"])) {
        $backurl = test_input($_GET["backurl"]);
    } else if (isset($_POST["backurl"])) {
        $backurl = test_input($_POST["backurl"]);
    }
    if (!is_numeric($errid)) {
        $errid = -1;
    }
    $globalsett = new YaloginGlobal();
    $errorarr = $globalsett->erroridArr;
    $erridstr = strval($errid);
    $errinfo = isset($errorarr[$erridstr]) ? $errorarr[$erridstr] : "其他错误。";
    $showinfo = "代码 ".$erridstr;
    if ($showdetailedinformation == true) {
        $showinfo = $showinfo." : ".$errinfo;
    }
    $alerttitle = "发生错误";
    $alertbtntxt = "返回";
    $erridnum = intval($errid);
    if ($erridnum > 1000 && $erridnum < 10000) {
        $alerttitle = "提示";
        $alertbtntxt = "确定";
    }
    echo "<h3>".$alerttitle."</h3><p>";
    echo $showinfo;
    $backurl = null;
    $onclick = "";
    if ($backurl == null) {
        $onclick = "history.back(-1);";
    } else {
        $onclick = "window.location.href='YashiUser-Error.php?errid=".$backurl."';";
    }
    echo "</p><p><input type=\"button\" name=\"submitbutton\" id=\"submitbutton\" value=\"".$alertbtntxt."\"  onclick=\"".$onclick."\" ></p>";
}
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
showerr();
?>
<p>© Kagurazaka Yashi</p></center>
</body>
</html>
