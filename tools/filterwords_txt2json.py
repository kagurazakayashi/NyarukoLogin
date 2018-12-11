# -*- coding:utf-8 -*-
# 这个工具可以将从其他地方获取的txt逐行关键词列表转换为本程序可识别的json，并写入redis数据库。
# 基于从LOL中提取的关键词列表构建的转换脚本，会删除特殊符号（PHP中同样会自动去除用户加入的特殊符号）、清理'|'标识符、大写字母转换成小写字母（PHP中同样会转换成小写字母进行大小写不敏感的识别）、去重。
# 直接显示转换后的结果: filterwords_txt2json.py filter_zh_cn.txt
# 存储成json文件: filterwords_txt2json.py filter_zh_cn.txt > filter.json
# 写入 redis 数据库需要填写正确的用户名和密码，通过注释掉最后一行可以关闭此功能。
# wildcardchar 和 punctuations 和 redis 相关设置应和 PHP 中的设置相匹配。
import datetime
import sys
import demjson
import redis

wordarr = []
# 特殊符号过滤器,不包括&，将&作为通配符
wildcardchar = '&'
punctuations = "\t\n!@#$%^*()-=_+|\\/?<>,.'\";:{}[]❤❥웃유♋☮✌☏☢☠✔☑♚▲♪✈✞÷↑↓◆◇⊙■□△▽¿─│♥❣♂♀☿Ⓐ✍✉☣☤✘☒♛▼♫⌘☪≈←→◈◎☉★☆⊿※¡━┃♡ღツ☼☁❅♒✎©®™Σ✪✯☭➳卐√↖↗●◐Θ◤◥︻〖〗┄┆℃℉°✿ϟ☃☂✄¢€£∞✫★½✡×↙↘○◑⊕◣◢︼【】┅┇☽☾✚〓▂▃▄▅▆▇█▉▊▋▌▍▎▏↔↕☽☾の•▸◂▴▾┈┊①②③④⑤⑥⑦⑧⑨⑩ⅠⅡⅢⅣⅤⅥⅦⅧⅨⅩ㍿▓♨♛❖♓☪✙┉┋☹☺☻تヅツッシÜϡﭢ™℠℗©®♥❤❥❣❦❧♡۵웃유ღ♋♂♀☿☼☀☁☂☄☾☽❄☃☈⊙☉℃℉❅✺ϟ☇♤♧♡♢♠♣♥♦☜☞☝✍☚☛☟✌✽✾✿❁❃❋❀⚘☑✓✔√☐☒✗✘ㄨ✕✖✖⋆✢✣✤✥❋✦✧✩✰✪✫✬✭✮✯❂✡★✱✲✳✴✵✶✷✸✹✺✻✼❄❅❆❇❈❉❊†☨✞✝☥☦☓☩☯☧☬☸✡♁✙♆。，、＇：∶；?‘’“”〝〞ˆˇ﹕︰﹔﹖﹑•¨….¸;！´？！～—ˉ｜‖＂〃｀@﹫¡¿﹏﹋﹌︴々﹟#﹩$﹠﹪%*﹡﹢﹦﹤‐￣¯―﹨ˆ˜﹍﹎+=<＿_-\ˇ~﹉﹊（）〈〉‹›﹛﹜『』〖〗［］《》〔〕{}「」【】︵︷︿︹︽_﹁﹃︻︶︸﹀︺︾ˉ﹂﹄︼☩☨☦✞✛✜✝✙✠✚†‡◉○◌◍◎●◐◑◒◓◔◕◖◗❂☢⊗⊙◘◙◍⅟½⅓⅕⅙⅛⅔⅖⅚⅜¾⅗⅝⅞⅘≂≃≄≅≆≇≈≉≊≋≌≍≎≏≐≑≒≓≔≕≖≗≘≙≚≛≜≝≞≟≠≡≢≣≤≥≦≧≨≩⊰⊱⋛⋚∫∬∭∮∯∰∱∲∳%℅‰‱㊣㊎㊍㊌㊋㊏㊐㊊㊚㊛㊤㊥㊦㊧㊨㊒㊞㊑㊒㊓㊔㊕㊖㊗㊘㊜㊝㊟㊠㊡㊢㊩㊪㊫㊬㊭㊮㊯㊰㊙㉿囍♔♕♖♗♘♙♚♛♜♝♞♟ℂℍℕℙℚℝℤℬℰℯℱℊℋℎℐℒℓℳℴ℘ℛℭ℮ℌℑℜℨ♪♫♩♬♭♮♯°øⒶ☮✌☪✡☭✯卐✐✎✏✑✒✍✉✁✂✃✄✆✉☎☏➟➡➢➣➤➥➦➧➨➚➘➙➛➜➝➞➸♐➲➳⏎➴➵➶➷➸➹➺➻➼➽←↑→↓↔↕↖↗↘↙↚↛↜↝↞↟↠↡↢↣↤↥↦↧↨➫➬➩➪➭➮➯➱↩↪↫↬↭↮↯↰↱↲↳↴↵↶↷↸↹↺↻↼↽↾↿⇀⇁⇂⇃⇄⇅⇆⇇⇈⇉⇊⇋⇌⇍⇎⇏⇐⇑⇒⇓⇔⇕⇖⇗⇘⇙⇚⇛⇜⇝⇞⇟⇠⇡⇢⇣⇤⇥⇦⇧⇨⇩⇪➀➁➂➃➄➅➆➇➈➉➊➋➌➍➎➏➐➑➒➓㊀㊁㊂㊃㊄㊅㊆㊇㊈㊉ⒶⒷⒸⒹⒺⒻⒼⒽⒾⒿⓀⓁⓂⓃⓄⓅⓆⓇⓈⓉⓊⓋⓌⓍⓎⓏⓐⓑⓒⓓⓔⓕⓖⓗⓘⓙⓚⓛⓜⓝⓞⓟⓠⓡⓢⓣⓤⓥⓦⓧⓨⓩ⒜⒝⒞⒟⒠⒡⒢⒣⒤⒥⒦⒧⒨⒩⒪⒫⒬⒭⒮⒯⒰⒱⒲⒳⒴⒵ⅠⅡⅢⅣⅤⅥⅦⅧⅨⅩⅪⅫⅬⅭⅮⅯⅰⅱⅲⅳⅴⅵⅶⅷⅸⅹⅺⅻⅼⅽⅾⅿ┌┍┎┏┐┑┒┓└┕┖┗┘┙┚┛├┝┞┟┠┡┢┣┤┥┦┧┨┩┪┫┬┭┮┯┰┱┲┳┴┵┶┷┸┹┺┻┼┽┾┿╀╁╂╃╄╅╆╇╈╉╊╋╌╍╎╏═║╒╓╔╕╖╗╘╙╚╛╜╝╞╟╠╡╢╣╤╥╦╧╨╩╪╫╬◤◥◄►▶◀◣◢▲▼◥▸◂▴▾△▽▷◁⊿▻◅▵▿▹◃❏❐❑❒▀▁▂▃▄▅▆▇▉▊▋█▌▍▎▏▐░▒▓▔▕■□▢▣▤▥▦▧▨▩▪▫▬▭▮▯㋀㋁㋂㋃㋄㋅㋆㋇㋈㋉㋊㋋㏠㏡㏢㏣㏤㏥㏦㏧㏨㏩㏪㏫㏬㏭㏮㏯㏰㏱㏲㏳㏴㏵㏶㏷㏸㏹㏺㏻㏼㏽㏾㍙㍚㍛㍜㍝㍞㍟㍠㍡㍢㍣㍤㍥㍦㍧㍨㍩㍪㍫㍬㍭㍮㍯㍰㍘☰☲☱☴☵☶☳☷☯"

