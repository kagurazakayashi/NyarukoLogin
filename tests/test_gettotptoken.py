# -*- coding:utf-8 -*-
# pip install onetimepass
# pip install demjson
import sys
import onetimepass as otp
from urllib import parse,request
import demjson
import time
import test_core
import platform
import datetime
test_core.title("请求加密密钥")
# 需要提供与数据库 app 表中记录的内容
test_core.tlog("读入配置文件 ...")
f = open("totpsecret.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["appsecret"] == "" or jsonfiledata["apiver"] == "" or jsonfiledata["url"] == "":
    test_core.terr("错误： 'totpsecret.json' 配置不完全。")
    exit()
postData = {
    'appsecret':jsonfiledata["appsecret"],
    'apiver':jsonfiledata["apiver"],
    'devtype':"debug",
    'devdevice':platform.architecture(),
    'devos':platform.system(),
    'devosver':platform.platform()
}
test_core.tlog("准备要提交的数据:")
test_core.tlog(demjson.encode(postData))
postMod = parse.urlencode(postData).encode(encoding='utf-8')
test_core.tlog("↑ 发送请求:")
test_core.tlog(postMod)
postUrl = jsonfiledata["url"]+"nyatotp.php"
postReq = request.Request(url=postUrl,data=postMod)
postRes = request.urlopen(postReq)
postRes = postRes.read()
postRes = postRes.decode(encoding='utf-8')
test_core.tlog("↓ 收到数据:")
test_core.tlog(postRes)
test_core.tlog("检查返回的数据 ...")
resArr = demjson.decode(postRes)
if resArr['code'] != 1000000:
    test_core.terr("返回状态码错误。")
    exit()
totp_secret = resArr['totp_secret']
totp_code = resArr['totp_code']
totp_token = resArr['totp_token']
totp_timestamp = resArr['timestamp']
timeSt = time.time() - int(totp_timestamp)
if (timeSt > 30) or (timeSt < -30):
    test_core.terr("时间差太大。")
    test_core.tlog(timeSt)
    exit()
totptoken = otp.get_totp(totp_secret)
if totptoken != totp_code:
    test_core.terr("生成的动态码不匹配。")
    exit()
test_core.tlog("保存到数据文件 ...")
jsonfiledata["update"] = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S.%f')
jsonfiledata["totpsecret"] = totp_secret
jsonfiledata["totptoken"] = totp_token
lines = demjson.encode(jsonfiledata)
f = open("totpsecret.json", 'w')
f.write(lines)
f.close()
test_core.tok("完成。")