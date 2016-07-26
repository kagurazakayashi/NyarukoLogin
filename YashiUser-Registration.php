<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>YashiUser-Registration</title>
<link href="css/YashiUser-Registration.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/md5.js"></script>
<script type="text/javascript" src="js/md6.js"></script>
<script type="text/javascript" src="js/YashiUser-Registration.js"></script>
<!--<script src="js/require.js" data-main="js/YashiUser-Registration.js"></script>-->
</head>

<body>
<center><h2>雅诗通用用户登录后台测试接口</h2>
<h3>用户注册</h3></center>
<form action="?" id="form1" name="form1" method="post">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tbody>
      <tr>
        <td align="right">配置文件版本(uint5)：</td>
        <td><input type="text" name="userversion" disabled id="userversion" value="1"></td>
      </tr>
      <tr>
        <td align="right">用户名*(text)：</td>
        <?php
        echo '<td><input type="text" name="username" id="username" value="testuser'.
date("YmdHis").'"></td>';
        ?>
      </tr>
      <tr>
        <td align="right">昵称(text)：</td>
        <td><input type="text" name="usernickname" id="usernickname" value=""></td>
      </tr>
      <tr>
        <td align="right">电子邮件*(text)：</td>
        <td><input type="text" name="useremail" id="useremail" value="test@test.test"></td>
      </tr>
      <tr>
        <td align="right">密码*(text)：</td>
        <td><input type="password" name="userpassword" id="userpassword" value="testpass"></td>
      </tr>
      <tr>
        <td align="right">验证密码*(text)：</td>
        <td><input type="password" name="userpasswordr" id="userpasswordr" value="testpass"></td>
      </tr>
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
      <tr>
        <td align="right">性别*(int2)：</td>
        <td><select name="usersex" id="usersex">
          <?php
          require "php/yaloginGlobal.php";          
          $globalsett = new YaloginGlobal();
          $sexArrin = $globalsett->sexArr;
          for ($i=0; $i < count($sexArrin); $i++) { 
            echo "<option value=\"".$i."\">".$sexArrin[$i]."</option>";
          }
          ?>
        </select></td>
      </tr>
      <tr>
        <td align="right">生日(date)：</td>
        <td><input type="text" name="userbirthday" id="userbirthday" value=""></td>
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
        <td align="right">&nbsp;</td>
        <td>&nbsp;</td>
        <input type="hidden" name="echomode" id="echomode" value="html">
        <?php
          $backurl = isset($_GET["backurl"]) ? $_GET["backurl"] : "YashiUser-Login.php";
          echo '<input type="hidden" name="backurl" id="backurl" value="'.$backurl.'\">';
        ?>
      </tr>
      <tr>
        <td align="right"><input type="reset" name="reset" id="reset" value="取消"></td>
        <td><input type="button" name="submitbutton" id="submitbutton" value="注册新用户" onclick="toVaild('php/yaloginRegistrationC.php')"></td>
      </tr>
    </tbody>
  </table>
</form>
<center><p>© Kagurazaka Yashi</p></center>
</body>
</html>
