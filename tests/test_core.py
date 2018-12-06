# -*- coding:utf-8 -*-
import sys
import onetimepass as otp # pip3 install onetimepass
from urllib import parse,request
import demjson # pip3 install demjson
import xxtea # pip3 install xxtea-py cffi
import base64
import re
import datetime
import hashlib

def tlog(loginfo:"信息内容",send=None):
    """输出前面带时间的信息"""
    nowtime = datetime.datetime.now().strftime('[%Y-%m-%d %H:%M:%S.%f]')
    print(nowtime,end=' ')
    print(loginfo)

def postarray(postUrl:"提交到指定的URL",jsonDataArr:"提交的数据数组",showAllInfo=False):
    """向服务器提交内容并显示返回内容，自动处理加密解密"""
    if (showAllInfo) : tlog("准备输入的数据 ...")
    tlog(postUrl)
    tlog(jsonDataArr)
    if (showAllInfo) : tlog("读取 totpsecret.json ...")
    totpsecret = ""
    totptoken = ""
    try:
        f = open('totpsecret.json', 'r')
        filejson = f.read().rstrip('\n')
        if (showAllInfo) : tlog(filejson)
        filedataarr = demjson.decode(filejson)
        totpsecret = filedataarr["totp_secret"]
        totptoken = filedataarr["totp_token"]
    except:
        tlog("错误：不能打开文件「totpsecret.json」，先运行「test_gettotptoken.py」来获取返回的 JSON，确保没有错误信息，然后将 JSON 保存到「totpsecret.json」")
    finally:
        if f:
            f.close()
    if (showAllInfo) : tlog("JSON 编码 ...")
    jsonstr = demjson.encode(jsonDataArr)
    if (showAllInfo) :
        tlog(jsonstr)
        tlog("生成 TOTP 代码 ...")
        tlog(totpsecret)
    totpcode = otp.get_totp(totpsecret)
    if (showAllInfo) :
        tlog(totpcode)
        tlog("混合 totpsecret 和 totpcode 并转为 MD5 ...")
    md5g = hashlib.md5()
    md5prestr = totpsecret + str(totpcode)
    md5g.update(md5prestr.encode(encoding='utf-8'))
    md5str = md5g.hexdigest()
    if (showAllInfo) :
        tlog(md5prestr)
        tlog(md5str)
        tlog("XXTEA 加密 ...")
    encryptdata = xxtea.encrypt(jsonstr, md5str)
    if (showAllInfo) :
        tlog(encryptdata)
        tlog("base64 编码 ...")
    base64str = base64.b64encode(encryptdata).decode()
    if (showAllInfo) :
        tlog(base64str)
        tlog("base64 变体 ...")
    postStr = base64str.replace('+','-').replace('/','_').replace('=','')
    if (showAllInfo) :
        tlog(postStr)
        tlog("准备 POST 数据 ...")
    postData = {'t':totptoken,'j':postStr}
    postMod = parse.urlencode(postData).encode(encoding='utf-8')
    if (showAllInfo) :
        tlog("↑ 发送请求:")
        tlog(postMod.decode())
    postReq = request.Request(url=postUrl,data=postMod)
    postRes = request.urlopen(postReq)
    postRes = postRes.read()
    postRes = postRes.decode(encoding='utf-8')
    if (showAllInfo) :
        tlog("↓ 收到数据:")
        tlog(postRes)
        tlog("检查返回数据合法性 ...")
    matchObj = re.match(r"^[0-9A-Za-z\-_]+$", postRes)
    if matchObj == None:
        tlog("收到了非预期的数据，中止。")
        tlog(postRes)
        sys.exit()
    if (showAllInfo) : tlog("base64 撤销变体 ...")
    base64str = postRes.replace('-','+').replace('_','/')
    mod4 = len(postRes) % 4
    if mod4:
        base64str += "===="[0:4-mod4]
    if (showAllInfo) :
        tlog(base64str)
        tlog("base64 解码 ...")
    encryptdata = base64.b64decode(base64str)
    if (showAllInfo) :
        tlog(encryptdata)
        tlog("生成 TOTP 代码 ...")
        tlog(totpsecret)
    totpcode = otp.get_totp(totpsecret)
    if (showAllInfo) :
        tlog(totpcode)
        tlog("混合 totpsecret 和 totpcode 并转为 MD5 ...")
    md5g = hashlib.md5()
    md5prestr = totpsecret + str(totpcode)
    md5g.update(md5prestr.encode(encoding='utf-8'))
    md5str = md5g.hexdigest()
    if (showAllInfo) :
        tlog(md5prestr)
        tlog(md5str)
        tlog("XXTEA 解密 ...")
    jsonstr = xxtea.decrypt_utf8(encryptdata, md5str)
    if (showAllInfo) :
        tlog(jsonstr)
        tlog("JSON 解析 ...")
    try:
        dataarr = demjson.decode(jsonstr)
    except:
        tlog("错误：解密失败。")
    tlog(dataarr)
    if (showAllInfo) : tlog("完成。")