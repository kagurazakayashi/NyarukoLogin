##已完成功能

- 用户注册：支持设置 用户名、昵称、邮箱、密码、二级密码、密码提示问题、生日、性别 。
- 用户激活：发送包含激活码的电子邮件，第二次登录未激活时重新发送。输入或通过邮件输入激活码激活。
- 历史记录：记录用户 注册、激活、登录 的失败与成功时的信息，提供开关进行单独项的历史记录开关。
- 用户登录：支持检查密码尝试错误次数、激活有效时间、密码是否过期、用户是否过期、权限等级、校验密码、二级密码。

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

- **md6hash**
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

- **sendtestmail：发送测试邮件**
- 输入：接收方邮箱
- 输出：HTML
- **sendverifymail：发送注册验证邮件**
- 输入：address：接收方邮箱，username：请求注册的用户名，vcode：激活码，timeout：超时时间。
- 输出：错误信息，null 为成功。


##用户注册流程
###YashiUser-Registration.php
**[View]前端：用户注册测试页面**

依赖：

- css/YashiUser-Registration.css
- js/md5.js
- js/md6.js
- js/YashiUser-Registration.js
- php/validate_image.php

输入（GET）：

- backurl：要返回的页面，默认值「YashiUser-Login.php」。

输出：

- 发送数据到：php/yaloginRegistrationC.php

###php/yaloginRegistrationC.php
**[Controller]后端：用户注册程序连接器**

依赖：

- php/yaloginRegistration.php
- echomode：返回值格式（HTML/JSON）。
- YashiUser-Alert.php（echomode=HTML）

输入（POST）：

- 模块 php/yaloginRegistration.php 所需的数据。
- backurl：处理完成后要返回的页面（可选，默认为后退JS）。

输出（JSON/HTML。HTML 输出模式将提交到 YashiUser-Alert.php 处理。）：

- result：返回的结果代码。
- backurl：要返回的页面。

###php/yaloginRegistration.php
**[Model]后端：用户注册程序**

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
- php/yaloginActivationC.php

输入（GET）：

- acode：要自动键入的激活码（可选）。
- backurl：要返回的页面，默认值「YashiUser-Login.php」。

输出：

- 将 acode 提交到 php/yaloginActivationC.php 。

###php/yaloginActivationC.php
**[Controller]后端：用户激活程序连接器**

依赖：

- php/yaloginActivation.php
- YashiUser-Alert.php（echomode=HTML）

输入（GET）：

- 模块 php/yaloginActivation.php 所需的数据。
- backurl：处理完成后要返回的页面（可选，默认为后退JS）。
- echomode：返回值格式（HTML/JSON）。

输出（JSON/HTML。HTML 输出模式将提交到 YashiUser-Alert.php 处理。）：

- result：返回的结果代码。
- backurl：要返回的页面。

###php/YashiUserActivation.php
**[Model]后端：用户激活程序**

判断输入的激活码正确、未过期、目标用户未激活，然后激活用户。

依赖：

- php/yaloginGlobal.php
- php/yaloginSQLC.php

输入（GET/POST）：

- acode：要使用的激活码。

输出：

- vaild() -> int ：返回结果代码。

##用户登录与注销流程

###YashiUser-Login.php
**[View]前端：用户登录页**

提供用户密码输入表单。

依赖：

- css/YashiUser-Registration.css
- php/validate_image.php
- php/yaloginLoginC.php
- js/YashiUser-Login.js
- js/md5.js
- js/md6.js

输入（GET）：

- backurl：要返回的页面，默认值「YashiUser-Status.php」。

###php/yaloginLoginC.php
**[Controller]后端：用户登录和注销程序连接器**

依赖：

- php/yaloginLogin.php
- YashiUser-Login.php（echomode=HTML）

输入（GET/POST）：

- 模块 php/yaloginLogin.php 所需的数据。
- backurl：处理完成后要返回的页面（可选，默认为后退JS）。
- echomode：返回值格式（HTML/JSON）。

输入（GET）：

- logout：无参数（可选）：执行注销操作（否则为登录操作）。

输出（JSON/HTML。HTML 输出模式将提交到 YashiUser-Alert.php 处理。）：

