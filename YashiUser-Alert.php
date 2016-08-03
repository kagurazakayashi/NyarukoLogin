<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="robots" content="noarchive">
<title>YashiUser-Alert</title>
<link href="css/YashiUser-UI.css" rel="stylesheet" type="text/css">
<script type="text/ecmascript" src="js/md5.js"></script>
<script language="JavaScript" src="js/YashiUser-Registration.js"></script>
</head>

<body>
<center><h2>雅诗通用用户后台测试接口</h2>
<?php 
function showerr() {
/*
前端：信息提示
输入：errid，backurl
*/
    require "php/yaloginGlobal.php";
    require "php/yaloginSafe.php";
    $showdetailedinformation = true;
    $errid = -2;
    if (isset($_GET["errid"])) {
        $errid = test_input($_GET["errid"]);
    } else if (isset($_POST["errid"])) {
        $errid = test_input($_POST["errid"]);
    }
    $backurl = null;
    if (isset($_GET["backurl"]) || $_GET["backurl"] != "") {
        $backurl = test_input($_GET["backurl"]);
    } else if (isset($_POST["backurl"]) || $_POST["backurl"] != "") {
        $backurl = test_input($_POST["backurl"]);
    } else {
        $backurl = "javascript:window.opener=null;window.open('','_self');window.close();";
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
    if ($erridnum >= 1000 && $erridnum < 10000) {
        $alerttitle = "提示";
        $alertbtntxt = "确定";
    }
    echo "<h3>".$alerttitle."</h3>"; //标题
    echo "<p>".$showinfo."</p>"; //信息
    echoinfo();
    $onclick = "history.back(-1);";
    if ($backurl != null) {
        $onclick = "window.location.href='".$backurl."';";
    }
    echo '<p><input type="button" name="submitbutton" id="submitbutton" value="'.$alertbtntxt.'" onclick="'.$onclick.'" ></p>'; //按钮
}
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
function echoinfo() {
    $json = "";
    if (isset($_GET["data"]) == false || $_GET["data"] == "") {
        if (isset($_POST["data"]) == false || $_POST["data"] == "") {
            return;
        } else {
            $json = $_POST["data"];
        }
    } else {
        $json = $_GET["data"];
    }
    $safe = new yaloginSafe();
    $jsonarray = json_decode($safe->base_decode($json));
    echo '<hr><table><tbody>';
    while(list($key,$val)= each($jsonarray)) { 
        echo '<tr><th align="right" scope="row">'.$key.' ：</th><td align="left">'.$val.'</td></tr>';
    }
    echo "</tbody></table>";
}
showerr();
?>
<p>© Kagurazaka Yashi</p></center>
</body>
</html>
