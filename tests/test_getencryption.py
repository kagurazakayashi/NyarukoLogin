# -*- coding:utf-8 -*-
# pip install onetimepass
# pip install demjson
# pip install rsa
import sys
import onetimepass as otp
from urllib import parse, request
import json
import time
import test_core
import platform
import datetime
import base64
import rsa
from rsa import common
from threading import Thread
import time
from M2Crypto import BIO, RSA  # dnf install python3-m2crypto.x86_64 -y
pubkey = ""
privkey = ""

def rsaEncrypt(public_key, message):
    bio = BIO.MemoryBuffer(public_key)
    rsa_pub = RSA.load_pub_key_bio(bio)
    # ctxt64_pri = rsa_pub.public_encrypt(message, RSA.pkcs1_padding)
    # ctxt64_pri = base64.b64encode(ctxt64_pri)

    buffer = None
    while message:
        input = message[:245]
        snidata = rsa_pub.public_encrypt(input, RSA.pkcs1_padding)
        if buffer == None:
            buffer=snidata
        else:
            buffer = buffer+snidata
        message = message[245:]
    ctxt64_pri = base64.b64encode(buffer)

    return ctxt64_pri

def rsaDecrypt(private_key, message):
    bio = BIO.MemoryBuffer(private_key)
    rsa_pri = RSA.load_key_bio(bio)
    buffer = None
    while message:
        input = message[:512]
        snidata = rsa_pri.private_decrypt(input, RSA.pkcs1_padding)
        if buffer == None:
            buffer=snidata
        else:
            buffer = buffer+snidata
        message = message[512:]
    return buffer
    # ctxt_pri = message
    # output = rsa_pri.private_decrypt(ctxt_pri, RSA.pkcs1_padding)
    # return output


