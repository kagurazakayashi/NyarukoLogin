function toVaild(path) {

    var acodeformat = /^[0-9a-z]*$/g;
    var wrn = "";
    var wrni = 0;

    var v = document.getElementById("acode").value;
    if (v.length != 64) {
        wrni++; wrn = wrn + wrni + ". 激活码的长度不正确。\n";
    }
    if (acodeformat.test(v) == false) {
        wrni++; wrn = wrn + wrni + ". 激活码的格式不正确。\n";
    }
    if (wrni > 0) {
        alert("抱歉，你输入的内容有误：\n" + wrn);
    } else {
        var form1 = document.getElementById("form1");
        form1.action = path;
        form1.submit();
    }
}