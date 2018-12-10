<?php
class nyasetting_db {
    //数据库连接设置
    //只读库，可指定多个数据库
    var $read_dbs = [
        [
            "db_host" => "127.0.0.1",
            "db_port" => "3306",
            "db_name" => "nyarukologin",
            "db_user" => "nyarukologinuser",
            "db_password" => "djdme8wEF9UOPfBa4Vvfx482ncfa4aSNWI5BX4ptpGVAol2iVocY3byGKIJjYt9qGWjftibIJ0ovVUk0nLS6OmwbJddJdIpmGKbzxjqaMareRbfo3H9oBIR2tD2wUEaZZWU9gkh00jAK3rbOhfJjTlhXFuozbH73aCHPGXcifNHtCSvOK0CbKiQPMPAka9ruXZmQF5uKlTHYoWacS0YVo8YWDnA9vVyj7bZVUFzAI6FMBB15nXITbfxrgDZBxOrW"
        ]
    ];
    //写入库，可指定多个数据库
    var $write_dbs = [
        [
            "db_host" => "127.0.0.1",
            "db_port" => "3306",
            "db_name" => "nyarukologin",
            "db_user" => "nyarukologinuser",
            "db_password" => "djdme8wEF9UOPfBa4Vvfx482ncfa4aSNWI5BX4ptpGVAol2iVocY3byGKIJjYt9qGWjftibIJ0ovVUk0nLS6OmwbJddJdIpmGKbzxjqaMareRbfo3H9oBIR2tD2wUEaZZWU9gkh00jAK3rbOhfJjTlhXFuozbH73aCHPGXcifNHtCSvOK0CbKiQPMPAka9ruXZmQF5uKlTHYoWacS0YVo8YWDnA9vVyj7bZVUFzAI6FMBB15nXITbfxrgDZBxOrW"
        ]
    ];
    //数据库表设置
    var $tables = [
        "business" => "u1_business", //业务表
        "change" => "u1_change", //信息和积分变更日志
        "external_app" => "u1_external_app", //外部程序表
        "integral" => "u1_integral", //积分表
        "ip_address" => "u1_ip_address", //IP地址表
        "jurisdiction" => "u1_jurisdiction", //权限表
        "password_protection" => "u1_password_protection", //密保表
        "session_token" => "u1_session_token", //会话令牌表
        "users" => "u1_users", //用户表
        "users_information" => "u1_users_information", //用户信息表
        "user_group" => "u1_user_group", //用户组表
        "verification_sending_log" => "u1_verification_sending_log", //验证信息发送日志
        "session_totp" => "u1_session_totp", //通信动态密码
    ];
    //Redis数据库设定
    var $redis = [
        "rdb_enable" => true,
        "rdb_host" => "127.0.0.1",
        "rdb_port" => 6379,
        "rdb_password" => "uHJBJd0ZQNh47C9KKlCFBO8y1LXALbUTyZzRakIlTxmy5ja2scR8w3xKpb7s78jA9FwQseFCAO3sz9U0h6jI8IZ9NL1q5XdErsGmyMrjh2XAjai10oboWPYeGx5MrqJ93Hs1IYSsgWTEDTRcLpEazdBNGV32ETmd7ePX78PqgguxkBhHb9p1D9N2Gd6EPz6X5KhrFKilr2rbQTWd1oPexJYSjGLgybjn3UnSUKovXSQkJADihDgpc7MKnXEaBjKuX4ogQrjcJGbxwaMAdYYDdCL0lSggQx7jkVnBEeqxPkk4QyIRbkj1PCEgJIAVv0eauQ88rgUdSlwxYWabw5Dy5kgdjMwkWmD3jeJXRnP5ApHDvgSAhh4JPk3jGsXfn60tkjQPiIkJwsPMLj8nSmyQtDzyOBAZlVvxwCI40DXnc13oAchhoNr5VMLDdG7oSwqyu0BCiYNzleIIQTQc5dBSWMekYhCcLUoeAyZLoHlIRi1nooUYcJUODIOD0gb9MvX3",
        "rdb_id" => 0
    ];
}

//应用相关设置
class nyasetting_app {
    var $debug = 1; //是否输出所有PHP错误,1显示,0禁止,其他数字:按照php.ini中的设定
    var $app = "nyalogin_dev"; //应用名称（纯字母）（预留未实装）
    var $appname = "nyalogin_test"; //应用描述（预留未实装）
    var $wwwroot = ""; //所在路径（预留未实装）
    var $jsonlen_get = 2000; //数据（j参数）使用 get 方式提交时允许长度（字节）
    var $jsonlen_post = 1000000; //数据（j参数）使用 post 方式提交时允许长度（字节）
    var $timezone = 0; //时区补偿（秒），例如 8 * 3600
    var $alwayencrypt = false; //强制进行 TOTP/XXTEA 加密
    var $totpcompensate = 0; //TOTP 补偿，需要客户端匹配

