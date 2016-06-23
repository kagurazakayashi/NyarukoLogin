function toVaild(path) {
    var wrn = "";
    var wrni = 0;
    var intnum = /^[1-9]+[0-9]*]*$/;
    var floatnum = /^[0-9]+.?[0-9]*$/;
    var englishnum = /^[A-Za-z0-9]+$/;
    //var md5hash = require(["js/md5.js"]).md5hash;
    //var md6hash = require(["js/md6.js"]).md6hash;


    var v = document.getElementById("useremail").value;
    if (v.length < 5 || v.length > 64) {
        wrni++; wrn = wrn + wrni + ". 「电子邮件」必须在 5 到 64 个字之间。\n";
    } else {
        apos = v.indexOf("@")
        dotpos = v.lastIndexOf(".")
        if (apos < 1 || dotpos - apos < 2)
        { wrni++; wrn = wrn + wrni + ". 「电子邮件」必须是一个有效的邮箱地址。\n"; }
    }
    if (!inputcheck(v)) {
        wrni++; wrn = wrn + wrni + ". 「电子邮件」不能包含特殊字符。\n";
    }

    var v = document.getElementById("vcode").value;
    if (v.length != 4) {
        wrni++; wrn = wrn + wrni + ". 「验证码」不正确。\n";
    }
    if (!inputcheck(v)) {
        wrni++; wrn = wrn + wrni + ". 「验证码」不能包含特殊字符。\n";
    }

    if (wrni > 0) {
        alert("抱歉，你输入的内容有误：\n" + wrn);
        document.getElementById("vcodeimg").src = 'php/validate_image.php?' + Math.random();
        document.getElementById("vcode").value = "";
    } else {
        var form1 = document.getElementById("form1");
        form1.action = path;
        form1.submit();
    }
}

function inputcheck(str) {
    var myReg = /^[^\/\'\\\"#$%&\^\*]+$/;
    if (myReg.test(str)) return true;
    return false;
}