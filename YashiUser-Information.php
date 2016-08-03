<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>YashiUser-Status</title>
<link href="css/YashiUser-Registration.css" rel="stylesheet" type="text/css">
</head>
<body>
<center><h2>雅诗通用用户登录后台测试接口</h2>
<h3>当前已登录用户信息查询</h3></center>
<form action="php/YaloginInformationC.php" name="form1" method="get">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tbody>
        <tr>
            <td align="right">要查询的数据库别名*(text)：</td>
        <td><input type="text" name="db" id="db" value=""></td>
        </tr>
        <tr>
            <td align="right">要查询的表别名*(text)：</td>
        <td><input type="text" name="table" id="table" value=""></td>
        </tr>
        <tr>
            <td align="right">要查询的列,用半角逗号分隔*(text)：</td>
        <td><input type="text" name="column" id="column" value=""></td>
        </tr>
    </tbody>
  </table>
  <center>
            <input type="radio" name="echomode" value="html" id="echomode_0" checked>
                    HTML<br>
            <input type="radio" name="echomode" value="json" id="echomode_1">
                    JSON
            <input type="hidden" name="backurl" value="YashiUser-Information.php">
            <p><input type="submit" value="查询"></p>
            </center>
</form>
<center><p>© Kagurazaka Yashi</p></center>
</body>
</html>