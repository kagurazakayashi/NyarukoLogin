# -*- coding:utf-8 -*-
import sys
import onetimepass as otp # pip3 install onetimepass
from urllib import parse,request,error
import demjson # pip3 install demjson
import xxtea # pip3 install xxtea-py cffi
import base64
import re
import datetime
import hashlib
import os

def postarray(postUrl:"提交到指定的URL",jsonDataArr:"提交的数据数组",showAllInfo=False):
    """向服务器提交内容并显示返回内容，自动处理加密解密"""

    # 需要提供与数据库 external_app 表中记录的内容
    apiverAppidSecret = ["1","vbCxaCOZL36G5EamUIbKC9ABk4aj8L9CTxBrcaJdrdukZJU3PrZs1oAh2UNkK0nW"]

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
        terr("错误：不能打开文件「totpsecret.json」，先运行「test_gettotptoken.py」来获取返回的 JSON，确保没有错误信息，然后将 JSON 保存到「totpsecret.json」")
    finally:
        if f:
            f.close()
    if (showAllInfo) : tlog("插入固定提交信息 ...")
    jsonDataArr["apiver"] = apiverAppidSecret[0]
    jsonDataArr["appsecret"] = apiverAppidSecret[1]
    if (showAllInfo) :
        tlog(apiverAppidSecret)
        tlog("JSON 编码 ...")
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
    if (showAllInfo) :
        tlog("↓ 收到数据:")
        tlog(postRes)
        tlog("检查返回数据合法性 ...")
    matchObj = re.match(r"^[0-9A-Za-z\-_]+$", postRes)
    if matchObj == None:
        tlog("\033[31m错误：收到了非预期的数据，中止。\033[0m")
        tlog("原始内容：")
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
        terr("错误：解密失败。")
        tlog("原始内容：")
        tlog(postRes)
        sys.exit()
    tlog(dataarr)
    if (showAllInfo) : tok("完成。")
    return dataarr

def tlog(loginfo:"信息内容",end='\n'):
    """输出前面带时间的信息"""
    nowtime = datetime.datetime.now().strftime('[%Y-%m-%d %H:%M:%S.%f]')
    print("\033[35m",end='')
    print(nowtime,end='\033[0m ')
    print(loginfo,end=end)

def terr(loginfo:"信息内容"):
    """输出错误"""
    tlog("\033[31m"+loginfo+"\033[0m")

def tok(loginfo:"信息内容"):
    """输出正确"""
    tlog("\033[32m"+loginfo+"\033[0m")

def title(loginfo:"信息内容"):
    """输出标题"""
    tlog("\033[1m"+loginfo.center(40,'=')+"\033[0m")

def instr(alertinfo:"提示用户要输入的内容",isint=False):
    """接收用户输入"""
    tlog("\033[1m"+alertinfo+"\033[4m",'')
    userinput = input()
    print("\033[0m",end='')
    if isint:
        return int(userinput)
    return userinput
