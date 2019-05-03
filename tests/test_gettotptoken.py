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
test_core.title("请求加密密钥")
# 需要提供与数据库 external_app 表中记录的内容
postData = {
    'appsecret':"vbCxaCOZL36G5EamUIbKC9ABk4aj8L9CTxBrcaJdrdukZJU3PrZs1oAh2UNkK0nW",
    'devtype':"debug",
    'devdevice':platform.architecture(),
    'devos':platform.system(),
    'devosver':platform.platform()
}
postMod = parse.urlencode(postData).encode(encoding='utf-8')
test_core.tlog("↑ 发送请求:")
test_core.tlog(postMod)
postUrl = "http://127.0.0.1/NyarukoLogin/nyatotp.php"
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
if (timeSt > 60) or (timeSt < -60):
    test_core.terr("时间差太大。")
    test_core.tlog(timeSt)
    exit()
totptoken = otp.get_totp(totp_secret)
if totptoken != totp_code:
    test_core.terr("生成的动态码不匹配。")
    exit()
test_core.tok("完成。")
