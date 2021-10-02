#!/usr/bin/python3
# coding: utf-8
# 日志文件分割工具，可用于 NyarukoLogin 和 NyarukoSNS
# 放在和日志文件同一个文件夹中，修改代码最下方的各文件名，然后执行。
# 将会为每个日志文件单独创建一个文件夹，然后将日志拆开成以每天分隔的各个文件（以日期作为文件名），放在各自的文件夹里。
# 可以设置为 00:00 定时执行，记得执行成功完成后，清空这几个日志文件的内容，防止被重新整理。

import os


def log_l(logname: str):
    '''單行日誌格式'''
    if os.path.exists(logname) == False:
        os.makedirs(logname)
    logfile = logname + ".log"
    print("open" + logfile)
    fr = open(logfile, "r", encoding="utf-8")
    nowfw:str = ""
    fw = None
    for line in fr.readlines():
        line = line.strip()
        if not len(line):
            continue
        arr: list[str] = line.split(' ')
        if len(arr) < 2:
            continue
        datetimestr: str = arr[0]
        datetimestr = datetimestr.replace(datetimestr[0], '')
        if len(datetimestr) > 10 or len(datetimestr.split('-')) != 3:
            print("ERR Line")
            continue
        tologfile: str = logname + "/" + datetimestr + ".log"
        if tologfile != nowfw:
            if fw != None:
                fw.close()
            print("write " + tologfile)
            fw = open(tologfile, "a", encoding="utf-8")
            nowfw = tologfile
        fw.write(line + "\n")
    fr.close()

def log_ml(logname: str):
    '''多行日誌格式'''
    if os.path.exists(logname) == False:
        os.makedirs(logname)
    logfile = logname + ".log"
    print("open " + logfile)
    fr = open(logfile, "r", encoding="utf-8")
    nowfw: str = ""
    fw = None
    outlog: str = ""
    keyline: str = ""
    for line in fr.readlines():
        line = line.strip()
        if not len(line):
            continue
        if len(line) > 10 and line[0:5] == "=" * 5:
            keyline = line
            if len(outlog) == 0:
                continue
            arr: list[str] = line.split(' ')
            if len(arr) != 4:
                continue
            datetimestr: str = arr[1]
            if len(datetimestr) > 10 or len(datetimestr.split('-')) != 3:
                print("ERR Line")
                continue
            tologfile: str = logname + "/" + datetimestr + ".log"
            if tologfile != nowfw:
                if fw != None:
                    fw.close()
                print("write " + tologfile)
                fw = open(tologfile, "a", encoding="utf-8")
                nowfw = tologfile
                fw.write(keyline + "\n" + outlog)
                outlog = ""
        else:
            outlog += line + "\n"
    fr.close()


log_ml("exec")
log_l("nya")
log_l("db")
log_l("submit")