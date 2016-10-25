<?php
/**
* 导入HTML
* KagurazakaYashi/YaLogin/YashiImport
*/
class importHTML {

    //截取期间起始标记
    public $bodystart = "<body>";
    //截取期间结束标记
    public $bodyend = "</body>";
    //内容替换数组(原文=>替文)
    public $replace = null;
    //版本号
    private $ver = "1.3";

    //返回文件内容
    //$localfile 文件相对路径。
    //$body=ture 将只摘录来源中<body></body>中的内容，可以通过预先改变属性 $bodystart 和 $bodyend 自定义裁剪区域。
    function loadfile($localfile,$body=false) {
        if(file_exists($localfile)) {
            $handle = fopen($localfile, "r");
            $filesize = filesize($localfile);
            $filedata = fread($handle, $filesize);
            fclose($handle);
            if ($body) {
                $filedata = $this->cutbody($filedata);
            }
            if ($this->replace) {
                while(list($key,$val)= each($this->replace)) {
                    $filedata = str_replace($key,$val,$filedata);
                }
            }
            return $filedata;
        } else {
            return "<b>ERROR: File \"".$localfile."\" does not exist.</b>";
        }
    }

    //返回文件调试页面
    //$localfile 文件相对路径。
    //$body=ture 将只摘录来源中<body></body>中的内容，可以通过预先改变属性 $bodystart 和 $bodyend 自定义裁剪区域。
    //$show=ture 允许输出网页,显示会乱版的代码时可以临时关闭它。
    function debug($localfile,$body=false,$show=true) {
        $filecode = "<b>ERROR: File \"".$localfile."\" does not exist.</b>";
        $fileinfo = $filecode;
        $filedata = $filecode;
        $bodyinf = "";
        if(file_exists($localfile)) {
            $handle = fopen($localfile, "r");
            $filesize = filesize($localfile);
            $fileinfoarr = stat($localfile);
            $fileinfo = "";
            $filedata = fread($handle, $filesize);
            $fileinfo = $fileinfo."<p>File : ".$localfile."</p><hr><p>String Size : ".strlen($filedata)."</p><hr>";
            fclose($handle);
            if ($body) {
                $filedata = $this->cutbody($filedata);
                $bodyinf = "(".htmlentities($this->bodystart)."|".htmlentities($this->bodyend).")";
                $fileinfo = $fileinfo."<p>Cut : ".$bodyinf."</p><hr>";
            } else {
                $fileinfo = $fileinfo."<p>Cut : NO</p><hr>";
            }
            if ($this->replace) {
                $repdebug = "";
                while(list($key,$val)= each($this->replace)) {
                    $repcount = 0;
                    $filedata = str_replace($key,$val,$filedata,$repcount);
                    $repdebug = '"'.$key.'" to "'.$val.'" x'.$repcount.'  ';
                }
                $fileinfo = $fileinfo."<p>Replace : ".$repdebug."</p><hr>";
            } else {
                $fileinfo = $fileinfo."<p>Replace : NO</p><hr>";
            }
            $filecode = htmlentities($filedata);
            $fileinfo = $fileinfo."<p>Code Size : ".strlen($filedata)."</p><hr>";
            while(list($key,$val)= each($fileinfoarr)) {
                if (!is_numeric($key)) {
                    $fileinfo = $fileinfo."<p>$key : $val</p><hr>";
                }
            }
            $fileinfo = $fileinfo."<p><center>YashiImport ".$this->ver." - Kagurazaka Yashi</center></p>";
            if (!$show) {
                $filedata = "<b>ERROR: This feature has been disabled.</b>";
            }
        }

        $html = '<!doctype html><html><head><meta charset="UTF-8"><title>YashiImport '.$this->ver.' - '.$localfile.'</title></head><body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0"><script>function yhvc_codebtn(){document.getElementById("yhv_codebtn").bgColor="#666666";document.getElementById("yhv_infobtn").bgColor="#000000";document.getElementById("yhv_showbtn").bgColor="#000000";document.getElementById("yhv_code").hidden=false;document.getElementById("yhv_info").hidden=true;document.getElementById("yhv_show").hidden=true;}function yhvc_infobtn(){document.getElementById("yhv_codebtn").bgColor="#000000";document.getElementById("yhv_infobtn").bgColor="#666666";document.getElementById("yhv_showbtn").bgColor="#000000";document.getElementById("yhv_code").hidden=true;document.getElementById("yhv_info").hidden=false;document.getElementById("yhv_show").hidden=true;}function yhvc_showbtn(){document.getElementById("yhv_codebtn").bgColor="#000000";document.getElementById("yhv_infobtn").bgColor="#000000";document.getElementById("yhv_showbtn").bgColor="#666666";document.getElementById("yhv_code").hidden=true;document.getElementById("yhv_info").hidden=true;document.getElementById("yhv_show").hidden=false;}</script><font color="#FFF"><table width="100%" border="0" bgcolor="#000000"><tr><td width="150" height="40" align="center">YashiImport '.$this->ver.'</td><td width="100" align="center" bgcolor="#666666" id="yhv_codebtn" onClick="yhvc_codebtn()" style="cursor:pointer;">Code</td><td width="100" align="center" id="yhv_showbtn" onClick="yhvc_showbtn()" style="cursor:pointer;">Show</td><td width="100" align="center" id="yhv_infobtn" onClick="yhvc_infobtn()" style="cursor:pointer;">Info</td><td width="100" align="center" id="yhv_yashibtn" onClick="window.location.href = \'https://github.com/cxchope/YashiLogin/blob/master/php/importHTML.php\'" style="cursor:pointer;">Update</td><td align="right">'.$localfile.$bodyinf.'</td></tr></table></font><div id="yhv_code"><p>'.$filecode.'</p></div><div id="yhv_info" hidden="true">'.$fileinfo.'</div><div id="yhv_show" hidden="true">'.$filedata.'</div></body></html>';

        return $html;
    }

    function cutbody($html) {
        $nhtmlarr = explode($this->bodystart,$html);
        if (count($nhtmlarr) != 2) {
            return "ERROR: Did not find the <body> that does not contain properties.";
        }
        $nhtmlarr = explode($this->bodyend,$nhtmlarr[1]);
        if (count($nhtmlarr) != 2) {
            return "ERROR: Did not find the <body> that does not contain properties.";
        }
        $nhtml = $nhtmlarr[0];
        return $nhtml;
    }

}

//<测试部分开始
//使用方法演示，正式使用时注释掉这些：

//输出导入内容
// $ih = new importHTML();
// $ih->replace = array("HTML"=>"PAGE"); //可选行
// echo $ih->loadfile("testbody.html",true);

//输出测试
$ih = new importHTML();
$ih->replace = array("HTML"=>"PAGE"); //可选行
echo $ih->debug("testbody.html",true,true);

//测试部分结束>

?>