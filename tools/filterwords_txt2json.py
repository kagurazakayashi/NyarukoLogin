# -*- coding:utf-8 -*-
# ### 介绍
# - 基于从 LOL 中提取的关键词列表构建的转换脚本。
# - 这个工具可以将从其他地方获取的 txt 逐行关键词列表转换为本程序可识别的json，输出到标准命令行并写入 Redis 数据库。
# - 会删除特殊符号（PHP中同样会自动去除用户加入的特殊符号）、清理'|'标识符、大写字母转换成小写字母（PHP中同样会转换成小写字母进行大小写不敏感的识别）、去重。
# ### 命令
# - 直接显示转换后的结果并写入到 Redis 数据库:
#   - `python3 filterwords_txt2json.py filter_zh_cn.txt`
# - 存储成 JSON 文件并写入到 Redis 数据库:
#   - `python3 filterwords_txt2json.py filter_zh_cn.txt > filter.json`
# ### 注意事项
# - 写入 Redis 数据库，请先编辑 `filterwords_config.py` 中的关于 Redis 数据库相关的设定。
# - 如果不写入 Redis 数据库，通过注释掉最后一行可以关闭此功能。
# - wildcardchar 和 punctuations 和 Redis 相关设置应和 PHP 中的设置相匹配。
import datetime
import sys
import demjson
import redis
import filterwords_config

wordarr = []
# 特殊符号过滤器,不包括&，将&作为通配符
wildcardchar = '&'
punctuations = "\t\n!@#$%^*()-=_+|\\/?<>,.'\";:{}[]"

def tlog(loginfo:"信息内容",send=None):
    """输出前面带时间的信息"""
    nowtime = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S.%f')
    print(nowtime,end=' ')
    print(loginfo)

def scanword(line:"要分析的行"):
    """分析当前行中包含的词汇"""
    # 删除特殊符号
    wordstr = line
    for punctuationschar in punctuations:
        wordstr = wordstr.replace(punctuationschar, "")
    # 清理'|'
    wordstr = wordstr.split('|')[0]
    # 把所有字符中的大写字母转换成小写字母
    wordstr = wordstr.lower()
    # 去重并新增
    if (wordstr in wordarr) == False and wordstr != "":
        wordarr.append(wordstr)

def wtoredis(data:"要存储的数据"):
    """写入Redis数据库"""
    r = redis.Redis(host=filterwords_config.redis_host, port=filterwords_config.redis_port,db=filterwords_config.redis_db,password=filterwords_config.redis_password)
    r.set(filterwords_config.redis_key, data)
    r.close

# 读入资料
f = None
try:
    f = open(sys.argv[1], 'r')
    line = f.readline()
    while line:
        scanword(line)
        line = f.readline()
except Exception as e:
    tlog("错误：不能打开文件 " + sys.argv[1] + "，因为",e)
    quit()
finally:
    if f:
        f.close()
# 创建 JSON
jsonstr = demjson.encode(wordarr)
print(jsonstr)
# 写入 redis 数据库，不需要写入注释掉
wtoredis(jsonstr)
