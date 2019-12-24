# -*- coding:utf-8 -*-
import test_core
import demjson
test_core.title("登录测试")
f = open("totpsecret.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["url"] == "" or jsonfiledata["token"] == "":
    test_core.terr("错误： 'totpsecret.json' 配置不完全。")
    exit()
uurl = jsonfiledata["url"]+"chktoken.php"
udataarr = {
    'token':jsonfiledata["token"],
}
test_core.postarray(uurl,udataarr,True)