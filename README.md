# 雅诗个人网站登录系统 2

注意：这个程序尚未做完，请勿使用。

要查看旧版本代码和此处的说明，请前往 v2016_expired 分支。

## 数据库结构

*为必须

- kylogin_user (用户基本信息表格)
  - id (int32) *用户ID (主键,自增)
  - hash (text64) *用户哈希 (日期时间32哈希+名称邮箱32哈希)
  - mail (text32) *用户邮箱及验证信息 (可登录)
  - phone (int15) 用户手机号码及验证信息 (尚未开发)
  - mailv (text32) 邮箱验证码 (用户哈希+当前时间的哈希,空未验证,1已验证)
  - phonev (text32) 手机验证码 (由第三方平台决定的哈希,空未验证,1已验证)
  - pwd (text64) *密码哈希 (密码哈希前半部分哈希+密码哈希后半部分哈希)
  - name (text32) *用户名 (同时作为昵称,可登录)
  - ver (int1) *对应用户数据版本 (目前版本为2)
  - twostep (text) 额外登录手续代号 (逗号分隔符,选项:QA,SPWD)
  - loginfo (text) 最近登录信息 (逗号分隔符,TIME/IP/APP)
  - reginfo (text) 注册信息 (TIME/IP/APP)
  - ban (datetime) 封锁到时间 (超时和空可登录)
  - alert (text) 重要警告文本 (非空则显示警告信息,可配合ban)
  - jur (int3) *权限ID (kylogin_jur->id)
- kylogin_jur (权限等级表格)
  - id (int3) *权限ID (主键,自增)
  - jname (text16) *权限组名称
  - func (text) 可用功能代号 (逗号分隔符)
- kylogin_safe (账户安全表格,也可作为额外登录手续)
  - hash (text64) *用户哈希
  - qa (text) 密码提示问题和答案 (JSON二维数组)
  - spwd (text64) 二级密码哈希 (密码哈希前半部分哈希+密码哈希后半部分哈希)
- kylogin_activity (已登录表格)
  - hash (text64) *用户哈希
  - app (text32) *应用程序名称
  - timeset (datetime) *令牌生成时间
  - timeend (datetime) *令牌自动失效时间
  - ip (text39) 绑定IP地址
  - token (text64) *访问令牌 (用户哈希+当前时间的哈希)

## 错误代码表

JSON返回格式: {"stat":xxxx,"msg":"..."}

- 1xxx : 操作成功执行
  - 11xx : 数据库操作成功
    - 1100 : SQL语句成功执行。
    - 1101 : SQL语句成功执行，返回0值。
- 2xxx : 操作执行失败
  - 20xx : 参数不足
    - 2000 : No parameters.
    - 2001 : More parameters are needed.
    - 2002 : Invalid parameter.
  - 21xx : 数据库操作失败
    - 2100 : Failed to connect to DB.
    - 2101 : SQL Error.
    - 2102 : Error returning data.
  - 22xx : 字符串检查异常
    - 2200 : Invalid string.
    - 2201 : Incorrect characters.
    - 2202 : Incorrect SQL characters.
    - 2203 : Incorrect HTML characters.