# 設定內建公鑰和私鑰，在本地應用還沒有和伺服器獲取金鑰對之前，使用此預共享金鑰對進行加密。
# 應與伺服器端配置的 `defaultPrivateKey` 和 `defaultPublicKey` 相對應。
# 總計 2 個金鑰對，與伺服器分別持有對方的公鑰和私鑰。
# 無需開頭結尾標記，無需修改 base64 內容。
default_public_key = '''MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEApS7SdQ7o9wWyS7TtkDte
jRPeKWVhJxyDNKgSxK4lUpX4Z9qJ/EczQiZnxTOR04/l5DvH4w7qRZkvjQpvE/1/
kCP3kA9jAMQDSvquEgbzd0KnrmPat3NDmGxVOUGJlAVkLf0JU/e/CJCILPDtX1mD
x0biEXvzLTWqzASOhfLTK4Zx8h0YxnapIh9EwGWSYyCl7oeP5iuPcgnfTa/HnGct
fnAtMIVOLFvWDz3ywSlCnWcPkeLGvVLtDeptLHkKpdM7b5QACOFlxTbZmsxc29ny
SFCVPNQr2ZfKGHyq3DM2z3Hkv+h5UlB0sBIoIm2GYvA9NtDYeU1NXQtMGMZ0TyvX
sGGIobLpJmSbNxgFnaSXIrlHiiCuF2d4G3vCPLv8fDp6gUUt6VFxjwrZL2sEM0cr
JsM6ASr/TJSayybcSRBB1oaJUuznCMi0Qkr3VZl1aG2Jb+Mju9R0Z2Px4jphZVLp
2kZKzijJVwuS6yu1bYoCzFoOzc56as8jbaFQm2qE1j6QfZxaI82Pi6WfONmsT6rx
yDxzlohLAFtCMDJINRjwIXjP9/6NyjhbPHVmEAoGCOYt2LNgu3mogXhhFWChW1vx
6bvgwSxiDnMPWFdjpJwNu6Ode7BF1+7dIfeZViVO+EdYWzhDT2UcBEmqfzvvsLJn
yI+Xnerp986x1XpBYzLaVsECAwEAAQ=='''
default_private_key = '''MIIJRAIBADANBgkqhkiG9w0BAQEFAASCCS4wggkqAgEAAoICAQCf+tzEQejLquWo
ZYp/oA7fCYZpdRhn5o8N7PuIHa8cwFtvNeJ4OXYkah378NykdLQM8ZAJQpFFV+UP
MxVuiUysmwBA+m/D9S0P1Ti61OUIv1v6HCJoEttbPz9CBpB6/Qb6pttzfdcbxtvf
44nEslxPmkyksujwH+GF463WtPxANzxi88R2we+Rd8+SS0/cX5ByBQhoCTFN18ZF
nwwLyaUzjYdfF6VWTFZwF5SAAEyeedhr2TdJ2GVjC0c+F50LgeL6F/otf+g9tB6m
09dQMN5ukWg6DXqyDTaMqWrrCSWY/fh16Uo4NlZSCSXngscOlboiXmn/6x4UiJ1a
uGfCEf7/bf0Wk5GCg/fPn+VMTjAxPMsKrjhmW7WfBi25LQQffnBusec07AhWtGMl
Nmf30DjrCjHvkJxTy18rfzyXP7kvc7HqFZrEKPlv4IzMU5+JpVC9wClG4w65haW3
2VVBVBhnqesNwTOBURujZZXIp/GMBmabQCaqX5rfTFCdqMc0WaZ+XryYuNF4d3YC
Tb5qGPcjOTFTxamGkELMPM10q3pBbWc2ffzgcKF4vtewRfvoA4x+2QwCgLvgswVx
xLkItGg3h0sIvPlS8bp1/Lx8ty8QzflIz0/hWtp2YVkLnvmNYVCk4/4fna21uI8o
JlObNU6GQm6wU2Y1jFKyqQ/rZONz/QIDAQABAoICAQCE/xWXZr+0ya9BDqFUNmkG
jLGcbEdM4USeLBxz7VGN0nBxBNfwYLzRyxKAR532bjrc49l9iUSh5E8+EpbHnQcM
G5PbcReHTgUXO4h88c4yNnutcsQ7xylrMypQpopoZH/v0tVvrvcsVWanO09mIW1S
lBERftbeAXMITt5PN4jZjdH65XK308RpJ7R9h3St3RiFYZ+6daHyd+aSLIXkKs/X
Ctq2eD7wfmgBqei/fQA1Y1BDYIJxNqAa9d5VRWY+kc1zYomOV95npZ0xzb0bInVY
cDO4x0nwScHGsHV9Tc5e4Qw0YixcjPe4HXjQfU0k1961Ac6Xfgw0v9oo+FLx6OoW
eQBhwOMZNpSz0tXdjWzduMAhbz7UOLC3XlLKaDZWTpWkUzVrTEQcA9+wJA9jw95n
uVX8jTw143LIeTyMXogi3BfE9eIudm5dl07q8FCQ1NqydC94cfU4oaZ37YnwY4WG
N9eMVpInF0/w9fFmc4mOh2Txaaj1WIMAY9AZcEdFksmEF1/Ef85p8jJq5yk330yH
43PB2XX8DQ3U+hTmCISOUlA/LgAhC57+xZqF+qcFcc7aehCDR+sjlwKZVj/IORdx
+KoS3A3F2tey449XqCxIvfEV1B0LKmT/n28bW57GxkeIJ2mWHcYSHXHi6dnIVbCa
6dAwoyVZe4LZeppa4TRJQQKCAQEAzZLYgHSJgzcPeZigtv58DWKve1DijlEoXbI9
1xFkU/ABkuYg9Nbnjqt4l2xg6AFInTBFBEXBCpUzrJv7SfUfrK4zLQCwxdfBygCL
CfwwLBAirOWBjocCfO7yJoTBFKI+n26oGpqM46qwfAGpGYaV/VsQM1UMXFBzHCHh
NGBfpNvrLF56fQYmJ2DYd+01WAOwusFRbfTqW444JhxYUNXN7H/vI2yqBHsLC7Yi
bXfsBVIshaL+pC0Gfq05BV2oSbL74OLO1nAd3MfhPPSpVnHUIxvIOm3JFP8HXjRk
9eDf1s6v+8yhrP5gTmJ3O7wGDzA3p55QqGWnlQt8b6qZptE0UQKCAQEAxzjtChU7
wR5gECa2Vc9CdpRqS0Z83VSs8TLqhqzOn67gzw++t+PfBhZOau7A3/Jgmk2v7Vu7
WzRM4Pe+d40lW4YW5UKp9/4H4cw3qxUTlAvw7kINhh82e4f/oP9gLBzX/IbzFNwh
Eo78iTJoL0OeUepLxQjqlxxQiYMftWLhRTJKTl4xocG38XCBhRe+NRsggsV7ZjOq
aiPDlDWLH9EBUOYRn3C4unVr4047PdavmimhYwKO2ieuKaiqrYsmoyjrc637Fmh+
znM/R0C2koW42nbKGynimPBk/047HOtG7ibdiS6Otn/2NP1ll7B/v61k5e6Kwh/l
j1wgNpsDkT117QKCAQEAyAsjvyyoPBWya2NI0Yom8Wec1+VH8bGsFDFE2CpGdR/S
iD6Ex3zw6/aUbNW8H0hQfxXfs16lkUTLeiZ3i+qKniEOD7biTkO8gnOw2VCdF+PD
unfDcQyUMTrcTPtWYFvZkI+/6dOHUmxhPOrowLwk7NxWkX0rgBGRUkjtu2gk6mpP
GWf+zfbT3fHx9BHSnQlKSxGfyd5QyDNHFF702gSUVJXSAajWcXHCt+zO57OQ60Sh
qpGINf7SmSa7nCsdfko7rQVIvYdwp82hsw1qU4KFKnz4hVMcH+BkLRnTPSlM6N1M
hz0D/zGySdSrrmczhkgngGcgmh4By5nXx1o+FwTxYQKCAQBu1ffijS+iYHDXa4Iz
uUxjxmxglsWvKYXR5x7dcFTAOD3wVPUpP4h7zXsxOMVrkmA2WJRQ/KD+u9ktrAd2
Ybtv8OjaAb2oL9dYwxIRh3dN/Vq8Y1k8zi60iVQp0QpDYIS1hKrGZzWrKovLFJUr
/nWdYzlmVHCXQ3R3HNFOS8XChpkagoMgWDMrCwmz7yKuTvpGZvIhSQs9Y8pSsXtG
ZzRROr6BMuhDLEKqwoJpBADMRu73s5ugOUVG/gR+6pTDX2enAxtXOymZWDrTPGiW
mfje6gnFFQdxSQUXmQGANFFQ+6Ryxsk1NXnXv0Z/Hv3juOcSmSTvnWlM2dc+Kf3R
Fx19AoIBAQCDatpN2tz0PcFCDbGVOtXeBQYLk5DVn28APfotJkjNYmVXmLawElxZ
lKx+4FPqTJ5i8ccopQRco7fPi6Kh5qg0bXWTBKdRt4Zt25k/qgZK9Y5+uhP8NFTC
dXCdBO7zDIJt3Q94DxbQYv84kNMDMzwbkLojq1NcnU6dpZEoMrv/IFA6fWVeZQPh
9et8NFp/LzYlleXopluhm1fZr80B6HOTFEZadimvggt9IEIsd/iLdpGDAVC0ge5d
RuB8Uas+Y0tPh9hiUvpeqUvj1Pi4PskA/n/Nw4z+pe71ZwvSLaduNyL/HUgUfhsa
IXbV4A0zGPnwi7zbOCOZ0VSgYnlNmmAF'''
test_core.title("请求加密密钥")
# 需要提供與資料庫 app 表中記錄的內容
test_core.tlog("读入配置文件 ...")
f = open("testconfig.json", "r")
lines = f.read()
f.close()
jsonfiledata = json.loads(lines)
if jsonfiledata["appkey"] == "" or jsonfiledata["apiver"] == "" or jsonfiledata["url"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
# 準備預共享的金鑰
test_core.tlog("准备预共享密钥对 ...")
default_public_key = "-----BEGIN PUBLIC KEY-----\n" + \
    default_public_key+"\n-----END PUBLIC KEY-----"
default_private_key = "-----BEGIN RSA PRIVATE KEY-----\n" + \
    default_private_key+"\n-----END RSA PRIVATE KEY-----"
# 建立一個金鑰對
test_core.tlog("正在生成本地密钥 ...")
rsakeys = RSA.gen_key(4096, 65537)
bio = BIO.MemoryBuffer()
rsakeys.save_key_bio(bio, None)
privatekey = bio.read_all()
rsakeys.save_pub_key_bio(bio)
publickey = bio.read_all()

publickey = publickey.decode()
# 在 POST 模式下，这两步可有可无
# publickey = base64.b64encode(publickey).decode()
# publickey = publickey.replace('+', '-').replace('/', '_').replace('=', '')
##

postData = {
    'appkey': jsonfiledata["appkey"],
    'apiver': jsonfiledata["apiver"],
    'devtype': "debug",
    'devdevice': platform.architecture(),
    'devos': platform.system(),
    'devosver': platform.platform(),
    'publickey': publickey
}
# 加密模式
test_core.tlog("[加密模式] 准备要提交的数据:")
jsondata = json.dumps(postData)
test_core.tlog(jsondata)
jsondata=str.encode(jsondata)
print(jsondata)
# jsondata = bytes(jsondata, encoding = "utf8")
test_core.tlog("正在加密数据 ...")
default_public_key = str.encode(default_public_key)

postData = {
    'd':rsaEncrypt(default_public_key,jsondata)
}

# encDataArr = []
# while jsondata:
#     jsonChunk = jsondata[:501] # keylength / 8 - 11
#     print("原文行",len(jsonChunk),jsonChunk)
#     rsaLine = rsaEncrypt(default_public_key,jsonChunk)
#     encDataArr.append(rsaLine)
#     print("加密行",len(rsaLine),rsaLine)
#     jsondata = jsondata[501:]
# postData = {
#     'd': b','.join(encDataArr)
# }

##

# 明文模式
# test_core.tlog("[明文模式] 准备要提交的数据:")
# test_core.tlog(json.dumps(postData))
##

postMod = parse.urlencode(postData).encode(encoding='utf-8')
test_core.tlog("↑ 发送请求:")
# test_core.tlog(postData)
postUrl = jsonfiledata["url"]+"nyaencryption.php"
postReq = request.Request(url=postUrl, data=postMod)
postRes = request.urlopen(postReq)
postRes = postRes.read()

test_core.tlog("↓ 收到数据:")
postRes = postRes.decode(encoding='utf-8')
test_core.tlog(postRes)
test_core.tlog("解密数据 ...")
postRes = postRes.replace('-','+').replace('_','/')
mod4 = len(postRes) % 4
if mod4:
    postRes += "===="[0:4-mod4]
postRes = bytes(postRes, encoding = "utf8")
print(postRes.decode())
postRes = base64.b64decode(postRes)
default_private_key = str.encode(default_private_key)
postRes = rsaDecrypt(default_private_key,postRes)

test_core.tlog("检查返回的数据 ...")

try:
    resArr = json.loads(postRes)
except:
    test_core.terr("返回数据错误。")
    exit()
if resArr['code'] != 1000000 and resArr['code'] != 1000100:
    test_core.terr("返回状态码错误。")
    exit()
test_core.tlog("保存到数据文件 ...")
jsonfiledata["publickey"] = base64.b64encode(
    resArr['publickey'].encode()).decode()
jsonfiledata["privateKey"] = base64.b64encode(privatekey).decode()
jsonfiledata["apptoken"] = resArr['apptoken']
jsonfiledata["updatetime"] = [resArr['time'],
                              resArr['timestamp'], resArr['timezone']]
jsonfiledata["encrypt"] = resArr['encrypt']
print(jsonfiledata)
lines = json.dumps(jsonfiledata)
f = open("testconfig.json", "w")
f.write(lines)
f.close()
test_core.tok("完成。")
