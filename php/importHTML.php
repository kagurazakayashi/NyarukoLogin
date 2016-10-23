<?php
/**
* 导入HTML
* KagurazakaYashi/YaLogin
*/
class importHTML {

    function __constrct() {

    }

    //返回文件内容
    function loadfile($localfile) {
        $handle = fopen($localfile, "r");
        $filesize = filesize($localfile);
        $filedata = fread($handle, $filesize);
        fclose($handle);
        return $filedata;
    }

    //返回文件调试页面
    function debug($localfile) {
        $handle = fopen($localfile, "r");
        $filesize = filesize($localfile);
        $fileinfoarr = stat($localfile);
        $fileinfo = "";
        while(list($key,$val)= each($fileinfoarr)) {
            if (!is_numeric($key)) {
                $fileinfo = $fileinfo."<p>$key : $val</p>";
            }
        }
        
        $filedata = fread($handle, $filesize);
        $filecode = htmlentities($filedata);
        fclose($handle);

        $html = '<!doctype html><html><head><meta charset="UTF-8"><title>Yashi Viewer - '.$localfile.'</title></head><body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0"><script>function yhvc_codebtn(){document.getElementById("yhv_codebtn").bgColor="#666666";document.getElementById("yhv_infobtn").bgColor="#000000";document.getElementById("yhv_showbtn").bgColor="#000000";document.getElementById("yhv_code").hidden=false;document.getElementById("yhv_info").hidden=true;document.getElementById("yhv_show").hidden=true;}function yhvc_infobtn(){document.getElementById("yhv_codebtn").bgColor="#000000";document.getElementById("yhv_infobtn").bgColor="#666666";document.getElementById("yhv_showbtn").bgColor="#000000";document.getElementById("yhv_code").hidden=true;document.getElementById("yhv_info").hidden=false;document.getElementById("yhv_show").hidden=true;}function yhvc_showbtn(){document.getElementById("yhv_codebtn").bgColor="#000000";document.getElementById("yhv_infobtn").bgColor="#000000";document.getElementById("yhv_showbtn").bgColor="#666666";document.getElementById("yhv_code").hidden=true;document.getElementById("yhv_info").hidden=true;document.getElementById("yhv_show").hidden=false;}</script><font color="#FFF"><table width="100%" border="0" bgcolor="#000000"><tr><td width="150" height="40" align="center">Yashi Viewer</td><td width="100" align="center" bgcolor="#666666" id="yhv_codebtn" onClick="yhvc_codebtn()" style="cursor:pointer;">Code</td><td width="100" align="center" id="yhv_infobtn" onClick="yhvc_infobtn()" style="cursor:pointer;">Info</td><td width="100" align="center" id="yhv_showbtn" onClick="yhvc_showbtn()" style="cursor:pointer;">Show</td><td align="right">'.$localfile.'</td></tr></table></font><div id="yhv_code">'.$filecode.'</div><div id="yhv_info" hidden="true">'.$fileinfo.'</div><div id="yhv_show" hidden="true">'.$filedata.'</div></body></html>';

        return $html;
    }

}

//<测试部分开始
//使用方法演示，正式使用时注释掉这些：

//输出测试
$ih = new importHTML();
echo $ih->debug("testbody.html");

//输出导入内容
// $ih = new importHTML();
// echo $ih->loadfile("testbody.html");

//测试部分结束>

?>