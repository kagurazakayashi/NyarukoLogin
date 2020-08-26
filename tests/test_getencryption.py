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
import base64
test_core.title("请求加密密钥")
# 需要提供与数据库 app 表中记录的内容
test_core.tlog("读入配置文件 ...")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["appsecret"] == "" or jsonfiledata["apiver"] == "" or jsonfiledata["url"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
publickey = b'-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAts5Z5Q6AA0RTwm8UZgt7\ncC2zFIzSYuEJz+ZjUUKuQI5UTI53L2x/zvr7TogPVho9EEmd9VqLBZqFQA1C0+hf\nZmArOyYuJna2t+AXaTCWR5txRyTCKaXmMxcwTMNsoJdW2iJssJ5Cd9OJv+v7X/QW\njDSJz7NxiFOQ48t4F51fG72sl5G7NjhsVF9bsOxFtQUh8AN3z7hjbfCRAgn7dFM9\nPyCYOowXWEGmK6VVuan8LYgOdJO5lH0hmAsVLDVSOpJf8TxcUpHtC7p+FxM3ePOh\nfRQRjztXXuG/Il8RG25HeNcyQZh0okPnfv8x2fo28NZf3wj72L6JxRDy4OSJ0ldl\nbyO2TCxnB/xW+bFTNHGUfrDG1l4/ZgdzhjPna9HPPjuLDRPZteNoZyJgWoRwzydb\nXNs9YB7MRqINGfvGESEGyNo8MHHIqN6pekpSpyQZB7pBlfsviRsXsBbeZmuz82ga\nHQsD/xvzGkKIsg4v+iqXK6O/Fkk6cO4Tz32J3KevbyHaujCdUXpehMl4STmwbMX1\neS+58qoUf+JuAqWkRs7D5f44b+F2cUinxEQlFRbxj5HKH1zE55aE+MRyAmU9TccW\niXYQDElpaY+U0JMmmvBpuFItlPGAJIGf72fAFqu0zfbA/7bTMcOexIDNuQVleEpu\nJx7B1hkr3bT5L9nhIzE73YkCAwEAAQ==\n-----END PUBLIC KEY-----\n'
publickey = base64.b64encode(publickey).decode()
publickey = publickey.replace('+','-').replace('/','_').replace('=','')
postData = {
    'appsecret':jsonfiledata["appsecret"],
    'apiver':jsonfiledata["apiver"],
    'devtype':"debug",
    'devdevice':platform.architecture(),
    'devos':platform.system(),
    'devosver':platform.platform(),
    'publickey':publickey
}
test_core.tlog("准备要提交的数据:")
test_core.tlog(demjson.encode(postData))
postMod = parse.urlencode(postData).encode(encoding='utf-8')
test_core.tlog("↑ 发送请求:")
test_core.tlog(postMod)
postUrl = jsonfiledata["url"]+"nyaencryption.php"
postReq = request.Request(url=postUrl,data=postMod)
postRes = request.urlopen(postReq)
postRes = postRes.read()
postRes = postRes.decode(encoding='utf-8')
test_core.tlog("↓ 收到数据:")
test_core.tlog(postRes)
# test_core.tlog("检查返回的数据 ...")
# resArr = demjson.decode(postRes)
# if resArr['code'] != 1000000:
#     test_core.terr("返回状态码错误。")
#     exit()
# totp_secret = resArr['totp_secret']
# totp_code = resArr['totp_code']
# totp_token = resArr['totp_token']
# totp_timestamp = resArr['timestamp']
# timeSt = time.time() - int(totp_timestamp)
# if (timeSt > 30) or (timeSt < -30):
#     test_core.terr("时间差太大。")
#     test_core.tlog(timeSt)
#     exit()
# totptoken = otp.get_totp(totp_secret)
# if totptoken != totp_code:
#     test_core.terr("生成的动态码不匹配。")
#     exit()
# test_core.tlog("保存到数据文件 ...")
# jsonfiledata["update"] = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S.%f')
# jsonfiledata["totpsecret"] = totp_secret
# jsonfiledata["totptoken"] = totp_token
# lines = demjson.encode(jsonfiledata)
# f = open("testconfig.json", 'w')
# f.write(lines)
# f.close()
# test_core.tok("完成。")