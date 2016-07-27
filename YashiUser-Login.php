<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>YashiUser-Login</title>
<link href="css/YashiUser-Registration.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/YashiUser-Login.js"></script>
<script type="text/javascript" src="js/md5.js"></script>
<script type="text/javascript" src="js/md6.js"></script>
</head>
<body>
<center><h2>雅诗通用用户登录后台测试接口</h2>
<h3>用户登录</h3></center>
<form action="?" id="form1" name="form1" method="post">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tbody>
    <tr>
        <td align="right">用户名*(text)：</td>
        <td><input type="text" name="username" id="username" value="testuser"></td>
      </tr>
      <tr>
        <td align="right">密码*(text)：</td>
        <td><input type="password" name="userpassword" id="userpassword" value="testpass"></td>
      </tr>
      <tr>
        <td align="right">二级密码(text)：</td>
        <td><input type="password" name="userpassword2" id="userpassword2"></td>
      </tr>
      <tr>
        <td align="right">自动登录时长*(text)：</td>
        <td><select name="autologin" id="autologin">
        <option value="0">不自动登录</option>
        <option value="3600">1小时</option>
        <option value="86400">1天</option>
        <option value="604800">1周</option>
        <option value="2592000">1月</option>
        <option value="7776000">1季</option>
        <option value="31536000">1年</option>
</td>
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
        <input type="hidden" name="echomode" id="echomode" value="html">
        <?php
          $backurl = isset($_GET["backurl"]) ? $_GET["backurl"] : "YashiUser-Status.php";
          echo '<input type="hidden" name="backurl" id="backurl" value="'.$backurl.'">';
        ?>
        <input type="hidden" name="userversion" id="userversion" value="1">
            <td align="right"><input type="reset" name="reset" id="reset" value="取消"></td>
            <td><input type="button" name="submitbutton" id="submitbutton" value="用户登录" onclick="toVaild('php/yaloginLoginC.php')"></td>
        </tr>
    </tbody>
  </table>
</form>
<center><p>© Kagurazaka Yashi</p></center>
</body>
</html>