# -*- coding:utf-8 -*-
import sys
from urllib import parse, request, error
import base64
import re
import datetime
import os
import json
from M2Crypto import BIO, RSA  # dnf install python3-m2crypto.x86_64 -y
import hashlib
import traceback

def getjsonfiledata(encrypt: "检查是否已经获取密钥对" = True):
    """读入配置文件 testconfig.json ，请先配置它，并先执行 test_gettotptoken.py 。"""

    # tlog("读入配置文件 ...")
    f = open("testconfig.json", 'r')
    lines = f.read()
    f.close()
    jsonfiledata = json.loads(lines)
    if jsonfiledata["apiver"] == "" or jsonfiledata["url"] == "":
        terr("错误： 'testconfig.json' 配置不完全。")
        exit()
    if encrypt and (jsonfiledata["publickey"] == "" or jsonfiledata["privatekey"] == ""):
        terr("错误： 需要一个初始的密钥对。")
        exit()
    return jsonfiledata


def rsaEncrypt(public_key: "公钥", message: "要加密的信息", showAllInfo=True):
    """RSA 加密"""
    bio = BIO.MemoryBuffer(public_key)
    rsa_pub = RSA.load_pub_key_bio(bio)
    buffer = None
    while message:
        input = message[:245]
        if showAllInfo:
            tlog("正在加密分段 ...")
            tlog(input)
        snidata = rsa_pub.public_encrypt(input, RSA.pkcs1_padding)
        if buffer == None:
            buffer = snidata
        else:
            buffer = buffer+snidata
        message = message[245:]
    ctxt64_pri = base64.b64encode(buffer)
    return ctxt64_pri


def rsaDecrypt(private_key: "私钥", message: "要解密的信息", showAllInfo=True):
    """RSA 解密"""
    if (isinstance(private_key,bytes) == False):
        private_key = bytes(private_key, encoding = "utf8")
    bio = BIO.MemoryBuffer(private_key)
    rsa_pri = RSA.load_key_bio(bio)
    buffer = None
    while message:
        input = message[:512]
        if showAllInfo:
            tlog("正在解密分段 ...")
        snidata = rsa_pri.private_decrypt(input, RSA.pkcs1_padding)
        if showAllInfo:
            tlog(snidata)
        if buffer == None:
            buffer = snidata
        else:
            buffer = buffer+snidata
        message = message[512:]
    return buffer


def postarray_p(postUrl: "提交到指定的URL", jsonDataArr: "提交的数据数组", showAllInfo=True):
    """[明文传输]向服务器提交内容并显示返回内容，明文操作"""

    jsonfiledata = getjsonfiledata(False)
    apiverAppidSecret = [jsonfiledata["apiver"], jsonfiledata["apptoken"]]

    if (showAllInfo):
        tlog("传输模式：明文")
        tlog("准备输入的数据 ...")
    tlog(postUrl)
    tlog(jsonDataArr)
    if (showAllInfo):
        tlog("读取 testconfig.json ...")
    totptoken = jsonfiledata["apptoken"]
    if (showAllInfo):
        tlog("插入固定提交信息 ...")
    jsonDataArr["apptoken"] = totptoken
    jsonDataArr["apiver"] = apiverAppidSecret[0]
    postMod = parse.urlencode(jsonDataArr).encode(encoding='utf-8')
    if (showAllInfo):
        tlog(json.dumps(jsonDataArr))
        tlog("↑ 发送请求:")
        tlog(postMod.decode())
    postReq = request.Request(url=postUrl, data=postMod)
    try:
        postRes = request.urlopen(postReq)
    except error.HTTPError as e:
        terr("错误：HTTP 连接遇到问题！")
        tlog(e)
        tlog("使用 cURL 获取原始数据 ...")
        curlcmd = 'curl -X POST -d "'+postMod.decode()+'" "'+postUrl+'"'
        tlog(curlcmd)
        output = os.popen(curlcmd)
        tlog(output.read())
        sys.exit(1)
    except error.URLError as e:
        terr("错误：网址不正确！")
        tlog(e)
        sys.exit(1)
    postRes = postRes.read()
    postRes = postRes.decode(encoding='utf-8')
    if (showAllInfo):
        tlog("↓ 收到数据:")
        tlog(postRes)
        tlog("JSON 解析 ...")
    try:
        dataarr = json.loads(postRes)
    except:
        terr("错误：解密失败。")
        tlog("原始内容：")
        tlog(postRes)
        sys.exit()
    tlog(dataarr)
    tok("完成。")
    return dataarr

