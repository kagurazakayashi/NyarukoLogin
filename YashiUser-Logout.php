<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>YashiUser-Retrieveviamail</title>
<link href="css/YashiUser-Registration.css" rel="stylesheet" type="text/css">
<!--<script src="js/require.js" data-main="js/YashiUser-Registration.js"></script>-->
</head>
<body>
<center><h2>雅诗通用用户登录后台测试接口</h2>
<h3>注销</h3></center>
<form action="php/yaloginLoginC.php" name="form1" method="get">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tbody>
        <tr>
            <td align="center">真的要登出吗？</td>
        </tr>
        <tr>
            <td align="center">
            <input type="hidden" name="logout">
            <input type="hidden" name="backurl" value="YashiUser-Login.php">
            <input type="hidden" name="echomode" value="html">
            <input type="submit" value="登出">
            </td>
        </tr>
    </tbody>
  </table>
</form>
<center><p>© Kagurazaka Yashi</p></center>
</body>
</html>