<?php
class nyaverification {
    /**
     * @description: 生成邮件内容并发送
     * @param String userhash 用户哈希
     * @param String nickname 用户昵称
     * @param String mailto 收件人邮箱
     * @param String language 使用指定语言发送邮件(可选，默认自动检测)
     * @return Array 多个信息
     */
    function sendmail($userHash,$nickname,$mailto,$language=null) {
        global $nlcore;
        $language = $nlcore->safe->getlanguage($language);
        $mailtemplatefilepath = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."template".DIRECTORY_SEPARATOR."signupmail.".$language.".html";
        //读入模板
        $mailtemplatefile = fopen($mailtemplatefilepath, "r") or die("Unable to open file!");
        $mailhtml = fread($mailtemplatefile,filesize($mailtemplatefilepath));
        fclose($mailtemplatefile);
        //生成结束时间
        $endtime = $nlcore->safe->getdatetime(null,time()+$nlcore->cfg->verify->timeout["mail"])[1];
        //生成验证代码
        $vcode = $nlcore->safe->randstr();
        //写验证代码

        //生成验证网址
        $url = $nlcore->cfg->app->$appurl.DIRECTORY_SEPARATOR."nyaverification.php?code=".$vcode;
        $appname = $nlcore->cfg->app->appname;
        $time = $nlcore->safe->getdatetime()[1];
        //替换字符
        $findreplace = Array(
            "%%appname%%" => $appname,
            "%%nickname%%" => $nickname,
            "%%endtime%%" => $endtime,
            "%%url%%" => $url,
            "%%time%%" => $time
        );
        $mailhtml = $nlcore->safe->replacestr($mailhtml,$findreplace);
        //TODO: 发送邮件
        $api_return_result = null;
        //写入邮件发送记录
        $tableStr = $nlcore->cfg->db->tables["verification_sending_log"];
        $insertDic = array(
            "hash" => $userHash,
            "verification_category" => 2, //1:站内信，2:电子邮件，3:短信
            "recipient" => $mailto,
            "verification_message" => $mailhtml,
            "api_return_result" => $api_return_result
        );
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2030201);
        return [$mailhtml,$vcode];
    }
}
?>
