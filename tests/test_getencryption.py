# -*- coding:utf-8 -*-
# pip install onetimepass
# pip install demjson
# pip install rsa
import sys
import onetimepass as otp
from urllib import parse, request
import demjson
import time
import test_core
import platform
import datetime
import base64
import rsa
from threading import Thread
import time
test_core.title("请求加密密钥")
# 需要提供与数据库 app 表中记录的内容
test_core.tlog("读入配置文件 ...","r+")
f = open("testconfig.json")
lines = f.read()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["appkey"] == "" or jsonfiledata["apiver"] == "" or jsonfiledata["url"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
# 创建一个密钥对
wtxt = "正在生成本地密钥，这需要一些时间..."
test_core.tlog(wtxt)
pubkey = ""
privkey = ""
def workt():
    global pubkey
    global privkey
    (pub, pri) = rsa.newkeys(4096)
    pubkey = pub
    privkey = pri
t = Thread(None,workt)
t.start()
wtxti = 0
while 1:
    if (pubkey != ""):
        break
    wtxti += 1
    print("%s ( %i )" % (wtxt, wtxti))
    time.sleep(1)
publickey = pubkey.save_pkcs1()
privatekey = privkey.save_pkcs1()
publickey = base64.b64encode(publickey).decode()
publickey = publickey.replace('+', '-').replace('/', '_').replace('=', '')
postData = {
    'appkey': jsonfiledata["appkey"],
    'apiver': jsonfiledata["apiver"],
    'devtype': "debug",
    'devdevice': platform.architecture(),
    'devos': platform.system(),
    'devosver': platform.platform(),
    'publickey': publickey
}
test_core.tlog("准备要提交的数据:")
test_core.tlog(demjson.encode(postData))
postMod = parse.urlencode(postData).encode(encoding='utf-8')
test_core.tlog("↑ 发送请求:")
test_core.tlog(postMod)
postUrl = jsonfiledata["url"]+"nyaencryption.php"
postReq = request.Request(url=postUrl, data=postMod)
postRes = request.urlopen(postReq)
postRes = postRes.read()
postRes = postRes.decode(encoding='utf-8')
test_core.tlog("↓ 收到数据:")
test_core.tlog(postRes)
test_core.tlog("检查返回的数据 ...")
try:
    resArr = demjson.decode(postRes)
except:
    test_core.terr("返回数据错误。")
    exit()
if resArr['code'] != 1000000:
    test_core.terr("返回状态码错误。")
    exit()
test_core.tlog("保存到数据文件 ...")
jsonfiledata["publickey"] = resArr['publickey']
jsonfiledata["apptoken"] = resArr['apptoken']
jsonfiledata["updatetime"] = [resArr['time'],resArr['timestamp'],resArr['timezone']]
jsonfiledata["encrypt"] = resArr['encrypt']
lines = demjson.encode(jsonfiledata)
f.write(lines)
f.close()
test_core.tok("完成。")