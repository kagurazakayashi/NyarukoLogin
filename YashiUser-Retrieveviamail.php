<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>YashiUser-Retrieveviamail</title>
<link href="css/YashiUser-Registration.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/md5.js"></script>
<script type="text/javascript" src="js/md6.js"></script>
<script type="text/javascript" src="js/YashiUser-Retrieveviamail.js"></script>
<!--<script src="js/require.js" data-main="js/YashiUser-Registration.js"></script>-->
</head>
<body>
<center><h2>雅诗通用用户登录后台测试接口</h2>
<h3>通过邮件找回密码</h3></center>
<form action="?" id="form1" name="form1" method="post">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tbody>
        <tr>
            <td align="right" width="50%">电子邮件*(text)：</td>
            <td><input type="text" name="useremail" id="useremail" value="test@test.test"></td>
        </tr>
        <tr>
            <td></td>
            <td><img id="vcodeimg" title="点击刷新" src="image/getvalidateimage.gif" align="absbottom" onclick="this.src='php/validate_image.php?'+Math.random();" alt="点击刷新"></img> ←点击可以刷新</td>
        </tr>
        <tr>
            <td align="right">验证码*(text)：</td>
            <td><input type="text" name="vcode" id="vcode"></td>
        </tr>
        <tr>
            <td align="right"><input type="reset" name="reset" id="reset" value="取消"></td>
            <td><input type="button" name="submitbutton" id="submitbutton" value="验证邮箱地址" onclick="toVaild('php/yaloginRetrieve.php')"></td>
        </tr>
    </tbody>
  </table>
</form>
<center><p>© Kagurazaka Yashi</p></center>
</body>
</html>