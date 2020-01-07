# -*- coding:utf-8 -*-
import test_core
import demjson
import datetime
test_core.title("媒体可以提供的尺寸和格式")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["url"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["url"]+"nyamediafiles.php"
udataarr = {
    'path':"/2020/03/03/23d241520fb0c06282f16d11082b5903"
}
test_core.postarray(uurl,udataarr,True)