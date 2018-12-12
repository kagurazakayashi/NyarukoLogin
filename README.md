# NyarukoLogin 2

一个通用的用户登录系统

- 注意：这个程序尚未做完，请勿使用。
- 要查看旧版本代码和此处的说明，请前往 v2016_expired 分支。

## 功能
- 返回值
  - 除了加密问题会直接返回 403 ，其他情况下均返回 JSON 。
- 数据库
  - 支持单独指定多个只读数据库和多个写入数据库，来分摊负载。
  - 支持同时连接只读数据库和写入数据库进行操作，以完成单次读写都有的操作。
  - 数据库类调用时自动选择建立只读数据库或写入数据库的连接，并自动断开连接。
  - 独立通用的关键词数据库。
  - 支持自由指定数据库中每个表的名称。
- 加密传输
  - 客户端第一次连接时将和服务器端商定一个 TOTP 动态密码，并用动态密码加密每次 JSON 的收发，详情见 `加密处理流程` 部分。
- IP 地址验证
  - 支持为每个功能设定不同的接口访问频率。
  - 支持 IP 地址黑名单，并且可以设置封禁时长。
- 敏感词屏蔽
  - 支持从 json 文件拉取和 Redis 快速拉取词语表
  - 提供一个转换脚本用于导入其他应用中的逐行 txt 词库

## 所需第三方库
### 必备库
```
composer require phpgangsta/googleauthenticator
composer require xxtea/xxtea
composer require gregwar/captcha
```

### 测试用库
```
pip3 install onetimepass
pip3 install demjson
pip3 install xxtea-py cffi
```

## 通用参数

### 通用提交内容

- 请求值全体使用 POST/GET （两者均可，自动识别），每次需要上传以下参数：
- `t` : 当前设备加密识别码（可选）
- `j` : 经过 base64 编码、替换符号、然后用当前 TOTP 码 作为密码进行 XXTEA 加密后的请求JSON，如果不提供 `t` 参数则无需进行 TOTP/XXTEA 加密，详情见 `加密处理流程` 部分。JSON 中每次需要上传以下参数：
  - `apiver` : API版本
  - `token` : 会话令牌（部分场景不需要）
  - `appid` : 应用ID
  - `appsecret` : 应用密码

### 通用返回内容

- 返回值全体为经过 base64 编码、替换符号、然后用当前 TOTP 码作为密码进行 XXTEA 加密后的请求JSON，如果不提供 `t` 参数则无需进行 TOTP/XXTEA 加密，详情见 `加密处理流程` 部分。返回内容：
  - 第一项必为 `code` 字段，内容为整数状态代码，错误代码见 `nyainfomsg` 文件。

## 文件列表

所有 php 脚本使用 PHP 7 运行，所有 py 脚本使用 Python 3 运行。

- `src/nyacore.class.php`
  - 核心类
  - 功能：导入设置类、提示信息类、数据库连接类、安全类，以及各种第三方库。
- `src/nyainfomsg.class.php`
  - 提示信息类
  - 功能：创建异常信息提示JSON、向客户端返回异常。
- `src/nyaconnect.class.php`
  - 数据库连接类
  - 查询数据，插入数据，更新数据，查询数据总量，测试SQL连接，执行SQL连接。
  - 注意：全是最终数据库方法，不具备安全过滤功能。
- `src/nyasafe.class.php`
  - 安全类
  - base64 加密解密、url适用base64、xxtea 和 TOTP 解密等
  - 随机文本生成、随机哈希生成、随机种子生成等
  - 非法字符检查，是否为电子邮件、IP地址、手机号、数组字符串、违禁词汇、md5判断等
- `nyatotp.php` 和 `src/nyatotp.class.php`
  - 申请加密信息接口。

### tests/
- `sqlconnect.php`,`sqlconnect.sh` : 测试数据库连接是否正常。
- `test_gettotptoken.py` : 获取 `app_secret`，需要修改文件 `postData` 变量，提供与数据库 `external_app` 表中记录的内容。
- `test_core.py` : 加密和解密客户端，以下文件均依赖于此文件，不要直接执行它。
  - `test_encrypt.py` : 测试加密传输参数的提交和收到信息的解密，可修改 `udataarr` 变量，自定义提交的测试数据。
- `wordfilter.php` 敏感词模块测试，测试敏感词模块是否正常工作，提交 `?w=关键词` ，返回是否为敏感词。

### tools/
- `filterwords_txt2json.py` : 这个工具可以将从其他地方获取的txt逐行关键词列表转换为本程序可识别的json，并写入redis数据库。详细配置方式见该文件内注释。

