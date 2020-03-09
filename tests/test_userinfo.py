# -*- coding:utf-8 -*-
import test_core
import sys
import demjson
test_core.title("用户资料")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["url"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["url"]+"nyauserinfo.php"
udataarr = {
    "token":jsonfiledata["token"]
}
test_core.postarray(uurl,udataarr,True)
