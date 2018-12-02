# -*- coding:utf-8 -*-
# pip install onetimepass
# pip install demjson
import sys
import onetimepass as otp
from urllib import parse,request
import demjson
import time
print("===== 请求加密密钥 =====")
postData = {'n':"testapp1",'s':"mipxT4wpGJ7JD29ZwI87AKmRvvCx19rI"}
postMod = parse.urlencode(postData).encode(encoding='utf-8')
print("↑ 发送请求:")
print(postMod)
postUrl = "http://127.0.0.1/NyarukoLogin/nyatotp.php"
postReq = request.Request(url=postUrl,data=postMod)
postRes = request.urlopen(postReq)
postRes = postRes.read()
postRes = postRes.decode(encoding='utf-8')
print("↓ 收到数据:")
print(postRes)
print("检查返回的数据 ...")
resArr = demjson.decode(postRes)
if resArr['code'] != 1000000:
    print("返回状态码错误。")
totp_secret = resArr['totp_secret']
totp_code = resArr['totp_code']
totp_token = resArr['totp_token']
totp_time = resArr['time']
timeSt = time.time() - int(totp_time)
if (timeSt > 60) or (timeSt < -60):
    print("时间差太大。")
    print(timeSt)
totptoken = otp.get_totp(totp_secret)
if totptoken != totp_code:
    print("生成的动态码不匹配。")
print("完成。")