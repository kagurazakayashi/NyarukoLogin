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
    "to": "cxchope@163.com"
}
test_core.postarray(uurl, udataarr, True)
