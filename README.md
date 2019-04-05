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

## 使用的第三方库
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

# 更多信息
[请转至 Wiki](wiki)
