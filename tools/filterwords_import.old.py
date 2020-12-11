# -*- coding:utf-8 -*-
# ### 介绍
# - 这个工具可以导入关键词库到 Redis 。
# - 关键词文件是 一行一个关键词 的 纯文本 文件，建议使用 UTF-8 和 LF 。
# ### 命令
# - 导入 words.txt
#   - `python3 filterwords_import.py words.txt`
# ### 注意事项
# - 请先编辑 `filterwords_config.py` 中的关于 Redis 数据库相关的设定。
import sys
import demjson
import redis
import filterwords_config

wordarr = []
# 读入资料
f = None
i = 0
t = 0
try:
    f = open(sys.argv[1], 'r')
    line = f.readline()
    while line:
        linestr = line.replace("\r", "").replace("\n", "")
        if linestr != "":
            wordarr.append(linestr)
            print('.',end='')
            i = i + 1
        line = f.readline()
        t = t + 1
except Exception as e:
    print("错误：不能打开文件 " + sys.argv[1] + "，因为",e)
    quit()
finally:
    if f:
        f.close()
# 创建 JSON
jsonstr = demjson.encode(wordarr)
# print(jsonstr)
r = redis.Redis(host=filterwords_config.redis_host, port=filterwords_config.redis_port,db=filterwords_config.redis_db,password=filterwords_config.redis_password)
r.set(filterwords_config.redis_key, jsonstr)
r.close
print("已导入 "+str(i)+" / "+str(t)+" 项。")
