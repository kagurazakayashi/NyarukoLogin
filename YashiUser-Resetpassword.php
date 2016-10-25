<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>YashiUser-Registration</title>
<link href="css/YashiUser-UI.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/md5.js"></script>
<script type="text/javascript" src="js/md6.js"></script>
<script type="text/javascript" src="js/YashiUser-Resetpassword.js"></script>
<!--<script src="js/require.js" data-main="js/YashiUser-Registration.js"></script>-->
</head>
<body>
<center><h2>雅诗通用用户登录后台测试接口</h2>
<h3>修改密码</h3><hr></center>
<form action="?" id="form1" name="form1" method="get">
  <table>
    <tbody>
      <tr>
        <td align="right">邮件验证码*(text)：</td>
        <?php
          $acode = isset($_GET["acode"]) ? $_GET["acode"] : "";
          echo '<td><input type="text" name="mcode" id="mcode" value="'.$acode.'"></td>';
        ?>
      </tr>
      <tr>
        <td align="right">密码*(text)：</td>
        <td><input type="password" name="userpassword" id="userpassword" value=""></td>
      </tr>
      <tr>
        <td align="right">验证密码*(text)：</td>
        <td><input type="password" name="userpasswordr" id="userpasswordr" value=""></td>
      </tr>
      <tr><td><hr></td><td><hr></td></tr>
      <tr>
        <td align="right">二级密码(text)：</td>
        <td><input type="password" name="userpassword2" id="userpassword2"></td>
      </tr>
      <tr>
        <td align="right">验证二级密码(text)：</td>
        <td><input type="password" name="userpassword2r" id="userpassword2r"></td>
      </tr>
      <tr>
        <td align="right">密码提示问题1(text)：</td>
        <td><input type="text" name="userpasswordquestion1" id="userpasswordquestion1" value=""></td>
      </tr>
      <tr>
        <td align="right">密码提示答案1(text)：</td>
        <td><input type="text" name="userpasswordanswer1" id="userpasswordanswer1" value=""></td>
      </tr>
      <tr>
        <td align="right">密码提示问题2(text)：</td>
        <td><input type="text" name="userpasswordquestion2" id="userpasswordquestion2" value=""></td>
      </tr>
      <tr>
        <td align="right">密码提示答案2(text)：</td>
        <td><input type="text" name="userpasswordanswer2" id="userpasswordanswer2" value=""></td>
      </tr>
      <tr>
        <td align="right">密码提示问题3(text)：</td>
        <td><input type="text" name="userpasswordquestion3" id="userpasswordquestion3" value=""></td>
      </tr>
      <tr>
        <td align="right">密码提示答案3(text)：</td>
        <td><input type="text" name="userpasswordanswer3" id="userpasswordanswer3" value=""></td>
      </tr>
      <tr><td><hr></td><td><hr></td></tr>
      <tr>
        <td></td>
        <td><img id="vcodeimg" title="点击刷新" src="image/getvalidateimage.gif" align="absbottom" onclick="this.src='php/validate_image.php?'+Math.random();" alt="点击刷新"></img> ←点击可以刷新</td>
      </tr>
      <tr>
        <td align="right">验证码*(text)：</td>
        <td><input type="text" name="vcode" id="vcode"></td>
      </tr>
      <tr>
        <td align="right">&nbsp;</td>
        <td>&nbsp;</td>
        <input type="hidden" name="echomode" id="echomode" value="html">
        <?php
          $backurl = isset($_GET["backurl"]) ? $_GET["backurl"] : "YashiUser-Login.php";
          echo '<input type="hidden" name="backurl" id="backurl" value="'.$backurl.'\">';
          echo '<input type="hidden" name="mode" id="mode" value="cpwd">';
        ?>
      </tr>
      <tr>
        <td align="right"><input type="reset" name="reset" id="reset" class="mainbtn" value="取消"></td>
        <td><input type="button" name="submitbutton" id="submitbutton" class="mainbtn" value="修改" onclick="toVaild('php/yaloginRetrieveviamailC.php')"></td>
      </tr>
    </tbody>
  </table>
</form>
<center><p>© Kagurazaka Yashi</p></center>
</body>
</html>