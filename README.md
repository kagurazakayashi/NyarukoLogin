## 使用说明

注意：这个程序尚未做完，请勿使用。

###已完成功能

- 用户注册：支持设置 用户名、昵称、邮箱、密码、二级密码、密码提示问题、生日、性别 等。
- 用户激活：发送包含激活码的电子邮件，第二次登录未激活时重新发送。输入或通过邮件输入激活码激活。
- 历史记录：记录用户 注册、激活、登录 的失败与成功时的信息，提供开关进行单独项的历史记录开关。
- 用户登录：支持检查密码尝试错误次数、激活有效时间、密码是否过期、用户是否过期、权限等级、校验密码、二级密码。
- 检查登录状态：从 session 和 cookie 中获取当前登录用户的 名称、会话令牌、哈希值 等。
- 查询当前登录用户的其他信息：以用户哈希值来查询任意库中的任意表中的任意多个列值，库表名使用别名，支持防查询黑名单。
- HTML 调试输出 + JSON 输出。大多数类需要「echomode」选项来确定 HTML/JSON 输出，为空则什么都不输出以保护。

###集成步骤

1. 部署运行环境：需要 PHP + MySQL 环境。
2. 导入初始数据库：导入 SQL 文件夹中的 SQL 文件。
3. 修改数据库、邮件、历史等重要设置：修改 php/yaloginSQLSetting.php 文件里的设置项（重要）。
4. 修改一些显示条目等设置：修改 php/yaloginGlobal.php 文件里的设置项（可选）。
5. 部署文件：上传所有文件到服务器上的目标位置。
6. 测试基本功能：在网页中打开即可显示测试页面，测试一下功能是否正常。
7. 修改用户界面：修改所有「YashiUser-*.php」开头的文件为自己的，可以重命名。
8. 修改网页回显：可以修改所有「php/*C.php」中的「$html = 」输出来自定义输出的html；也可以只修改「YashiUser-Alert.php」来统一处理返回值，减少修改的文件数量。
9. 创建其他用户资料表：在数据库中创建自己的业务表，通过接入「php/yaloginInformation.php」即可读取，用户特征为 hash 值。
10. 从其他网页中检索登录状态：通过接入「php/yaloginStatus.php」查询登录状态，然后用「php/yaloginInformation.php」读取信息。

###命名方式

- UI 层：
 - 「YashiUser-*.php」为用户 UI 层，可以任意替换，注意保持功能。
- 连接层：
 - 「php/*C.php」为 UI 与逻辑连接层，可以替换输出等功能部分代码。同时也可以作为逻辑层的使用说明。
- 逻辑层：
 - 「php/」中的其他 php 文件为逻辑层，保持原样即可。

###接入方式

- HTML 接入时：
 - 修改所有 UI 层文件开头的文件为自己的，可以重命名，注意所使用的接口。「echomode」设置为「html」。
- APP 接入时：
 - 根据接口说明直接调用各连接层网址，接收返回的 json 数据。「echomode」设置为「html」。
- PHP 接入时：
 - 根据说明直接调用各逻辑层 PHP 。

##类介绍

###通用类

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
- data：输入由 php/yaloginSafe.php->base_encode() 编码后的 JSON。
（可选，用于显示返回数据，JSON 仅限于单层字典[str:str]）。

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
- **isEmail**
- 确认是否符合电子邮件地址格式。
- 输入：emailAddress：电子邮件地址。
- 输出：bool：是否符合。
- **base_encode**
- 将字符串进行编码以便在网址中传输。
- 输入：string：明文。
- 输出：string：编码后字符。
- **base_decode**
- 将使用base_encode编码字符串进行解码。
- 输入：string：编码后字符。
- 输出：string：明文。

###php/yaloginSendmail.php
**[Model]后端：邮件模板和发送邮件**

依赖：

- php/yaloginSQLSetting.php（父级引入）
- php/class.phpmailer.php
- php/class.smtp.php

功能：

- **sendtestmail：发送测试邮件(mailtype:0)**
- 输入：接收方邮箱
- 输出：HTML
- **sendverifymail：发送注册验证邮件(mailtype:1)**
- 输入：address：接收方邮箱，username：请求注册的用户名，vcode：激活码，timeout：超时时间。
- 输出：错误信息，null 为成功。
- **sendretrievemail：发送找回密码邮件(mailtype:2)**
- 输入：address：接收方邮箱，username：请求注册的用户名，vcode：激活码，timeout：超时时间。
- 输出：错误信息，null 为成功。

##用户注册流程

[√]前端网页调用　[√]APP-API调用　[×]后端PHP直接调用

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

##用户激活流程

[√]前端网页调用　[√]APP-API调用　[√]后端PHP直接调用

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

[√]前端网页调用　[√]APP-API调用　[×]后端PHP直接调用

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

yaloginLogin 和 yaloginLoginC 提供 3 种功能：

- POST 输入 vcode，username，userpassword，userpassword2，userversion，autologin ：进行用户登录。
- POST 输入 multipleverification ：查询用户是否需要其他验证方式。
- GET 输入 logout ：进行用户注销。
- 通用输入：backurl，echomode。

依赖：

- php/yaloginLogin.php
- YashiUser-Login.php（echomode=HTML）

输入（GET/POST）：

- 模块 php/yaloginLogin.php 所需的数据。
- backurl：处理完成后要返回的页面（可选，默认为后退JS）。
- echomode：返回值格式（HTML/JSON）。

输入（GET）：

- logout：无参数（可选模式）：执行注销操作（否则为登录操作）。

输入（POST）：

- multipleverification：用户名（可选模式）：检查是否需要其他验证方式。

输出（JSON/HTML。HTML 输出模式将提交到 YashiUser-Alert.php 处理。）：

- result：返回的结果代码。
- backurl：要返回的页面。
- sessiontoken：PHP会话令牌。（登录操作时）
- sessionname：PHP会话令牌名称。（登录操作时）
- sessionid：PHP会话令牌ID。（登录操作时）
- username：用户名。（登录操作时）
- userhash：用户哈希。（登录操作时）
- lifetime：有效期至。（登录操作时）
- 查询是否需要其他验证方式时，返回需要验证方式数组。

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
- username：用户名（是否需要其他验证方式时，只需要这一个参数）。
- userpassword：密码。
- userpassword2：二级密码（可选）。
- userversion：用户数据模型版本。
- autologin：自动登录时长（秒）。

输出：

- vaild() -> int ：返回结果代码。
- $cookiejsonarr：用户基础信息（sessiontoken，sessionname，sessionid，username，userhash，lifetime）。
- multipleverification($username)：返回需要验证方式数组。

##用户登录状态查询

[√]前端网页调用　[√]APP-API调用　[√]后端PHP直接调用

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

输出（JSON/HTML。HTML 输出模式将ID和数据提交到 YashiUser-Alert.php 处理。）：

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

输入（PHP调用）：

- loginuser()

输出：

- loginuser() -> int / [str]：
 - 返回int时：错误代码。
 - 返回字符串字典数组时：当然登录用户的基础信息。
  - sesinfoarr：autologinby：登录状态获取方式：包括 fail，cookie，session。
  - cookiejsonarr：用户基础信息（sessiontoken，sessionname，sessionid，username，userhash，lifetime）（有用户登录时）。

##用户信息查询流程

[√]前端网页调用　[√]APP-API调用　[√]后端PHP直接调用

###php/yaloginInformationC.php
**[Controller]后端：当前用户资料查询程序连接器**

依赖：

- php/yaloginInformation.php
- YashiUser-Alert.php（echomode=HTML）

输入（POST）：

- backurl：处理完成后要返回的页面（可选，默认为后退JS）。
- echomode：返回值格式（HTML/JSON）。不推荐 HTML 。
- db：要查询的数据库名称（使用别名，空为默认 db_name 数据库，推荐为空）。
- table：要查询的表（使用别名，空为默认 db_user_table 用户表）。
- column：要查询的所有列(逗号分隔，例如 "username,useremail" )。

输出（JSON/HTML。HTML 输出模式将ID和数据提交到 YashiUser-Alert.php 处理。）：

- result：返回的结果代码。
- backurl：要返回的页面。
- 其他：在 php/yaloginInformation.php 中输入的所有查询命令得到的结果。

###php/yaloginInformation.php
**[Model]后端：当前用户资料查询程序**

- 为了安全，只能查询当前已登录用户的资料。
- 目标数据不能从名称为纯数字的列中取。

依赖：

- php/yaloginUserInfo.php（父级引入）
- php/yaloginGlobal.php
- php/yaloginStatus.php
- php/yaloginSQLC.php

输入（PHP调用）：

- getInformation($column,$table,$db)
 - column：要查询的所有列(逗号分隔，例如 "username,useremail" )。
 - table：要查询的表（使用别名，空为默认 db_user_table 用户表）。
 - db：要查询的数据库名称（使用别名，空为默认 db_name 数据库，推荐为空）。

输出：

- getInformation() -> int / [str]：
 - 返回int时：错误代码。
 - 返回字符串字典数组时：所查询的所有用户资讯。
  - [查询的key=>值,查询的key=>值...]
- subsql() -> int / [str]：
 - 这种方式将直接对数据库进行自定义查询，为了安全最好不要直接用它。
 - 返回int时：错误代码。
 - 返回字符串字典数组时：所查询的所有用户资讯。
  - [查询的key=>值,查询的key=>值...]

##通过邮件重置密码流程

[√]前端网页调用　[√]APP-API调用　[×]后端PHP直接调用

尚未开发完成。