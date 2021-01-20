# -*- coding:utf-8 -*-
import test_core
import sys
import demjson
test_core.title("获取站内信")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["url"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["url"]+"nyamessage.php"
udataarr = {
    "token": jsonfiledata["token"],
    "mode": 2,  # 0 重要未讀　1 普通未讀　2 所有未讀　3 已讀　? 所有
    "onlylen": 0,
    "limit": 0,
    "offset": 10
}
test_core.postarray(uurl, udataarr, True)
