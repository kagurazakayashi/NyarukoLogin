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
uurl = jsonfiledata["url"]+"nyauserinfoedit.php"
udataarr = {
    "token":jsonfiledata["token"],
    "name":"神楽坂雅詩",
    "gender":0,
    "pronoun":0,
    "address":"中国",
    "profile":"我想…像诗意绽放的花朵，选择更璀璨的人生。",
    "description":"你好，这里是神楽坂雅詩。「神楽坂」来自一个已经离开的社交网站中的，一段难忘的故事。"
}
test_core.postarray(uurl,udataarr,True)
