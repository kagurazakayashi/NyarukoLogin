# -*- coding:utf-8 -*-
import test_core
test_core.title("登录测试")
uurl = "http://127.0.0.1/NyarukoLogin/nyalogin.php"
udataarr = {
    'user':"testmail@uuu.moe",
    'password':"testpassword"
}
test_core.postarray(uurl,udataarr,True)
