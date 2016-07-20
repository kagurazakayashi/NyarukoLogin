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
**[View]前端：信息提示页面。**

依赖：

- css/YashiUser-Registration.css
- js/md5.js
- js/YashiUser-Registration.js
- php/yaloginGlobal.php（通用设置）

输入（POST / GET）：

- errid：返回值ID。
- backurl：处理完成后要返回的页面（可选，默认为后退JS）。

输出：HTML

###php/yaloginSafe.php
**[Model]后端：安全相关类**

依赖：

- php/md6.php

功能：

- ***md6hash**
- MD6 哈希值。
- 输入：data：输入要哈希的字符串。
- 输出：string：该字符串的哈希值。
- **randstr**
- 随机文本生成。
- 输入：len：长度，chars：字符库。
- 输出：string：随机文本。
- **randhash**
- 生成一个随机哈希值，用于创建临时会话等标记。
- 输入：userinfo：要混入的用户固定信息文本。
- 输出：string：随机 MD6 哈希值。
- **containsSpecialCharacters**
- 识别是否有特殊字符。
- 输入：data：文字数据，inputmatch：特殊字符库。
- 输出：bool：返回值代码，0 为正常。
- **clearSpecialCharacters**
- 清除特殊字符。
- 输入：data：文字数据。
- 输出：string：过滤后文字，null 为错误。
- **is_md5**
- 确认是否符合32位MD5格式。
- 输入：md5str：MD5文字。
- 输出：bool：是否符合。

###php/yaloginSendmail.php
**[Model]后端：邮件模板和发送邮件**

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
**[View]前端：用户注册测试页面。**

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
**[Controller]后端：用户注册程序连接器。**

依赖：

- php/yaloginRegistration.php
- echomode：返回值格式（HTML/JSON）（可选，默认HTML）。
- YashiUser-Alert.php（echomode=HTML）

输入（POST）：

- 来自 YashiUser-Registration.php 的表单数据，调用 php/yaloginRegistration.php 处理。
- backurl：处理完成后要返回的页面（可选，默认为后退JS）。

输出（JSON/HTML，默认 JSON。HTML 输出模式将提交到 YashiUser-Alert.php 处理。）：

- result：返回的结果代码。
- backurl：要返回的页面。

###php/yaloginRegistration.php
**[Model]后端：用户注册程序。**
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

###YashiUser-Activation.php
**[View]前端：输入激活码激活用户**
提供激活码输入表单。

依赖：

- css/YashiUser-Registration.css
- js/YashiUser-Activation.js
- php/yaloginActivation.php

输入（GET/POST）：

- acode：要自动键入的激活码（可选）

输出：

- 将 acode 提交到 php/yaloginActivationC.php 。

###php/yaloginActivationC.php
**[Controller]后端：用户激活程序连接器。**

依赖：

- php/yaloginActivation.php
- YashiUser-Alert.php（echomode=HTML）

输入（GET）：

- 来自 YashiUser-Activation.php 的表单数据，调用 php/yaloginActivation.php 处理。
- backurl：处理完成后要返回的页面（可选，默认为后退JS）。
- echomode：返回值格式（HTML/JSON）（可选，默认HTML）。

输出（JSON/HTML，默认 JSON。HTML 输出模式将提交到 YashiUser-Alert.php 处理。）：

- result：返回的结果代码。
- backurl：要返回的页面。

###php/YashiUserActivation.php
**[Model]后端：用户激活程序**
判断输入的激活码正确、未过期、目标用户未激活，然后激活用户。

依赖：

- php/yaloginGlobal.php
- php/yaloginSQLC.php

输入（GET/POST）：

- acode：要使用的激活码
- vaild() -> int ：返回结果代码

##用户登录流程

###开发计划

- 输入 用户名、密码、记住密码时长、验证码。
- 校验验证码。
- 校验输入是否有特殊符号，与注册时校验方式匹配。
- 取 username(text) 校验用户名是否存在。
- 如果存在读取用户信息。
- 取 userpasserr(int) 检查密码尝试错误次数，超过一定量设密码无效，并重置为0。
- 取 verifymail(datetime) 激活有效时间，空为已激活，已超过时间询问是否重发邮件（重发限制时间）。
- 取 userpasswordenabled(tinyint) 检查密码是否有效，无效要求强制改密码。
- 取 userenable(datetime) 校验用户是否过期，未达启用时间为封禁期。
- 取 userjurisdiction(int) 权限等级，1直接视为封禁。
- 取 userpassword(text) 与输入的 MD6 进行校验密码。
- 取 userpassword2(text) ，如果需要返回输入页面增加二级密码输入框。与输入的 MD6 进行校验二级密码。
- 取 authenticatorid/authenticatortoken ：校验密保令牌，暂时不做。
- 写入登录状态，将登录操作记录到日志。
- 进行一次登录状态查询，确认已登录。
- 将用户信息返回，由后续程序校验权限和显示等。