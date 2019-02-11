# -*- coding:utf-8 -*-
import test_core
test_core.tlog("===== 获得验证码测试 =====")
uurl = "http://127.0.0.1/NyarukoLogin/nyacaptcha.php"
udataarr = {
}
test_core.postarray(uurl,udataarr,True)