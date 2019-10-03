# -*- coding:utf-8 -*-
import test_core
test_core.title("登录测试")
uurl = "http://127.0.0.1/NyarukoLogin/chktoken.php"
udataarr = {
    'token':"752f417c8197dd1958ab2ec7cfc7f8ffaf152292fbe173248962012c506d1146",
}
test_core.postarray(uurl,udataarr,True)
