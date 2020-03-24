# -*- coding:utf-8 -*-
import test_core
import sys
import demjson
import random
import string
test_core.title("注册子账户")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["url"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["url"]+"nyastand.php"
salt = ''.join(random.sample(string.ascii_letters + string.digits, 16))
udataarr = {
    "token":jsonfiledata["token"],
    "nickname":"小雅诗"+salt
}
test_core.postarray(uurl,udataarr,True)
