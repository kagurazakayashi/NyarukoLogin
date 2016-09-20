function toVaild(path) {
    var wrn = "";
    var wrni = 0;
    var intnum = /^[1-9]+[0-9]*]*$/;
    var floatnum = /^[0-9]+.?[0-9]*$/;
    var englishnum = /^[A-Za-z0-9]+$/;
    var acodeformat = /^[0-9a-z]*$/g;
    //var md5hash = require(["js/md5.js"]).md5hash;
    //var md6hash = require(["js/md6.js"]).md6hash;

    var v = document.getElementById("mcode").value;
    if (v.length != 64) {
        wrni++; wrn = wrn + wrni + ". 邮箱验证码的长度不正确。\n";
    }
    if (acodeformat.test(v) == false) {
        wrni++; wrn = wrn + wrni + ". 邮箱验证码的格式不正确。\n";
    }
    document.getElementById("mcode").value = v.toLowerCase();

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
    var md6v = md6hash.hex(str);
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