<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>YashiUser-Status</title>
<link href="css/YashiUser-Registration.css" rel="stylesheet" type="text/css">
<!--<script src="js/require.js" data-main="js/YashiUser-Registration.js"></script>-->
</head>
<body>
<center><h2>雅诗通用用户登录后台测试接口</h2>
<h3>用户登录状态查询</h3></center>
<form action="php/YaloginStatusC.php" name="form1" method="get">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tbody>
        <tr>
            <td align="center">查询用户状态</td>
        </tr>
        <tr>
            <td align="center">
                <input type="radio" name="echomode" value="html" id="echomode_0" checked>
                    HTML<br>
                <input type="radio" name="echomode" value="json" id="echomode_1">
                    JSON
            </td>
        </tr>
        <tr>
            <td align="center">
            <input type="hidden" name="backurl" value="YashiUser-Status.php">
            <input type="submit" value="查询">
            </td>
        </tr>
    </tbody>
  </table>
</form>
<center><p>© Kagurazaka Yashi</p></center>
</body>
</html>