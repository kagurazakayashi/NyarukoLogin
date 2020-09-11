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
# 應與伺服器端配置的 `defaultPrivateKey` 和 `defaultPublicKey` 相對應。
# 總計 2 個金鑰對，與伺服器分別持有對方的公鑰和私鑰。
# 無需開頭結尾標記，無需修改 base64 內容。
default_public_key = '''MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAoxQW+mSR06YzhNnw7OqL
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
7uqOKqtlaT9f1rg8ROCKhlsCAwEAAQ=='''
default_private_key = '''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'''

test_core.title("请求加密密钥")
# 需要提供與資料庫 app 表中記錄的內容
jsonfiledata = test_core.getjsonfiledata(False)
if jsonfiledata["publickey"] == "" or jsonfiledata["privateKey"] == "":
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
    url, postData, False, default_public_key, default_private_key)
test_core.tlog("保存到数据文件 ...")
jsonfiledata["publickey"] = base64.b64encode(
    resArr['publickey'].encode()).decode()
jsonfiledata["privateKey"] = base64.b64encode(privatekey).decode()
jsonfiledata["apptoken"] = resArr['apptoken']
jsonfiledata["updatetime"] = [resArr['time'],
                              resArr['timestamp'], resArr['timezone']]
jsonfiledata["encrypt"] = resArr['encrypt']
lines = json.dumps(jsonfiledata)
f = open("testconfig.json", "w")
f.write(lines)
f.close()
test_core.tok("完成。")
