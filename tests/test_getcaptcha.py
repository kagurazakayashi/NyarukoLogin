# -*- coding:utf-8 -*-
import test_core
import webbrowser

test_core.title("获得验证码测试")
uurl = "http://127.0.0.1/NyarukoLogin/nyacaptcha.php"
udataarr = {
}
img = test_core.postarray(uurl,udataarr,True)["img"]
webbrowser.open(img)