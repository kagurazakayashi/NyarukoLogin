# -*- coding:utf-8 -*-
import test_core
import webbrowser
import demjson
test_core.title("获得验证码测试")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["url"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["url"]+"tests/nyacaptcha.php"
udataarr = {
}
img = test_core.postarray(uurl,udataarr,True)["img"]
webbrowser.open(img)
