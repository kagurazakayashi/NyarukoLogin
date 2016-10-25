<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>YashiUser-Activation</title>
<link href="css/YashiUser-UI.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/YashiUser-Activation.js"></script>
</head>
<body>
<center><h2>雅诗通用用户登录后台测试接口</h2>
<h3>激活用户</h3><hr></center>
<form action="?" id="form1" name="form1" method="get">
  <table>
    <tbody>
        <tr>
            <td align="right" width="50%">激活码*(text)：</td>
            <td><?php
                $inacode = isset($_GET["acode"]) ? $_GET["acode"] : "";
                echo '<input type="text" name="acode" id="acode" value="'.$inacode.'">';
            ?></td>
            
        </tr>
        <tr>
        <input type="hidden" name="echomode" id="echomode" value="html">
        <?php
          $backurl = isset($_GET["backurl"]) ? $_GET["backurl"] : "YashiUser-Login.php";
          echo '<input type="hidden" name="backurl" id="backurl" value="'.$backurl.'">';
        ?>
            <td align="right"><input type="reset" name="reset" id="reset" class="mainbtn" value="取消"></td>
            <td><input type="button" name="submitbutton" id="submitbutton" class="mainbtn" value="激活用户" onclick="toVaild('php/yaloginActivationC.php')"></td>
        </tr>
    </tbody>
  </table>
</form>
<center><p>© Kagurazaka Yashi</p></center>
</body>
</html>
