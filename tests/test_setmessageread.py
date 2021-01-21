# -*- coding:utf-8 -*-
import test_core
import sys
import demjson
test_core.title("设置站内信的已读和未读状态")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["url"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["url"]+"nyamessage.php"
udataarr = {
    "token":jsonfiledata["token"],
    "msghash":"ZisdNxnCkvUQBJrRJ4vtQ7NUuBHhCtgd8pgSX7U74rpCQNQZuJXdymVDVrcDRhzJ",
    "readstat":0  # 0 未讀　1 已讀　2 全部已讀
}
test_core.postarray(uurl,udataarr,True)