- result：返回的结果代码。
- backurl：要返回的页面。
- sessiontoken：PHP会话令牌。（登录操作时）
- sessionname：PHP会话令牌名称。（登录操作时）
- sessionid：PHP会话令牌ID。（登录操作时）
- username：用户名。（登录操作时）
- userhash：用户哈希。（登录操作时）
- lifetime：有效期至。（登录操作时）

###php/yaloginLogin.php
**[Model]后端：用户登录和注销程序**

依赖：

- php/yaloginGlobal.php
- php/yaloginSQLC.php
- php/yaloginUserInfo.php
- php/yaloginSendmail.php
- php/yaloginSafe.php

输入（POST）：

- vcode：验证码。
- username：用户名。
- userpassword：密码。
- userpassword2：二级密码（可选）。
- userversion：用户数据模型版本。
- autologin：自动登录时长（秒）。

输出：

- vaild() -> int ：返回结果代码。
- $cookiejsonarr：用户基础信息（sessiontoken，sessionname，sessionid，username，userhash，lifetime）。

##用户登录状态查询

###YashiUser-Status.php
**[View]前端：当前用户登录状态查询请求页**

依赖：

- css/YashiUser-Registration.css
- php/YaloginStatusC.php

输出：

- 发送数据到：php/yaloginStatusC.php

###php/yaloginStatusC.php
**[Controller]后端：当前用户登录状态查询程序连接器**

依赖：

- php/yaloginStatus.php
- YashiUser-Login.php（echomode=HTML）

输入（GET）：

- 模块 php/yaloginStatus.php 所需的数据。
- backurl：处理完成后要返回的页面（可选，默认为后退JS）。
- echomode：返回值格式（HTML/JSON）。

输出（JSON/HTML。HTML 输出模式将提交到 YashiUser-Alert.php 处理。）：

- result：返回的结果代码。
- backurl：要返回的页面。
- autologinby：登录状态获取方式：包括 fail，cookie，session。
- sessiontoken：PHP会话令牌。（有用户登录时）
- sessionname：PHP会话令牌名称。（有用户登录时）
- sessionid：PHP会话令牌ID。（有用户登录时）
- username：用户名。（有用户登录时）
- userhash：用户哈希。（有用户登录时）
- lifetime：有效期至。（有用户登录时）

###php/yaloginStatus.php
**[Model]后端：当前用户登录状态查询程序**

依赖：

- php/yaloginUserInfo.php
- php/YaloginSQLSetting.php

输出：

- loginuser() -> int ：返回结果代码。
- sesinfoarr：autologinby：登录状态获取方式：包括 fail，cookie，session。
- cookiejsonarr：用户基础信息（sessiontoken，sessionname，sessionid，username，userhash，lifetime）（有用户登录时）。

##用户信息查询

###php/yaloginInformationC.php
**[Controller]后端：当前用户资料查询程序连接器**

依赖：

- php/yaloginInformation.php
- YashiUser-Alert.php（echomode=HTML，并且遇到错误时）

输入（POST）：

- 模块 php/yaloginInformation.php 所需的数据。
- backurl：处理完成后要返回的页面（可选，默认为后退JS）。
- echomode：返回值格式（HTML/JSON）。不推荐 HTML 。

输出（JSON/HTML）：

- （HTML 输出模式时，出现错误将提交到 YashiUser-Alert.php 处理，否则直接输出查询结果。）
- result：返回的结果代码。
- backurl：要返回的页面。
- 其他：在 php/yaloginInformation.php 中输入的所有查询命令得到的结果。

###php/yaloginInformation.php
**[Model]后端：当前用户资料查询程序**

- 为了安全，只能查询当前已登录用户的资料。
- 目标数据不能从名称为纯数字的列中取。

依赖：

- php/yaloginUserInfo.php
- php/yaloginGlobal.php
- php/yaloginStatus.php
- php/yaloginSQLC.php

输入（POST）：

- db：要查询的数据库名称（空为默认数据库，推荐为空）。
- table：要查询的表（空为默认用户表）。
- column：（字符串数组）要查询的所有列。

输出：

- getInformation() -> int/[str]：
- - 返回int时：错误代码。
- - 返回字符串字典数组时：所查询的所有用户资讯。
- subsql() -> int/[str]：
- - 这种方式将直接对数据库进行自定义查询，为了安全最好不要直接用它。
- - 返回int时：错误代码。
- - 返回字符串字典数组时：所查询的所有用户资讯。