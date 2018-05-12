# NyarukoLogin 2

- 注意：这个程序尚未做完，请勿使用。
- 要查看旧版本代码和此处的说明，请前往 v2016_expired 分支。

## 数据库结构

*为必须

- `nyalogin_user` (用户基本信息表格)
  - 见 `src/nyauser.class.php` 文件。
- `nyalogin_jur` (权限等级表格)
  - 见 `src/nyajur.class.php` 文件。
- `nyalogin_safe` (账户安全表格,也可作为额外登录手续)
  - 见 `src/nyamodsafe.class.php` 文件。
- `nyalogin_activity` (已登录表格)
  - 见 `src/nyamodactivity.class.php` 文件。

## 错误代码表

- JSON返回格式: `{"stat":xxxx,"msg":"..."}` 。
- 返回值及其对应的解释内容见 `nyainfomsg.class.php` 。

## tests/
- `sqlconnect.php` 测试数据库连接是否正常。