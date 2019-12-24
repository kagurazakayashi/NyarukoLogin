# -*- coding:utf-8 -*-
import test_core
import demjson
test_core.title("加密 I/O 测试")
f = open("totpsecret.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["url"] == "":
    test_core.terr("错误： 'totpsecret.json' 配置不完全。")
    exit()
uurl = jsonfiledata["url"]+"tests/conntest.php"
udataarr = {
    'testkey1':"testval1",
    'testkey2':"testval2"
}
test_core.postarray(uurl,udataarr,True)