## 错误代码表

- JSON返回格式: `{"stat":xxxx,"msg":"..."}` 。
- 返回值及其对应的解释内容见 `nyainfomsg.class.php` 。

## 处理流程

### TOTP/XXTEA 加密处理流程

#### 1. 获取加密凭证

客户端操作步骤演示文件： `tests/gettotptoken.py`

- 在数据库 `external_app` 表中登记 APP 的 `app_id` 和 `app_secret`。
- 客户端调用 `nyatotp.php`，提供以下参数：
  - `n` : app_id
  - `s` : app_secret
- 服务器将：
  - 检查 IP 访问频率、是否被封禁、在 `ip_address` 表查询或注册
  - 检查提交的应用名称和密钥的格式
  - 检查应用是否已经注册 `appname` 和 `appsecret`
  - 创建新的 `totp secret`
  - 创建 `apptoken`
  - 检查 `session_totp` 表，注销已存在的 `secret` 或者 `apptoken`
  - 将以上生成的信息写入 `session_totp` 表
- 服务器将返回以下参数：
  - `code` : 状态码（见 `错误代码表` 部分）
  - `totp_secret` : totp 验证器生成代码所需的 secret，应保存在应用中。
  - `totp_code` : totp 验证器生成的第一组代码，应用收到 totp_secret 后建议立即生成代码，核对是否与之匹配。
  - `totp_token` : 本应用与服务器加密交互的认证代码，应保存在应用中。
  - `time` : 当前服务器时间戳。
  - `appname` : 所申请的 app_id。

#### 2. 将 JSON 加密并上传

客户端操作步骤演示文件： `tests/test_encrypt.py`

1. 将需要提交的内容数组转换为 JSON 字符串。
2. 使用 totp 验证器，通过保存的 `totp_secret` 生成当前的 `totp_code`。
3. 将 `totp_secret` 和 `totp_code` 两个字符串合并，并计算 MD5。
4. 使用该 MD5 作为密码，对 JSON 字符串进行 `xxtea` 加密。
5. 使用 `base64` 编码 `xxtea` 加密后的结果。
6. 对 `base64` 中的部分符号进行替换：
  - `+` 改成 `-`
  - `/` 改成 `_`
  - 删除 `=`
7. 检查最终生成的字符串长度，是否低于 `nyaconfig.class.php` 中设置的值。
8. 客户端调用所需接口，提供以下参数：
  - `t` : 本地所保存的 totp_token 。
    - 本参数为可选，如果不提供，则不用执行上述第 2 步和第 3 步，在第 4 步使用 `base64` 直接编码 JSON 字符串即可。内容将以
    - `nyaconfig.class.php` 中允许将此项设置为必须。
  - `j` : 第 5 步生成的加密字符串。

服务器收到请求后将：
检查 IP 访问频率
检验参数格式
检查 IP 是否被封禁
查询 apptoken 对应的 secret
使用 secret 生成 totp 代码
使用 secret+totp代码 计算 md5
使用 md5 对提交的内容解密
解析 json
（具体功能逻辑）
对需要返回的内容包装为 json
重新执行 步骤5 和 步骤6
使用 md5 对提交的内容加密
将加密后的结果(加密JSON)或者错误信息(明文HTML/TXT/JSON或者直接403/500)返回给客户端

#### 3. 解析服务器返回的 JSON 数据

客户端操作步骤演示文件： `tests/test_encrypt.py`

1. 检查是否返回了异常，包括：
  - PHP 报错信息
  - HTTP 错误代码，如 403（通常为参数或加密失败或IP封禁）、 404 等。
  - 其他的未预期返回格式、字符，通常不应包含：
    - `A-Z`, `a-z`, `0-9`, `-`, `_` 以外的字符或空格、换行。
2. 对 `base64` 中的部分符号进行替换：
  - `-` 改成 `+`
  - `_` 改成 `/`
  - 在末尾补足 `=`：
    - 需要补充 `=` 的数量为 `4 - 字符串长度 % 4` ，如果 `字符串长度 % 4` 为 0 则不补。
    - 可参考 `nyasafe.class.php` 中的 `urlb64decode` 方法中的写法。
3. 使用 `base64` 解码。
4. 使用 totp 验证器，通过保存的 `totp_secret` 生成当前的 `totp_code`。
5. 将 `totp_secret` 和 `totp_code` 两个字符串合并，并计算 MD5。
6. 使用该 MD5 作为密码，对 `base64` 解码后的信息进行 `xxtea` 解密，获得 JSON 字符串。
7. 将 JSON 字符串解析为数组，进行后续操作。

### 用户注册流程