    var $frequency = true; //启动接口访问频率限制
    //各功能时长设定（每个IP地址）：[多少秒内,最多允许访问多少次]
    var $limittime = [
        "encrypttest" => [60,30],
        "getlinktotp" => [60,3] //限制 连接加密TOTP申请 接口的访问频率
    ];

    //关键词过滤设置，数据应全转换为小写，将&作为通配符的 json
    //违禁词列表为 JSON 一维数组，每个字符串中可以加「wildcardchar」分隔以同时满足两个条件词。
    var $wordfilter = [
        "enable" => 1, //0:禁用, 1:从 Redis 读入, 2:从 $wordfilterjsonfile 读入
        "rediskey" => "wordfilter", //从 Redis 读入，指定 Redis Key
        "jsonfile" => "filter_zh_cn.json", //从文件读入，指定文件路径和名
        "wildcardchar" => '&', //分隔符，用于同时满足多个条件词。
        "replacechar" => '*', //如果返回和谐后的文字，已屏蔽的字符用此字符替代。
        "maxlength" => 5, //最大分析长度，指定多个条件时，两个条件词之间间隔超过此长度则不判为违规
        "punctuations" => "\t\n!@#$%^*()-=_+|\\/?<>,.'\";:{}[]❤❥웃유♋☮✌☏☢☠✔☑♚▲♪✈✞÷↑↓◆◇⊙■□△▽¿─│♥❣♂♀☿Ⓐ✍✉☣☤✘☒♛▼♫⌘☪≈←→◈◎☉★☆⊿※¡━┃♡ღツ☼☁❅♒✎©®™Σ✪✯☭➳卐√↖↗●◐Θ◤◥︻〖〗┄┆℃℉°✿ϟ☃☂✄¢€£∞✫★½✡×↙↘○◑⊕◣◢︼【】┅┇☽☾✚〓▂▃▄▅▆▇█▉▊▋▌▍▎▏↔↕☽☾の•▸◂▴▾┈┊①②③④⑤⑥⑦⑧⑨⑩ⅠⅡⅢⅣⅤⅥⅦⅧⅨⅩ㍿▓♨♛❖♓☪✙┉┋☹☺☻تヅツッシÜϡﭢ™℠℗©®♥❤❥❣❦❧♡۵웃유ღ♋♂♀☿☼☀☁☂☄☾☽❄☃☈⊙☉℃℉❅✺ϟ☇♤♧♡♢♠♣♥♦☜☞☝✍☚☛☟✌✽✾✿❁❃❋❀⚘☑✓✔√☐☒✗✘ㄨ✕✖✖⋆✢✣✤✥❋✦✧✩✰✪✫✬✭✮✯❂✡★✱✲✳✴✵✶✷✸✹✺✻✼❄❅❆❇❈❉❊†☨✞✝☥☦☓☩☯☧☬☸✡♁✙♆。，、＇：∶；?‘’“”〝〞ˆˇ﹕︰﹔﹖﹑•¨….¸;！´？！～—ˉ｜‖＂〃｀@﹫¡¿﹏﹋﹌︴々﹟#﹩\$﹠﹪%*﹡﹢﹦﹤‐￣¯―﹨ˆ˜﹍﹎+=<＿_-\ˇ~﹉﹊（）〈〉‹›﹛﹜『』〖〗［］《》〔〕{}「」【】︵︷︿︹︽_﹁﹃︻︶︸﹀︺︾ˉ﹂﹄︼☩☨☦✞✛✜✝✙✠✚†‡◉○◌◍◎●◐◑◒◓◔◕◖◗❂☢⊗⊙◘◙◍⅟½⅓⅕⅙⅛⅔⅖⅚⅜¾⅗⅝⅞⅘≂≃≄≅≆≇≈≉≊≋≌≍≎≏≐≑≒≓≔≕≖≗≘≙≚≛≜≝≞≟≠≡≢≣≤≥≦≧≨≩⊰⊱⋛⋚∫∬∭∮∯∰∱∲∳%℅‰‱㊣㊎㊍㊌㊋㊏㊐㊊㊚㊛㊤㊥㊦㊧㊨㊒㊞㊑㊒㊓㊔㊕㊖㊗㊘㊜㊝㊟㊠㊡㊢㊩㊪㊫㊬㊭㊮㊯㊰㊙㉿囍♔♕♖♗♘♙♚♛♜♝♞♟ℂℍℕℙℚℝℤℬℰℯℱℊℋℎℐℒℓℳℴ℘ℛℭ℮ℌℑℜℨ♪♫♩♬♭♮♯°øⒶ☮✌☪✡☭✯卐✐✎✏✑✒✍✉✁✂✃✄✆✉☎☏➟➡➢➣➤➥➦➧➨➚➘➙➛➜➝➞➸♐➲➳⏎➴➵➶➷➸➹➺➻➼➽←↑→↓↔↕↖↗↘↙↚↛↜↝↞↟↠↡↢↣↤↥↦↧↨➫➬➩➪➭➮➯➱↩↪↫↬↭↮↯↰↱↲↳↴↵↶↷↸↹↺↻↼↽↾↿⇀⇁⇂⇃⇄⇅⇆⇇⇈⇉⇊⇋⇌⇍⇎⇏⇐⇑⇒⇓⇔⇕⇖⇗⇘⇙⇚⇛⇜⇝⇞⇟⇠⇡⇢⇣⇤⇥⇦⇧⇨⇩⇪➀➁➂➃➄➅➆➇➈➉➊➋➌➍➎➏➐➑➒➓㊀㊁㊂㊃㊄㊅㊆㊇㊈㊉ⒶⒷⒸⒹⒺⒻⒼⒽⒾⒿⓀⓁⓂⓃⓄⓅⓆⓇⓈⓉⓊⓋⓌⓍⓎⓏⓐⓑⓒⓓⓔⓕⓖⓗⓘⓙⓚⓛⓜⓝⓞⓟⓠⓡⓢⓣⓤⓥⓦⓧⓨⓩ⒜⒝⒞⒟⒠⒡⒢⒣⒤⒥⒦⒧⒨⒩⒪⒫⒬⒭⒮⒯⒰⒱⒲⒳⒴⒵ⅠⅡⅢⅣⅤⅥⅦⅧⅨⅩⅪⅫⅬⅭⅮⅯⅰⅱⅲⅳⅴⅵⅶⅷⅸⅹⅺⅻⅼⅽⅾⅿ┌┍┎┏┐┑┒┓└┕┖┗┘┙┚┛├┝┞┟┠┡┢┣┤┥┦┧┨┩┪┫┬┭┮┯┰┱┲┳┴┵┶┷┸┹┺┻┼┽┾┿╀╁╂╃╄╅╆╇╈╉╊╋╌╍╎╏═║╒╓╔╕╖╗╘╙╚╛╜╝╞╟╠╡╢╣╤╥╦╧╨╩╪╫╬◤◥◄►▶◀◣◢▲▼◥▸◂▴▾△▽▷◁⊿▻◅▵▿▹◃❏❐❑❒▀▁▂▃▄▅▆▇▉▊▋█▌▍▎▏▐░▒▓▔▕■□▢▣▤▥▦▧▨▩▪▫▬▭▮▯㋀㋁㋂㋃㋄㋅㋆㋇㋈㋉㋊㋋㏠㏡㏢㏣㏤㏥㏦㏧㏨㏩㏪㏫㏬㏭㏮㏯㏰㏱㏲㏳㏴㏵㏶㏷㏸㏹㏺㏻㏼㏽㏾㍙㍚㍛㍜㍝㍞㍟㍠㍡㍢㍣㍤㍥㍦㍧㍨㍩㍪㍫㍬㍭㍮㍯㍰㍘☰☲☱☴☵☶☳☷☯" //特殊符号字符过滤器,不包括&，因为上面将&作为了通配符
    ];
}
//邮件发送设置
class nyasetting_mail {
    var $enable = false;
    var $fromEmail = "";
    var $fromName = "";
    var $smtpHost = "";
    var $smtpPort = "";
    var $fromMail = "";
    var $username = "";
    var $password = "";
    var $charSet = "UTF-8";
    var $encoding = "base64";
}
//验证码设置
class nyasetting_vcode {
    var $verification = true; //进行验证码验证
	//基于php/内字体路径，可从多个字体中抽选
    var $font = array("../font/SourceCodePro-Regular.ttf");
    var $fontsize = 20; //字体大小
    var $bgchar = "poi"; //背景干扰字符
    var $charset = "abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789"; //可抽选字符
    var $codelen = 4; //验证码长度
    var $width = 130; //宽度
    var $height = 50; //高度
    var $imageformat = "jpg"; // png / jpg / gif / wbmp(不推荐) / xbm(不推荐)
    var $line_density = 6; //背景线条密度
    var $bgchar_density = 33; //背景干扰字符密度
}
//初始化，不要修改
class nyasetting {
    var $db;
    var $app;
    var $mail;
    var $vcode;
    function __construct() {
        $this->db = new nyasetting_db();
        $this->app = new nyasetting_app();
        $this->mail = new nyasetting_mail();
        $this->vcode = new nyasetting_vcode();
    }
    function __destruct() {
        $this->db = null; unset($this->db);
        $this->app = null; unset($this->app);
        $this->mail = null; unset($this->mail);
        $this->vcode = null; unset($this->vcode);
    }
}
?>