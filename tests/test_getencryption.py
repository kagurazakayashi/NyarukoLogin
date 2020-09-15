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

# 設定內建公鑰和私鑰，在本地應用還沒有和伺服器獲取金鑰對之前，使用此預共享金鑰對進行加密。
# 應與伺服器端配置的 `defaultPrivateKey` 相對應。
# 總計 2 個金鑰對，與伺服器分別持有對方的公鑰和私鑰。
# 無需開頭結尾標記，無需修改 base64 內容。
default_public_key = '''-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAq47jTLBnFvlBWu/c7fIG
rsw2vMW0xOF+AnQR8L9+i7Em32KVejbSVeIrISGC+0xIfjgQpkRKYRB+xJag8c6J
QDrHFkdckIvitYc4iiuvmAnTyiOCzLQgH9lVUdJ67+2qK5k3dc9ojhPFVWwpTknx
eX7XdkLOG6Ei0locKwh27YusgBrkkRpAM8EqO6E+19vS7Hr8V1KsrEz/9a232fhf
vqF/F347f/RCwX4Iuu0djYeaS9Je+DtMjqWO47ZUDwgQxhWSXpfjCqxSyi1a+b0b
JCWP2vejXbHVeWaihlzAs+XLNd6Kefq4BGNmK+FH0lT70NWnPMJVpWf0/L3znJrm
v3wVXpeh4uP2TvpucNtjrF8eVMTzc1y/Un2zYF/HBcRlcl8/yJZnCYHOdTqfj0xI
uXgAy/wzDmgfKL+kJ3kLWdCJwdnE2HLS8BWwI07Uq/T2iL3qSAqFcM84Ouv3bZ9B
Ex669h82eEMWp0j0xAB/MIoy93N0nEDU5WijZYK3z044i5lO2utks8wCav5OrT57
zjt3lRoJibBBeZUG/rAKnJ7RQgT/YuuTwRBp9IsTgkU5iwFmCLJ+3n30fgF+Lpsh
uvtl5EyR2WhL/yFjTkocydcWiK/w0y3VDy74Zgsd4M0FL5e7ig17baOEo5PhEYsp
si0uFCjCOiY4lgOH/E+BJVkCAwEAAQ==
-----END PUBLIC KEY-----'''

test_core.title("请求加密密钥")
# 需要提供與資料庫 app 表中記錄的內容
jsonfiledata = test_core.getjsonfiledata(False)
if jsonfiledata["publickey"] == "" or jsonfiledata["privatekey"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
# 建立一個金鑰對
test_core.tlog("正在生成本地密钥对 ...")
rsakeys = RSA.gen_key(4096, 65537)
bio = BIO.MemoryBuffer()
rsakeys.save_key_bio(bio, None)
privatekey = bio.read_all()
rsakeys.save_pub_key_bio(bio)
publickey = bio.read_all()
privatekeymd5 = test_core.md5(test_core.clearkey(str(privatekey, encoding = "utf-8")))
publickeymd5 = test_core.md5(test_core.clearkey(str(publickey, encoding = "utf-8")))
rsakeymd5 = publickeymd5+privatekeymd5
test_core.tlog("生成的本地密钥对哈希: "+rsakeymd5)
# 输出生成的密钥
test_core.tlog("生成的密钥 private key")
test_core.tlog(privatekey)
test_core.tlog("生成的密钥 public key")
test_core.tlog(publickey)
# quit()

publickey = publickey.decode()
# publickey = base64.b64encode(publickey).decode() # 在 POST 模式下可有可无
postData = {
    'appkey': jsonfiledata["appkey"],
    'devtype': "debug",
    'devdevice': platform.architecture(),
    'devos': platform.system(),
    'devosver': platform.platform(),
    'publickey': publickey
}
url = jsonfiledata["url"]+"nyaencryption.php"
resArr = test_core.postarray(
    url, postData, True, default_public_key, privatekey,0)
test_core.tlog("保存到数据文件 ...")
jsonfiledata["publickey"] = resArr['publickey']
jsonfiledata["privatekey"] = privatekey.decode()
jsonfiledata["apptoken"] = resArr['apptoken']
jsonfiledata["updatetime"] = [resArr['time'],
                              resArr['timestamp'], resArr['timezone']]
jsonfiledata["encrypt"] = resArr['encrypt']
lines = json.dumps(jsonfiledata)
f = open("testconfig.json", "w")
f.write(lines)
f.close()
test_core.tok("完成。")
