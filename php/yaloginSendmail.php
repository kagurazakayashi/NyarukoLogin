<?php
//require 'yaloginSQLSetting.php';
require 'class.phpmailer.php';
require 'class.smtp.php';
class Sendmail {
    private $sqlset;
    private $mail;
    private $appname;
    private $mailhtmlhand;

    function init() {
        $this->sqlset = new YaloginSQLSetting();
        $this->mail = new PHPMailer();
        $this->appname = $this->sqlset->db_appname;
        $this->mail->IsSMTP();
        $this->mail->Host = $this->sqlset->mail_SMTPHost;
        $this->mail->Port = $this->sqlset->mail_SMTPPort;
        $this->mail->SMTPAuth = true;
        $this->mail->CharSet = $this->sqlset->mail_CharSet;
        $this->mail->Encoding = $this->sqlset->mail_Encoding;
        $this->mail->Username = $this->sqlset->mail_Username;
        $this->mail->Password = $this->sqlset->mail_Password;
        $this->mail->From = $this->sqlset->mail_FromMail;
        $this->mail->FromName = $this->sqlset->mail_FromName;
        $this->mail->SMTPSecure = "ssl";
        $this->mailhtmlhead = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\"><html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"en\"><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><title>Server</title><style type=\"text/css\">*{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;-box-sizing:border-box}body,html{background-color:#f2f3f4;color:#333;font-family:'Lucida Grande','Lucida Sans Unicode',Helvetica,Arial,Verdana,sans-serif;font-size:14px;height:100%;line-height:21px;margin:0;text-align:center;word-spacing:-1px}#wrapper{height:100%;min-height:660px;position:relative}#content{padding:26px}#main{background:#fff;border:1px solid #d5d5d6;border-top-color:#e0e1e2;border-bottom-color:#c0c1c2;margin:0 auto 0 auto;padding:20px 26px 19px 26px;width:730px;-webkit-box-shadow:0 1px 3px rgba(0,0,0,0.1);-moz-box-shadow:0 1px 3px rgba(0,0,0,0.1);box-shadow:0 1px 3px rgba(0,0,0,0.1);-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px}h1{color:#000;font-size:28px;font-weight:normal;line-height:36px;margin:0 0 8px 0}p{line-height:21px;margin:0}#main img{margin-bottom:40px;margin-top:48px}#navigation{color:#646464;font-size:12px;margin-top:20px}a,a:link,a:visited,a:active{color:#4b8aba;text-decoration:none;margin:3px}a:hover{text-decoration:underline}#footer{bottom:6px;left:0;position:absolute;right:0}#footer a{font-size:12px;color:#b1b1b1}</style></head><body><div id=\"wrapper\"><div id=\"content\"><div id=\"main\">";
    }

    //发送测试邮件(mailtype:0)
    function sendtestmail($address) {
        echo "邮件配置……";
        $this->mail->Subject = "这是一封测试邮件";
        $this->mail->AddAddress($address, $address);
        $this->mail->IsHTML(true);
        $this->mail->AddEmbeddedImage("getvalidateimage.gif", "testimg", "getvalidateimage.gif");
        $this->mail->Body = "<b>你好</b>，<br/>这是一封<a href=\"#\">测试邮件</a>。<br/><img alt=\"helloweba\" src=\"cid:testimg\">";
        echo "邮件送出……";
        if(!$this->mail->Send()) { 
            echo "错误。"; 
            return seerrinfo($this->mail->ErrorInfo);
        } else { 
            echo "完成。"; 
            return "完成。"; 
        }
    }

    //发送注册验证邮件(mailtype:1)
    function sendverifymail($address, $username, $vcode, $timeout) {
        $this->mail->Subject = $this->appname." 用户注册确认邮件";
        $this->mail->AddAddress($address, $username);
        $this->mail->IsHTML(true);
        $mvurl = $this->sqlset->www_root."YashiUser-Activation.php";
        $ovurl = $mvurl."?acode=".$vcode;
        $html = $this->mailhtmlhead."<h1>&nbsp;</h1><h1>您好， ".$username." 。</h1><p>&nbsp;</p><p>您收到了这封邮件说明这个邮件地址已经请求在 ".$this->appname." 注册。</p><p>这封邮件用于确认是否是您本人邮箱申请，</p><p>如果您没有使用这个邮箱请求注册，可能是有人冒用您的邮箱，</p><p>请不要点邮件里的任何链接并直接删除这封邮件。</p><p>&nbsp;</p><p>这封邮件中的激活码有效期至 ".$timeout." 。</p><p>&nbsp;</p><p>&nbsp;</p><p>你的激活码是</p><p>".$vcode."</p><p>&nbsp;</p><p>你可以点击下面的链接一键激活</p><p><a href=\"".$ovurl."\">".$ovurl."</a></p><p>&nbsp;</p><p>也可以点击下面的链接输入你的激活码</p><p><a href=\"".$mvurl."\">".$mvurl."</a></p><p>&nbsp;</p><p id=\"navigation\">".$this->appname."</p></div></div></div></body></html>";
        if ($address == "test@test.test") {
            echo $html;
            return -2;
        }
        $this->mail->Body = $html;
        if(!$this->mail->Send()) { 
            return $this->seerrinfo($this->mail->ErrorInfo);
        } else { 
            return null;
        }
    }

    //发送找回密码邮件(mailtype:2)
    function sendretrievemail($address, $username, $vcode, $timeout) {
        $this->mail->Subject = $this->appname." 用户找回密码确认邮件";
        $this->mail->AddAddress($address, $username);
        $this->mail->IsHTML(true);
        $mvurl = $this->sqlset->www_root."YashiUser-Retrieveviamail.php";
        $ovurl = $mvurl."?acode=".$vcode;
        $html = $this->mailhtmlhead."<h1>&nbsp;</h1><h1>您好， ".$username." 。</h1><p>&nbsp;</p><p>您收到了这封邮件说明这个邮件地址已经请求在 ".$this->appname." 找回密码。</p><p>这封邮件用于确认是否是您本人邮箱申请找回密码，</p><p>如果您没有使用这个邮箱请求找回密码，可能是有人冒用您的邮箱，</p><p>请不要点邮件里的任何链接并直接删除这封邮件。</p><p>&nbsp;</p><p>这封邮件中的激活码有效期至 ".$timeout." 。</p><p>&nbsp;</p><p>&nbsp;</p><p>你的激活码是</p><p>".$vcode."</p><p>&nbsp;</p><p>你可以点击下面的链接一键激活</p><p><a href=\"".$ovurl."\">".$ovurl."</a></p><p>&nbsp;</p><p>也可以点击下面的链接输入你的激活码</p><p><a href=\"".$mvurl."\">".$mvurl."</a></p><p>&nbsp;</p><p id=\"navigation\">".$this->appname."</p></div></div></div></body></html>";
        if ($address == "test@test.test") {
            echo $html;
            return -2;
        }
        $this->mail->Body = $html;
        if(!$this->mail->Send()) { 
            return $this->seerrinfo($this->mail->ErrorInfo);
        } else { 
            return null;
        }
    }

    function seerrinfo($einfo) {
        if ($einfo == null) {
            return null;
        } else {
            return intval($einfo);
        }
    }
}
?>