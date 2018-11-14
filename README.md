# NyarukoLogin 2

- 注意：这个程序尚未做完，请勿使用。
- 要查看旧版本代码和此处的说明，请前往 v2016_expired 分支。

## 通用参数

### 通用提交内容

- 请求值全体 POST 格式，每次至少上传以下参数：
- `apiver` : API版本
- `token` : 会话令牌（部分场景不需要）
- `appid` : 应用ID
- `appsecret` : 应用密码

### 通用返回内容

- 返回值全体为 JSON 格式
- 第一项必为 `stat` 字段，内容为整数状态代码，错误代码见 `nyainfomsg` 文件。

## 文件列表

### src/

- `nyacore.class.php`
  - 核心类
  - 功能：导入设置类、提示信息类、数据库连接类、安全类
- `nyainfomsg.class.php`
  - 提示信息类
  - 功能：创建异常信息提示JSON
- `nyaconnect.class.php`
  - 数据库连接类
  - 查询数据，插入数据，更新数据，查询数据总量，测试SQL连接，执行SQL连接
  - 注意：全是最终数据库方法，不具备安全过滤功能。
- `nyasafe.class.php`
  - 安全类
  - 哈希类：base64 加密解密、md5 判断
  - 随机信息生成类：随机文本生成、随机哈希生成、随机种子生成
  - 字符串检查类：非法字符检查，是否为电子邮件、IP地址、手机号、数组字符串、违禁词汇

### tests/
- `sqlconnect.php`,`sqlconnect.sh` : 测试数据库连接是否正常。
  
## 错误代码表

- JSON返回格式: `{"stat":xxxx,"msg":"..."}` 。
- 返回值及其对应的解释内容见 `nyainfomsg.class.php` 。