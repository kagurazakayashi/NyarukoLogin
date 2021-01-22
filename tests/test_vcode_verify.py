# -*- coding:utf-8 -*-
import test_core
import sys
import demjson
test_core.title("检查邮件或短信验证码是否正确并获取临时令牌")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["url"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["url"]+"nyavcode.php"
udataarr = {
    "apiver": 2,
    "vcode": "573702",
    "type": "mail"
}
test_core.postarray(uurl, udataarr, True)
