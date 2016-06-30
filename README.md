##用户注册流程
###YashiUser-Registration.php
**前端：用户注册测试页面。**

依赖：

- css/YashiUser-Registration.css
- js/md5.js
- js/md6.js
- js/YashiUser-Registration.js
- php/validate_image.php

输入：

- 内嵌表单数据

输出：

- 发送数据到：php/yaloginRegistrationC.php

###yaloginRegistrationC.php
**后端：用户注册程序连接器。**

依赖：

- yaloginRegistration.php
- YashiUser-Alert.php（echomode=HTML）

输入（POST）：

- 来自 YashiUser-Registration.php 的表单数据，调用 yaloginRegistration.php 处理。
- backurl：完成后要返回的页面。（可选）

输出（JSON/HTML）：

- result：注册程序返回的结果代码。
- backurl：要返回的页面。
- 默认 JSON。HTML 输出模式将提交到 YashiUser-Alert.php 处理。

###yaloginRegistration.php
**后端：用户注册程序。**
查询用户名是否重复、记录日志、发送激活码邮件。

依赖：

- yaloginUserInfo.php
- yaloginGlobal.php
- yaloginSendmail.php
- yaloginSQLC.php
- yaloginSafe.php

输入（YashiUser-Registration.php_POST->yaloginRegistrationC.php->）

- username：用户名
- usernickname：昵称
- useremail：邮箱
- userpassword：密码
- userpassword2：二级密码
- userpasswordquestion1：密码提示问题1
- userpasswordanswer1：密码提示答案1
- userpasswordquestion2：密码提示问题2
- userpasswordanswer2：密码提示答案2
- userpasswordquestion3：密码提示问题3
- userpasswordanswer3：密码提示答案3
- userbirthday：生日
- usersex：性别

输出：

- vaild() -> int ：返回结果代码