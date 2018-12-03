# -*- coding:utf-8 -*-
import sys
import onetimepass as otp
from urllib import parse,request
import demjson
import xxtea
import base64
import re
print("===== 加密 I/O 测试 =====")
jsonDataArr = {'testkey1':"testval1",'testkey2':"testval2"}
print("读取 totpsecret.tmp ...")
totpsecret = ""
totptoken = ""
try:
    f = open('totpsecret.tmp', 'r')
    filejson = f.read().rstrip('\n')
    print(filejson)
    filedataarr = demjson.decode(filejson)
    totpsecret = filedataarr["totp_secret"]
    totptoken = filedataarr["totp_token"]
except:
    print("不能打开文件「totpsecret.tmp」，先运行「python gettotptoken.py」来获取返回的 JSON，确保没有错误信息，然后将 JSON 保存到「totpsecret.tmp」")
finally:
    if f:
        f.close()
print("JSON 编码 ...")
jsonstr = demjson.encode(jsonDataArr)
print(jsonstr)
print("生成 TOTP 代码 ...")
print(totpsecret)
totpcode = otp.get_totp(totpsecret)
print(totpcode)
print("XXTEA 加密 ...")
encryptdata = xxtea.encrypt(jsonstr, str(totpcode))
print(encryptdata)
print("base64 编码 ...")
base64str = base64.b64encode(encryptdata).decode()
print(base64str)
print("base64 变体 ...")
postStr = base64str.replace('+','-').replace('/','_').replace('=','')
print(postStr)
print("准备 POST 数据 ...")
postData = {'t':totptoken,'j':postStr}
postMod = parse.urlencode(postData).encode(encoding='utf-8')
print("↑ 发送请求:")
print(postMod.decode())
postUrl = "http://127.0.0.1/NyarukoLogin/tests/encrypttest.php"
postReq = request.Request(url=postUrl,data=postMod)
postRes = request.urlopen(postReq)
postRes = postRes.read()
postRes = postRes.decode(encoding='utf-8')
print("↓ 收到数据:")
print(postRes)
print("检查返回数据合法性 ...")
matchObj = re.match(r"/[^0-9A-Za-z\-_]/", postRes, flags=0)
if matchObj:
    print("收到了非预期的数据，中止。")
    sys.exit()
print("base64 撤销变体 ...")
base64str = postRes.replace('-','+').replace('_','/')
mod4 = len(postRes) % 4
if mod4:
    base64str += "===="[0:4-mod4]
print(base64str)
print("base64 解码 ...")
encryptdata = base64.b64decode(base64str)
print(encryptdata)
print("生成 TOTP 代码 ...")
print(totpsecret)
totpcode = otp.get_totp(totpsecret)
print(totpcode)
print("XXTEA 解密 ...")
jsonstr = xxtea.decrypt_utf8(encryptdata, str(totpcode))
print(jsonstr)
print("JSON 解析 ...")
dataarr = demjson.decode(jsonstr)
print(dataarr)
print("完成。")