# appKeyMode:  0.使用'd'  1.apptoken作为key  2.apptoken加入json
def postarray(postUrl: "提交到指定的URL", jsonDataArr: "提交的数据数组", showAllInfo=True, publicKey: "服务器公钥" = None, privateKey: "客户端私钥" = None, appKeyMode=1):
    """[加密传输]向服务器提交内容并显示返回内容，自动处理加密解密"""
    jsonfiledata = getjsonfiledata(True)
    if (showAllInfo):
        tlog("传输模式：加密")
        tlog(postUrl)
    if (showAllInfo):
        tlog("读取 testconfig.json ...")
    if publicKey == None:
        publicKey = jsonfiledata["publickey"]
    if privateKey == None:
        privateKey = jsonfiledata["privatekey"]
    if (showAllInfo):
        tlog("插入固定提交信息 ...")
    if appKeyMode == 2:
        jsonDataArr["apptoken"] = jsonfiledata["apptoken"]
    jsonDataArr["apiver"] = jsonfiledata["apiver"]
    if (showAllInfo):
        tlog("准备输入的数据 ...")
    jsondata = json.dumps(jsonDataArr)
    jsondata = str.encode(jsondata)
    if (showAllInfo):
        tlog(jsondata)
    if (showAllInfo):
        publicKeyStr = ""
        if (isinstance(publicKey,str) == False):
            publicKeyStr = str(publicKey, encoding = "utf-8")
        else:
            publicKeyStr = publicKey
        tlog("正在使用公钥 "+md5(clearkey(publicKeyStr))+" 加密数据 ...")
    publicKey = str.encode(publicKey)
    if appKeyMode == 0:
        postKey = 'd'
    elif appKeyMode == 1:
        postKey = jsonfiledata["apptoken"]
    postData = {
        postKey: rsaEncrypt(publicKey, jsondata, showAllInfo)
    }
    postMod = parse.urlencode(postData).encode(encoding='utf-8')
    if (showAllInfo):
        tlog("↑ 发送请求:")
    if (showAllInfo):
        tlog(jsonDataArr)
    postReq = request.Request(url=postUrl, data=postMod)
    postRes = request.urlopen(postReq)
    postRes = postRes.read()
    if (showAllInfo):
        tlog("↓ 收到数据:")
    postRes = postRes.decode(encoding='utf-8')
    if (showAllInfo):
        tlog(postRes)
    if postRes[0:3] == '<br':
        terr("收到异常信息")
        quit()
    if re.match("^[A-Za-z0-9_-]*$", postRes) == False:
        terr("返回了非预期数据")
        quit()
    if (showAllInfo):
        tlog("还原 BASE64 ...")
    postRes = postRes.replace('-', '+').replace('_', '/')
    mod4 = len(postRes) % 4
    if mod4:
        postRes += "===="[0:4-mod4]
    postRes = bytes(postRes, encoding="utf8")
    tlog(postRes.decode())
    if (showAllInfo):
        tlog("解析 BASE64 ...")
    try:
        postRes = base64.b64decode(postRes)
    except:
        terr("解析 BASE64 不成功。")
        quit()
    if (showAllInfo):
        privateKeyStr = ""
        if (isinstance(privateKey,str) == False):
            privateKeyStr = str(privateKey, encoding = "utf-8")
        else:
            privateKeyStr = privateKey
        tlog("正在使用私钥 "+md5(clearkey(privateKeyStr))+" 解密数据 ...")
    try:
        postRes = rsaDecrypt(privateKey, postRes, showAllInfo)
    except:
        terr("解密数据不成功。")
        quit()
    if (showAllInfo):
        tlog("检查返回的数据 ...")
    if postRes[0:1] != b'[' and postRes[0:1] != b'{' :
        terr("返回数据错误。")
        quit()
    if (showAllInfo):
        tlog(str(postRes, encoding="utf-8"))
    try:
        resArr = json.loads(postRes)
    except:
        terr("JSON 解析失败。")
        quit()
    if resArr['code'] >= 2000000:
        terr("返回状态码错误。")
        quit()
    tok("网络操作完成。")
    tok(json.dumps(resArr, indent=2))
    return resArr

def clearkey(keystr: "密钥内容"):
    """只保留 key 的 base64 部分，删除首尾和回车"""
    keylines = keystr.split('\n')
    if keylines[-1][0:5] == '-----':
        keylines.pop()
    if keylines[0][0:5] == '-----':
        del(keylines[0])
    return ''.join(keylines)

def md5(bstr: "输入byte字符串"):
    """MD5 加密"""
    md5=hashlib.md5()
    md5.update(bstr.encode('utf-8'))
    return md5.hexdigest()

def tlog(loginfo: "信息内容", end='\n'):
    """输出前面带时间的信息"""
    nowtime = datetime.datetime.now().strftime('[%Y-%m-%d %H:%M:%S.%f]')
    print("\033[35m", end='')
    print(nowtime, end='\033[0m ')
    print(loginfo, end=end)


def terr(loginfo: "信息内容"):
    """输出错误"""
    tlog("\033[31m"+loginfo+"\033[0m")
    errinfo = traceback.format_tb(sys.exc_info()[2])
    if errinfo:
        for err in errinfo:
            tlog("\033[31m"+err+"\033[0m")


def tok(loginfo: "信息内容"):
    """输出正确"""
    tlog("\033[32m"+loginfo+"\033[0m")


def title(loginfo: "信息内容"):
    """输出标题"""
    tlog("\033[1m"+loginfo.center(40, '=')+"\033[0m")


def instr(alertinfo: "提示用户要输入的内容", isint=False):
    """接收用户输入"""
    tlog("\033[1m"+alertinfo+"\033[4m", '')
    userinput = input()
    print("\033[0m", end='')
    if isint:
        return int(userinput)
    return userinput
