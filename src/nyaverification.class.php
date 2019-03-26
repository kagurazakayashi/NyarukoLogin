<?php
class nyaverification {
    /**
     * @description: 将发送的内容记录到数据库
     * @param String verification_category 发送信息类别
     * 1:站内信，2:电子邮件，3:短信
     * @param String recipient 收件人
     * @param String verification_message 发送的内容
     * @param Sting api_return_result API结果(可选)
     * @param String language 使用指定语言发送邮件(可选，默认自动检测)
     * @return: 
     */
    function sendinglog($verification_category,$recipient,$verification_message,$api_return_result=null,$language=null) {
        
    }
    /**
     * @description: 生成邮件内容
     * @param String language 使用指定语言发送邮件(可选，默认自动检测)
     * @return: 
     */
    function getmail($nickname,$language=null) {
        global $nlcore;
        $language = $nlcore->safe->getlanguage($language);
        $mailtemplatefilepath = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."template".DIRECTORY_SEPARATOR."signupmail.".$language.".html";
        //读入模板
        $mailtemplatefile = fopen($mailtemplatefilepath, "r") or die("Unable to open file!");
        $mailhtml = fread($mailtemplatefile,filesize($mailtemplatefilepath));
        fclose($mailtemplatefile);
        //生成结束时间
        $endtime = date('Y-m-d H:i:s', time()+$nlcore->cfg->verify->timeout["mail"]);
        //生成验证代码
        $vcode = $nlcore->safe->randstr();
        //生成验证网址
        $url = $nlcore->cfg->app->$appurl.DIRECTORY_SEPARATOR."nyaverification.php?code=".$vcode;
        $appname = $nlcore->cfg->app->appname;
        $time = date('Y-m-d H:i:s', time());
        //替换字符
        $findreplace = Array(
            "%%appname%%" => $appname,
            "%%nickname%%" => $nickname,
            "%%endtime%%" => $endtime,
            "%%url%%" => $url,
            "%%time%%" => $time
        );
        $mailhtml = $nlcore->safe->replacestr($mailhtml,$findreplace);
        //发送邮件

        //写入邮件发送记录
        
        
        return [$mailhtml,$appname,$nickname,$endtime,$url,$time];
    }
}
?>