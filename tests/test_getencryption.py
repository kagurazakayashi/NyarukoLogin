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
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAoxQW+mSR06YzhNnw7OqL
ZSVKa/Cil/7iMrO2GTO4Ri7T1ordAec9dLxkZ+2vA7KVFU9uWxhaoKG3VOukjPbJ
4KK+1dB5f7PG+h2kGOVMPqlTQZDQHEspfqCX7UJrr2nc48k6IVNmAe+NjDiqs6k/
FNmtZ942ugUo3ktXQk2q9SGLtlrlGxNs5Ulguhbd3l+E/sRc52BQZIiQZIdyh7A3
vZ/x5PuiytQhp+p4M2ufTPdM/crsGX+fxaLsJS9U1/xTDhoqc3C04Ht7V0vJircu
UobF/U3fohtUPdVWpEddyOXmXT7UrZNsGTajyjF5bse8lV3jHxymdu8v+TCZPJNt
nKOK/c5lDXiwK4vs6QjcMPSa3GsCI/MStJIagAc8TGMeAHk0luxotEqsVne+VNfN
cbD4uKBykGVs6JcXIFur4Eha5vyza1sIHWxDYmq6qxIGz+0V5OGTsc6DhmIuNoPk
bKPx2UX4vKzEV8SEHlexrVAdQnmw9EusaB3l82JadJIDUZWVgixpq2t3p4tcnuOG
jx78YHr493tJKzeMEPd+B9DnZ+IL5S8VyLrzcqHfHrywBMiPuIj0ay7Q7DmkY04b
0azf8xTrxmX35SCmSfo/Amxh2cazb2wYpiWUryN4qF8Vovg2qdfXupLcAbgiS19g
7uqOKqtlaT9f1rg8ROCKhlsCAwEAAQ==
-----END PUBLIC KEY-----'''

test_core.title("请求加密密钥")
# 需要提供與資料庫 app 表中記錄的內容
jsonfiledata = test_core.getjsonfiledata(False)
if jsonfiledata["publickey"] == "" or jsonfiledata["privateKey"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
# 建立一個金鑰對
test_core.tlog("正在生成本地密钥 ...")
rsakeys = RSA.gen_key(4096, 65537)
bio = BIO.MemoryBuffer()
rsakeys.save_key_bio(bio, None)
privatekey = bio.read_all()
rsakeys.save_pub_key_bio(bio)
publickey = bio.read_all()
# 输出生成的密钥
# test_core.tlog("privatekey")
# test_core.tlog(privatekey)
# test_core.tlog("publickey")
# test_core.tlog(publickey)
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
jsonfiledata["privateKey"] = privatekey.decode()
jsonfiledata["apptoken"] = resArr['apptoken']
jsonfiledata["updatetime"] = [resArr['time'],
                              resArr['timestamp'], resArr['timezone']]
jsonfiledata["encrypt"] = resArr['encrypt']
lines = json.dumps(jsonfiledata)
f = open("testconfig.json", "w")
f.write(lines)
f.close()
test_core.tok("完成。")
