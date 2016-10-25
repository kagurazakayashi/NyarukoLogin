function toVaild(path) {
    var btn = document.getElementById("submitbutton");
    btn.value = "正在登录...";
    btn.disabled = true;
    var wrn = "";
    var wrni = 0;
    var englishnum = /^[A-Za-z0-9]+$/;
    var urlsend = "";

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
    //document.getElementById("username").value = v.toLowerCase();
    urlsend = urlsend + "username=" + v.toLowerCase();

    var v = document.getElementById("userpassword").value;
    if (v.length < 6 || v.length > 64) {
        wrni++; wrn = wrn + wrni + ". 「密码」必须在 6 到 64 个字之间。\n";
    }

    var v = document.getElementById("userpassword2").value;
    if (v.length > 0) {
        if (v.length < 6 || v.length > 64) {
            wrni++; wrn = wrn + wrni + ". 「二级密码」必须在 6 到 64 个字之间。\n";
        }
    }

    var v = document.getElementById("vcode").value;
    if (v.length != 4) {
        wrni++; wrn = wrn + wrni + ". 「验证码」不正确。\n";
    }
    if (!inputcheck(v)) {
        wrni++; wrn = wrn + wrni + ". 「验证码」不能包含特殊字符。\n";
    }
    urlsend = urlsend + "&vcode=" + v;

    if (wrni > 0) {
        alert("抱歉，你输入的内容有误：\n" + wrn);
        document.getElementById("vcodeimg").src = 'php/validate_image.php?' + Math.random();
        document.getElementById("vcode").value = "";
    } else {
        //document.getElementById("userpassword").value = hash(document.getElementById("userpassword").value);
        urlsend = urlsend + "&userpassword=" + hash(document.getElementById("userpassword").value);
        if (document.getElementById("userpassword2").value.length > 0) {
            //document.getElementById("userpassword2").value = hash(document.getElementById("userpassword2").value);
            urlsend = urlsend + "&userpassword2=" + hash(document.getElementById("userpassword2").value);
        }
        // var form1 = document.getElementById("form1");
        // form1.action = path;
        // form1.submit();
        urlsend = urlsend + "&userversion=1&echomode=alert";
        psubmit(path,urlsend);
    }
    // btn.value = "用户登录";
    // btn.disabled = false;
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

function psubmit(path,urlsend) {
    //escape() //中文编码
    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
        //console.log("readyState="+xmlhttp.readyState+", status="+xmlhttp.status+", responseText="+xmlhttp.responseText);
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            alert(xmlhttp.responseText);
        }
    }
    xmlhttp.open("POST",path,true);
    xmlhttp.setRequestHeader("CONTENT-TYPE","application/x-www-form-urlencoded");
    xmlhttp.send(urlsend);
}