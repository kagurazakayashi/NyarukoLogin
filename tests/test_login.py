# -*- coding:utf-8 -*-
import test_core
import demjson
import datetime
test_core.title("登录测试")
f = open("totpsecret.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["url"] == "":
    test_core.terr("错误： 'totpsecret.json' 配置不完全。")
    exit()
uurl = jsonfiledata["url"]+"nyalogin.php"
udataarr = {
    'user':"testmail@uuu.moe",
    'password':"testpassword",
    'ua':"test"
}
dataarr = test_core.postarray(uurl,udataarr,True)
jsonfiledata["update"] = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S.%f')
jsonfiledata["token"] = dataarr["token"]
lines = demjson.encode(jsonfiledata)
f = open("totpsecret.json", 'w')
f.write(lines)
f.close()