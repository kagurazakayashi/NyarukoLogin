function fileSelected() {
    var file = document.getElementById('fileToUpload').files[0];
    if (file) {
        var fileSize = 0;
        if (file.size > 1024 * 1024)
            fileSize = (Math.round(file.size * 100 / (1024 * 1024)) / 100).toString() + 'MB';
        else
            fileSize = (Math.round(file.size * 100 / 1024) / 100).toString() + 'KB';
        console.log('Name: ' + file.name);
        console.log('Size: ' + fileSize);
        console.log('Type: ' + file.type);
        if (file.type.indexOf("image") == 0) {
            var reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = function(e) {
                var newUrl = this.result; // 图片base64化
                document.getElementById("fileuploadimageview").src = newUrl;
                document.getElementById("fileuploadsizestr").innerText = fileSize;
            };
        }
    }
}
var fileuploadprogressstr = document.getElementById("fileuploadprogressstr");
var uploadprogressv = document.getElementById("uploadprogressv");
function uploadfile() {
    document.getElementById("uploadbtnbox").style.display = "none";
    document.getElementById("uploadprogressbox").style.display = "block";
    document.getElementById("uploadprogress").style.display = "block";
    var file = document.getElementById('fileToUpload').files[0];
    var form = new FormData();
    form.append("apiver",document.getElementById("apiver").value);
    form.append("t",document.getElementById("ttt").value);
    form.append("appsecret",document.getElementById("appsecret").value);
    form.append("token",document.getElementById("token").value);
    form.append("file", file);
    var xhr = new XMLHttpRequest() || new ActiveXObject("Microsoft.XMLHTTP");
    xhr.open("post","test_uploadfile.php",true);
    xhr.onload = function(evt) {
        console.log(evt.currentTarget.responseText);
        const response = evt.currentTarget.responseText;
        var jsonobj = JSON.parse(response);
        if (typeof jsonobj == 'object' && jsonobj ) {
            mdui.alert(print_r(jsonobj), '上传结果');
        } else {
            mdui.alert(response, '上传结果');
        }
        uploadfileend();
    }
    xhr.onerror = function(evt) {
        console.log(evt.currentTarget.responseText);
        mdui.alert(evt.currentTarget.response, '上传失败');
        uploadfileend();
    }
    xhr.upload.onloadstart = function(evt) {
        //开始上传
        uploadprogressv.className = "mdui-progress-indeterminate mdui-color-theme-accent";
    }
    xhr.upload.onprogress =function(evt){
        //上传进度
        const loaded = evt.loaded;
        const total = evt.total;
        const percentage = loaded / total * 100;
        fileuploadprogressstr.innerText = parseInt(percentage);
        if (loaded >= total) {
            uploadprogressv.style.width = "";
            if (uploadprogressv.className != "mdui-progress-indeterminate mdui-color-theme-accent") uploadprogressv.className = "mdui-progress-indeterminate mdui-color-theme-accent";
        } else {
            if (uploadprogressv.className != "mdui-progress-determinate mdui-color-theme-accent") uploadprogressv.className = "mdui-progress-determinate mdui-color-theme-accent";
            uploadprogressv.style.width = percentage + "%";
        }
    };
    xhr.send(form);
}
function uploadfileend() {
    uploadprogressv.style.width = "0%";
    fileuploadprogressstr.innerText = "0";
    document.getElementById("uploadbtnbox").style.display = "block";
    document.getElementById("uploadprogressbox").style.display = "none";
    document.getElementById("uploadprogress").style.display = "none";
}
function print_r(theObj) {
    var retStr = '';
    if (typeof theObj == 'object') {
        retStr += '<code>';
        for (var p in theObj) {
            if (typeof theObj[p] == 'object') {
                retStr += '<div><b>['+p+'] => ' + typeof(theObj) + '</b></div>';
                retStr += '<div style="padding-left:20px;">' + print_r(theObj[p]) + '</div>';
            } else {
                retStr += '<div>['+p+'] => <b>' + theObj[p] + '</b></div>';
            }
        }
        retStr += '</code>';
    }
    return retStr;
}
