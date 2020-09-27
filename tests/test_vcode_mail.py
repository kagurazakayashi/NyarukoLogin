# -*- coding:utf-8 -*-
import test_core
import sys
import demjson
test_core.title("获取邮件验证码")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["url"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["url"]+"nyavcode.php"
udataarr = {
    "to": "cxchope@163.com",
    "captcha": sys.argv[1]  # 请先运行 test_getcaptcha.py 获取验证码，并将验证码作为参数输入
}
test_core.postarray(uurl, udataarr, True)
