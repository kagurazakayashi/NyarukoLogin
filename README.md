##通用类

- php/yaloginGlobal.php：通用设置，在这里进行程序配置
- php/yaloginSQLSetting.php：通用数据库设置，在这里进行数据库配置
- php/yaloginUserInfo.php：用户信息模型

###第三方类库
- php/md6.php：MD6哈希编码支持。
- php/class.phpmailer.php：PHP邮件发送
- php/class.smtp.php：SMTP邮件协议支持
- php/validate_code.class.php：验证码
- php/validate_image.php：验证码创建

###YashiUser-Alert.php
**前端：信息提示页面。**

依赖：

- css/YashiUser-Registration.css
- js/md5.js
- js/YashiUser-Registration.js
- php/yaloginGlobal.php（通用设置）

输入（POST / GET）：

- errid：返回值ID。
- backurl：要返回的网页（可选，默认为JS关闭窗口）。

输出：HTML

###php/yaloginSafe.php
**后端：安全相关类**

依赖：

- php/md6.php

功能：

- MD6：md6hash

- randstr：随机文本生成
- 输入：len：长度，chars：字符库
- 输出：string：随机文本

- containsSpecialCharacters：识别是否有特殊字符
- 输入：data：文字数据，inputmatch：特殊字符库
- 输出：bool：返回值代码，0 为正常。

- clearSpecialCharacters：清除特殊字符
- 输入：data：文字数据
- 输出：string：过滤后文字，null 为错误。

###php/yaloginSendmail.php
**后端：邮件模板和发送邮件**

依赖：

- php/yaloginSQLSetting.php（父级引入）
- php/class.phpmailer.php
- php/class.smtp.php

功能：

- sendtestmail：发送测试邮件
- 输入：接收方邮箱
- 输出：HTML

- sendverifymail：发送注册验证邮件
- 输入：address：接收方邮箱，username：请求注册的用户名，vcode：激活码，timeout：超时时间。
- 输出：错误信息，null 为成功。


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

###php/yaloginRegistrationC.php
**后端：用户注册程序连接器。**

依赖：

- php/yaloginRegistration.php
- YashiUser-Alert.php（echomode=HTML）

输入（POST）：

- 来自 YashiUser-Registration.php 的表单数据，调用 yaloginRegistration.php 处理。
- backurl：完成后要返回的页面。（可选）

输出（JSON/HTML）：

- result：注册程序返回的结果代码。
- backurl：要返回的页面。
- 默认 JSON。HTML 输出模式将提交到 YashiUser-Alert.php 处理。

###php/yaloginRegistration.php
**后端：用户注册程序。**
查询用户名是否重复、记录日志、发送激活码邮件。

依赖：

- php/yaloginUserInfo.php
- php/yaloginGlobal.php
- php/yaloginSendmail.php
- php/yaloginSQLC.php
- php/yaloginSafe.php

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