def tlog(loginfo:"信息内容",send=None):
    """输出前面带时间的信息"""
    nowtime = datetime.datetime.now().strftime('[%Y-%m-%d %H:%M:%S.%f]')
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
    r = redis.Redis(host='127.0.0.1', port=6379,db=0,password='uHJBJd0ZQNh47C9KKlCFBO8y1LXALbUTyZzRakIlTxmy5ja2scR8w3xKpb7s78jA9FwQseFCAO3sz9U0h6jI8IZ9NL1q5XdErsGmyMrjh2XAjai10oboWPYeGx5MrqJ93Hs1IYSsgWTEDTRcLpEazdBNGV32ETmd7ePX78PqgguxkBhHb9p1D9N2Gd6EPz6X5KhrFKilr2rbQTWd1oPexJYSjGLgybjn3UnSUKovXSQkJADihDgpc7MKnXEaBjKuX4ogQrjcJGbxwaMAdYYDdCL0lSggQx7jkVnBEeqxPkk4QyIRbkj1PCEgJIAVv0eauQ88rgUdSlwxYWabw5Dy5kgdjMwkWmD3jeJXRnP5ApHDvgSAhh4JPk3jGsXfn60tkjQPiIkJwsPMLj8nSmyQtDzyOBAZlVvxwCI40DXnc13oAchhoNr5VMLDdG7oSwqyu0BCiYNzleIIQTQc5dBSWMekYhCcLUoeAyZLoHlIRi1nooUYcJUODIOD0gb9MvX3')
    r.set('wordfilter', data)

# 读入资料
f = None
# try:
f = open(sys.argv[1], 'r',encoding='utf-8')
line = f.readline()
while line:
    scanword(line)
    line = f.readline()
# except:
#     tlog("错误：不能打开文件。")
# finally:
#     if f:
f.close()
# 创建 JSON
jsonstr = demjson.encode(wordarr)
print(jsonstr)
# 写入 redis 数据库，不需要写入注释掉
wtoredis(jsonstr)