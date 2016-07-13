function toVaild(path) {
    var wrn = "";
    var wrni = 0;
    var intnum = /^[1-9]+[0-9]*]*$/;
    var floatnum = /^[0-9]+.?[0-9]*$/;
    var englishnum = /^[A-Za-z0-9]+$/;
    //var md5hash = require(["js/md5.js"]).md5hash;
    //var md6hash = require(["js/md6.js"]).md6hash;

    var v = document.getElementById("username").value;
    if (v.length < 3 || v.length > 32) {
        wrni++; wrn = wrn + wrni + ". 「用户名」必须在 3 到 32 个字之间。\n";
    }
    if (!englishnum.test(v)) {
        wrni++; wrn = wrn + wrni + ". 「用户名」只能由小写字母和数字组成。\n";
    }
    if (!inputcheck(v)) {
        wrni++; wrn = wrn + wrni + ". 「用户名」不能包含特殊字符。\n";
    }
    document.getElementById("username").value = v.toLowerCase();

    var v = document.getElementById("usernickname").value;
    // if (v == null || v == "") {
    //     document.getElementById("usernickname").value = document.getElementById("username").value;
    // }
    if (v.length > 0) {
        if (v.length > 32) {
            wrni++; wrn = wrn + wrni + ". 「昵称」必须在 3 到 32 个字之间。\n";
        }
        if (!inputcheck(v)) {
            wrni++; wrn = wrn + wrni + ". 「昵称」不能包含一些特定字符。\n";
        }
    }

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

    var v = document.getElementById("userpassword").value;
    if (v.length < 6 || v.length > 64) {
        wrni++; wrn = wrn + wrni + ". 「密码」必须在 6 到 64 个字之间。\n";
    }
    if (v != document.getElementById("userpasswordr").value) {
        wrni++; wrn = wrn + wrni + ". 「密码」两次输入不一致。\n";
    }

    var v = document.getElementById("userpassword2").value;
    if (v.length > 0) {
        if (v.length < 6 || v.length > 64) {
            wrni++; wrn = wrn + wrni + ". 「二级密码」必须在 6 到 64 个字之间。\n";
        }
    }
    if (v != document.getElementById("userpassword2r").value) {
        wrni++; wrn = wrn + wrni + ". 「二级密码」两次输入不一致。\n";
    }

    var v = document.getElementById("userpasswordquestion1").value;
    if (v.length > 0) {
        if (v.length > 64) {
            wrni++; wrn = wrn + wrni + ". 「密码提示问题1」不能超过 64 个字。\n";
        }
        if (!inputcheck(v)) {
            wrni++; wrn = wrn + wrni + ". 「密码提示问题1」不能包含特殊字符。\n";
        }
    }
    var v = document.getElementById("userpasswordanswer1").value;
    if (v.length > 0) {
        if (v.length > 64) {
            wrni++; wrn = wrn + wrni + ". 「密码提示答案1」不能超过 64 个字。\n";
        }
        if (!inputcheck(v)) {
            wrni++; wrn = wrn + wrni + ". 「密码提示答案1」不能包含特殊字符。\n";
        }
    }
    var v = document.getElementById("userpasswordquestion2").value;
    if (v.length > 0) {
        if (v.length > 64) {
            wrni++; wrn = wrn + wrni + ". 「密码提示问题2」不能超过 64 个字。\n";
        }
        if (!inputcheck(v)) {
            wrni++; wrn = wrn + wrni + ". 「密码提示问题2」不能包含特殊字符。\n";
        }
    }
    var v = document.getElementById("userpasswordanswer2").value;
    if (v.length > 0) {
        if (v.length > 64) {
            wrni++; wrn = wrn + wrni + ". 「密码提示答案2」不能超过 64 个字。\n";
        }
        if (!inputcheck(v)) {
            wrni++; wrn = wrn + wrni + ". 「密码提示答案2」不能包含特殊字符。\n";
        }
    }
    var v = document.getElementById("userpasswordquestion3").value;
    if (v.length > 0) {
        if (v.length > 64) {
            wrni++; wrn = wrn + wrni + ". 「密码提示问题3」不能超过 64 个字。\n";
        }
        if (!inputcheck(v)) {
            wrni++; wrn = wrn + wrni + ". 「密码提示问题3」不能包含特殊字符。\n";
        }
    }
    var v = document.getElementById("userpasswordanswer3").value;
    if (v.length > 0) {
        if (v.length > 64) {
            wrni++; wrn = wrn + wrni + ". 「密码提示答案3」不能超过 64 个字。\n";
        }
        if (!inputcheck(v)) {
            wrni++; wrn = wrn + wrni + ". 「密码提示答案3」不能包含特殊字符。\n";
        }
    }

    var v = document.getElementById("userbirthday").value;
    if (v.length > 0) {
        if (!datecheck(v)) {
            wrni++; wrn = wrn + wrni + ". 「生日」需要按照「YYYY-MM-DD」格式来输入日期。\n";
        }
        if (!inputcheck(v)) {
            wrni++; wrn = wrn + wrni + ". 「生日」不能包含特殊字符。\n";
        }
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
        document.getElementById("userpassword").value = hash(document.getElementById("userpassword").value);
        if (document.getElementById("userpassword2").value.length > 0) {
            document.getElementById("userpassword2").value = hash(document.getElementById("userpassword2").value);
        }
        var form1 = document.getElementById("form1");
        form1.action = path;
        form1.submit();
    }
    
}

function hash(str) {
    //var md5hash = new emd5hash();
    var md6hash = new emd6hash();
    var md6v = md6hash.hex(str, parseInt(128,10));
    //var md5v = md5hash.hex_md5(md6v);
    var md5v = hex_md5(md6v);
    return md5v;
}

function inputcheck(str) {
    var myReg = /^[^\/\'\\\"#$%&\^\*]+$/;
    if (myReg.test(str)) return true;
    return false;
}

function datecheck(date) {
    var result = date.match(/^(\d{1,4})(-|\/)(\d{1,2})\2(\d{1,2})$/);
    if (result == null)
        return false;
    var d = new Date(result[1], result[3] - 1, result[4]);
    return (d.getFullYear() == result[1] && (d.getMonth() + 1) == result[3] && d.getDate() == result[4]);
}