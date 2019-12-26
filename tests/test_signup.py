# -*- coding:utf-8 -*-
import test_core
import webbrowser
import sys
import demjson
test_core.title("注册用户测试")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["url"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
udataarr = {}
udataarr["user"] = test_core.instr("请输入邮箱或手机号码(默认值 test@test.com): ")
if (udataarr["user"] == ""): udataarr["user"] = "testmail@uuu.moe"
test_core.tlog("用户名: "+udataarr["user"])
udataarr["password"] = test_core.instr("请输入新密码(默认值 testpassword): ")
if (udataarr["password"] == ""): udataarr["password"] = "testpassword"
test_core.tlog("密码: "+udataarr["password"])
udataarr["nickname"] = test_core.instr("请输入昵称(默认值 测试用户): ")
if (udataarr["nickname"] == ""): udataarr["nickname"] = "测试用户"
test_core.tlog("昵称: "+udataarr["nickname"])
test_core.tlog("正在申请验证码 ...")
uurl = jsonfiledata["url"]+"nyacaptcha.php"
udataarr2 = {}
img = test_core.postarray(uurl,udataarr2,True)["img"]
test_core.tlog("在浏览器中打开验证码图像: "+img)
webbrowser.open(img)
udataarr["captcha"] = test_core.instr("请输入验证码(默认值 0000): ")
if (udataarr["captcha"] == ""): udataarr["captcha"] = "0000"
test_core.tlog("验证码: "+udataarr["captcha"])
uurl = jsonfiledata["url"]+"nyasignup.php"
test_core.postarray(uurl,udataarr,True)
