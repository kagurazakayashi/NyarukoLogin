# -*- coding:utf-8 -*-
import test_core
test_core.title("登录测试")
uurl = "http://127.0.0.1/NyarukoLogin/nyalogin.php"
udataarr = {
    'testkey1':"testval1",
    'testkey2':"testval2"
}
test_core.postarray(uurl,udataarr,True)
