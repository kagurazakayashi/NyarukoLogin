# -*- coding:utf-8 -*-
# ### 介绍
# - 这个工具可以导出 Redis 关键词库到标准命令行。
# ### 命令
# - 显示关键词列表
#   - `python3 filterwords_import.py`
# - 导出到 words.txt
#   - `python3 filterwords_import.py > words.txt`
# - 显示原始 JSON
#   - `python3 filterwords_import.py json`
# ### 注意事项
# - 请先编辑 `filterwords_config.py` 中的关于 Redis 数据库相关的设定。
# - 建议在 bash 中进行操作。
import sys
import redis
import filterwords_config
import demjson
r = redis.Redis(host=filterwords_config.redis_host, port=filterwords_config.redis_port,db=filterwords_config.redis_db,password=filterwords_config.redis_password)
data = r.get(filterwords_config.redis_key)
r.close
if len(sys.argv) > 1 and sys.argv[1] == "json":
    print(data)
    exit()
wordarr = demjson.decode(data)
for word in wordarr:
    print(word)
