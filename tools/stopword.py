# -*- coding:utf-8 -*-
# 将 TXT 敏感词列表导入到数据库。
# TXT 内容为每个词一行，多词触发则在一行内用空格分隔。
# 命令行参数参数（必须写全）：
# python3 stopword.py i m.txt 10 test
#     i : 导入数据
# m.txt : 要导入的文本文件
#    10 : 多词触发模式下，最多允许相隔多少字
#  test : 备注
import datetime
import sys
import traceback
import pymysql  # wget https://bootstrap.pypa.io/ez_setup.py && python3 ez_setup.py && pip3 install PyMySQL
import hashlib

# 特殊符号过滤器
punctuations = "\t\n!@#$%^*()-=_+|\\/?<>&,.'\";:{}[]"
# 数据库凭据
mysqlHost = "127.0.0.1"
mysqlUser = "nyarukologin_w"
mysqlPwd = "pgfeaKtAYChjegoXlNq1r2sKrN4ucgrMFE8cypB6p4cgwdBYHYDLn2KTT3MAOuxIRq5wLjiM1pqgutqQIhBZIZZy85DXjQKB8ss5bpSQ0Em2bDSZs5xqfW8bMkNqwNcryJNsJpeXZrDihmJH1xOb4DZQo4kH0rI84O1jtajDUX2BX2jHp7DZp0aTfDpihXkcAZQYn9sGPsopO6CahX1UhP568GuqteRQSKa8B8KtrPKpUSotFuQGNujQRnjj1rFz"
mysqlDb = "nyarukologin"
mysqlCharset = "utf8"
mysqlTable = "u1_stopword"
# 统计
sqlCmdArr = []


def tlog(loginfo: "信息内容", end='\n'):
    """输出前面带时间的信息"""
    nowtime = datetime.datetime.now().strftime('[%Y-%m-%d %H:%M:%S.%f]')
    print("\033[35m", end='')
    print(nowtime, end='\033[0m ')
    print(loginfo, end=end)


def terr(loginfo: "信息内容"):
    """输出错误"""
    tlog("\033[31m"+loginfo+"\033[0m")
    # errinfo = traceback.format_tb(sys.exc_info()[2])
    # if errinfo:
    #     for err in errinfo:
    #         tlog("\033[31m"+err+"\033[0m")


def tok(loginfo: "信息内容"):
    """输出正确"""
    tlog("\033[32m"+loginfo+"\033[0m")


def importLine(line: "分析的行内容"):
    """逐行词汇读入"""
    if len(line) == 0:
        return
    # 删除特殊符号
    wordstr = line
    for punctuationschar in punctuations:
        wordstr = wordstr.replace(punctuationschar, "")
    # 把所有字符中的大写字母转换成小写字母
    wordstr = wordstr.lower()
    # 转换全角空格
    wordstr = wordstr.replace('　', ' ')
    # 分割同行词汇
    wordarr = wordstr.split(' ')
    sha = hashlib.sha256()
    sha.update(wordstr.encode('utf-8'))
    chrint = sys.argv[3]
    keyarr = ['swhash', 'ps', 'sw0']
    valarr = [sha.hexdigest(), sys.argv[4], wordarr[0]]
    if len(wordarr) > 1:
        keyarr.append('chrint')
        valarr.append(chrint)
    if len(wordarr) >= 2:
        keyarr.append('sw1')
        valarr.append(wordarr[1])
    if len(wordarr) >= 3:
        keyarr.append('sw2')
        valarr.append(wordarr[2])
    if len(wordarr) >= 4:
        keyarr.append('sw3')
        valarr.append(wordarr[3])
    keystr = '`, `'.join(keyarr)
    valstr = '\', \''.join(valarr)
    sqlcmd = 'INSERT INTO `' + mysqlDb + '`.`' + mysqlTable + \
        '` (`'+keystr+'`) VALUES (\''+valstr+'\');'
    sqlCmdArr.append(sqlcmd)


def runSqlCmd():
    sqlCmdCount = str(len(sqlCmdArr))
    db = pymysql.connect(mysqlHost, mysqlUser, mysqlPwd, mysqlDb)
    cursor = db.cursor()
    i = 0
    iok = 0
    ierr = 0
    for sqlCmd in sqlCmdArr:
        tlog(sqlCmd)
        i += 1
        try:
            cursor.execute(sqlCmd)
            db.commit()
            # data = cursor.fetchone()
            iok += 1
            tok(str(i) + " / " + sqlCmdCount + "  OK")
        except:
            ierr += 1
            terr(str(i) + " / " + sqlCmdCount + "  ERROR")
            db.rollback()
    db.close()
    tlog("ALL : " + str(i))
    if iok > 0:
        tok(" OK : " + str(iok))
    if ierr > 0:
        terr("FAIL: " + str(ierr))


def importFromText():
    """将TXT敏感词库导入到SQL"""
    # 读入TXT
    f = None
    try:
        f = open(sys.argv[2], 'r')
        line = f.readline()
        while line:
            # 逐行词汇读入
            importLine(line)
            line = f.readline()
    except Exception as e:
        tlog("错误：不能打开文件 " + sys.argv[1] + "，因为", e)
        quit()
    finally:
        if f:
            f.close()
    runSqlCmd()


# 处理参数
if len(sys.argv) != 5:
    terr("参数不正确")
    quit()

if sys.argv[1] == 'i':
    importFromText()
    quit()
