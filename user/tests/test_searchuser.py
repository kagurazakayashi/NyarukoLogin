# -*- coding:utf-8 -*-
import test_core
import sys
test_core.title("搜索用户")
uurl = "http://127.0.0.1/NyarukoLogin/search.php"
udataarr = {
    'type': "username",
    'word': sys.argv[1]
}
test_core.postarray(uurl,udataarr,True)
