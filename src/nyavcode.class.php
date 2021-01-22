<?php
require_once "nyaverification.class.php";

use PHPMailer\PHPMailer\PHPMailer; // composer require phpmailer/phpmailer
/**
 * @description: 簡訊和郵箱驗證碼的建立和認證
 * @package NyarukoLogin
 */
class nyavcode {
    private $module = "";
    private $code = 000000;
    /**
     * @description: 建立簡訊或郵箱驗證碼
     * @param Array argReceived 客戶端提交資訊陣列
     * @return Array 可返回客戶端的資訊
     */
    function getvcode($argReceived): array {
        global $nlcore;
        $modulei = 0; //1.sms,2.mail,3.test
        // 檢查是否提供目標
        $to = isset($argReceived["to"]) ? $argReceived["to"] : $nlcore->msg->stopmsg(2000101);
        // 檢查是否提供目標型別
        if (
            isset($argReceived["type"]) &&
            (strcmp($argReceived["type"], "sms") != 0 || strcmp($argReceived["type"], "mail") != 0) ||
            strcmp($argReceived["type"], "test") != 0
        ) {
            if ($argReceived["type"] == "sms") $modulei = 1;
            else if ($argReceived["type"] == "mail") $modulei = 2;
            else if ($argReceived["type"] == "test") $modulei = 3;
        } else {
            // 如果沒有提供目標型別，自動判斷目標型別
            if (is_numeric($to)) {
                if ($nlcore->safe->isPhoneNumCN($to)) {
                    $modulei = 1;
                } else {
                    $nlcore->msg->stopmsg(2000101); // 不支援的手機號碼格式
                }
            } else if ($nlcore->safe->isEmail($to)) {
                $modulei = 2;
            } else {
                $nlcore->msg->stopmsg(2000102);
            }
        }
        $returnArr = [];
        if ($modulei == 1) {
            $this->module = 'sms';
            $returnArr = $this->getvcode_sms($to);
        } else if ($modulei == 2) {
            $this->module = 'mail';
            $returnArr = $this->getvcode_mail($to);
        } else if ($modulei == 3) {
            $this->module = 'mail';
            $returnArr = $this->getvcode_test();
        }
        return $returnArr;
    }
    /**
     * @description: 建立簡訊驗證碼
     * @param String phoneNum 電話號碼
     * @return Array 可返回客戶端的資訊
     */
    function getvcode_sms(string $phoneNum): array {
        global $nlcore;
        $debug = $nlcore->cfg->verify->debug;
        $this->code = rand(100000, 999999);
        $timeout = $nlcore->cfg->verify->timeout[$this->module];
        $txt = $this->msgAddInfo($nlcore->cfg->app->appname, $timeout, $nlcore->cfg->verify->vcodetext_sns);
        // 错误代码：渠道消息
        // 連線到傳送簡訊介面
        $statusCode = 2000002;
        if (strlen($nlcore->cfg->verify->debugmail) > 0) {
            // 使用除錯郵箱模擬
            $statusCode = $this->smtp($nlcore->cfg->verify->debugmail, $phoneNum, $txt, $phoneNum, $txt, 0) ? 1030301 : 2030201;
        } else {
            // 實際傳送簡訊
            $engine = $nlcore->cfg->verify->engine['sms'];
            if ($engine == 1) {
                $ali = new nyaaliyun();
                $statusCode = $ali->sendSms($phoneNum, $this->code) ? 1030301 : 2030201;
                $resultMessage = '-' . json_encode($ali->resultMessage);
            } else {
                $nlcore->msg->stopmsg(2040203, $engine);
            }
        }
        if ($statusCode < 2000000) $this->save(); // 儲存驗證碼
        $this->saveHistory($phoneNum, $txt, strval($statusCode . $resultMessage));
        $returnClientData = $nlcore->msg->m(0, $statusCode);
        if ($debug) $returnClientData['debug'] = $this->code;
        return $returnClientData;
    }
    /**
     * @description: 建立郵件驗證碼
     * @param String mailAddr 收件人電子郵件地址
     * @param String mailName 收件人名字
     * @return Array 可返回客戶端的資訊
     */
    function getvcode_mail(string $mailAddr, string $mailName = ""): array {
        global $nlcore;
        $debug = $nlcore->cfg->verify->debug;
        if (strlen($mailName) == 0) $mailName = $mailAddr;
        // $code = $nlcore->safe->randhash("", false, false);
        $this->code = rand(100000, 999999);
        $timeout = $nlcore->cfg->verify->timeout[$this->module];
        $subject = $this->msgAddInfo($nlcore->cfg->app->appname, $timeout, $nlcore->cfg->verify->vcodetext_mail['Subject']);
        $body = $this->msgAddInfo($nlcore->cfg->app->appname, $timeout, $nlcore->cfg->verify->vcodetext_mail['Body']);
        $altBody = $this->msgAddInfo($nlcore->cfg->app->appname, $timeout, $nlcore->cfg->verify->vcodetext_mail['AltBody']);
        // 错误代码：渠道消息
        // 連線到傳送郵件介面
        $resultMessage = '';
        $statusCode = -1;
        if (strlen($nlcore->cfg->verify->debugmail) > 0) {
            // 使用除錯郵箱模擬
            $mailAddr = $nlcore->cfg->verify->debugmail;
            $statusCode = $this->smtp($mailAddr, $subject, $body, $mailName, $altBody) ? 1030300 : 2030200;
        } else {
            // 實際傳送郵件
            $engine = $nlcore->cfg->verify->engine['mail'];
            if ($engine == 0) {
                $statusCode = $this->smtp($mailAddr, $subject, $body, $mailName, $altBody) ? 1030300 : 2030200;
            } else if ($engine == 1) {
                $ali = new nyaaliyun();
                $statusCode = $ali->singleSendMail($mailAddr, $subject, $body, $altBody, $mailName) ? 1030300 : 2030200;
                $resultMessage = '-' . json_encode($ali->resultMessage);
            } else {
                $nlcore->msg->stopmsg(2040203, $engine);
            }
        }
        if ($statusCode < 2000000) $this->save(); // 儲存驗證碼
        $this->saveHistory($mailAddr, $altBody, strval($statusCode . $resultMessage));
        $returnClientData = $nlcore->msg->m(0, $statusCode, $mailAddr . $resultMessage);
        if ($debug) $returnClientData['debug'] = $this->code;
        return $returnClientData;
    }
    /**
     * @description: 建立测试驗證碼
     * @return Array 可返回客戶端的資訊
     */
    function getvcode_test(): array {
        global $nlcore;
        $this->code = rand(100000, 999999);
        $this->save(); // 儲存驗證碼
        return $nlcore->msg->m(0, 1030302, $this->code);
    }
    /**
     * @description: 傳送郵件
     * @param String mailAddr 收件人電子郵件地址
     * @param String subject 郵件標題
     * @param String body 郵件內容
     * @param String mailName 收件人名字
     * @param String altBody 客戶端不支援 HTM L則顯示此內容
     * @param Int isHTML 是否以 HTML 文档格式发送（替代配置文件） 0 / 1
     * @return Bool 郵件傳送是否成功
     */
    function smtp(string $mailAddr, string $subject, string $body = "", string $mailName = "", string $altBody = "", int $isHTML = -1): bool {
        global $nlcore;
        $smtp = $nlcore->cfg->verify->smtp;
        $mail = new PHPMailer(true);
        if (strlen($smtp['CharSet']) > 0) $mail->CharSet = $smtp['CharSet'];
        if (strlen($smtp['SMTPDebug']) > 0) $mail->SMTPDebug = $smtp['SMTPDebug'];
        $mail->isSMTP();
        $mail->Host = $smtp['Host'];
        $mail->Port = $smtp['Port'];
        if (strlen($smtp['SMTPAuth']) > 0) $mail->SMTPAuth = $smtp['SMTPAuth'];
        $mail->Username = $smtp['Username'];
        if (strlen($smtp['Password']) > 0) $mail->Password = $smtp['Password'];
        if (strlen($smtp['SMTPSecure']) > 0) $mail->SMTPSecure = $smtp['SMTPSecure'];
        if ($isHTML >= 0) {
            $mail->isHTML = ($isHTML == 1) ? true : false;
        } else {
            $mail->isHTML = $smtp['isHTML'];
        }
        if (strlen($smtp['FromAddr']) > 0 && strlen($smtp['FromName']) > 0) $mail->setFrom($smtp['FromAddr'], $smtp['FromName']);
        if (strlen($smtp['ReplyToAddr']) > 0 && strlen($smtp['ReplyToName']) > 0) $mail->addReplyTo($smtp['ReplyToAddr'], $smtp['ReplyToName']);
        if (strlen($mailAddr) > 0 && strlen($mailName) > 0) $mail->addAddress($mailAddr, $mailName);
        if (strlen($subject) > 0) $mail->Subject = $subject;
        if (strlen($body) > 0) $mail->Body = $body;
        if (strlen($altBody) > 0) $mail->AltBody = $altBody;
        return $mail->send();
    }
    /**
     * @description: 傳送一封測試郵件到 $nlcore->cfg->verify->debugmail
     * @return Array 可返回客戶端的資訊
     */
    function sendtestmailhtm(): array {
        global $nlcore;
        $time = date('Y-m-d H:i:s', time());
        $body = '<!doctype html><html xmlns=http://www.w3.org/1999/xhtml><head><meta content="text/html; charset=utf-8"http-equiv=Content-Type><title>TEST MAIL</title></head><body><h1>TEST MAIL<h1><hr/><h3>' . $time . '</h3></body></html>';
        $alt = '[ TEST MAIL ] ' . $time;
        $statusCode = ($this->smtp($nlcore->cfg->verify->debugmail, "TEST MAIL", $body, "", $alt)) ? 1030300 : 2030200;
        $returnClientData = $nlcore->msg->m(0, $statusCode, $nlcore->cfg->verify->debugmail);
        return $returnClientData;
    }
    /**
     * @description: 傳送一封自定義純文字內容測試郵件
     * @return Array 可返回客戶端的資訊
     */
    function sendtestmailtxt($body): array {
        global $nlcore;
        $time = date('Y-m-d H:i:s', time());
        $statusCode = ($this->smtp($nlcore->cfg->verify->debugmail, "TEST MAIL " . $time, $body, "", $body, 0)) ? 1030300 : 2030200;
        $returnClientData = $nlcore->msg->m(0, $statusCode, $nlcore->cfg->verify->debugmail);
        return $returnClientData;
    }
    /**
     * @description: 建立 Redis 鍵名
     * @return String 鍵名
     */
    function redisKeyName(): string {
        global $nlcore;
        return $nlcore->cfg->db->redis_tables["vcode2"] . $this->module . '_' . $nlcore->sess->appToken;
    }
    /**
     * @description: 儲存驗證碼
     */
    function save(): void {
        global $nlcore;
        $timeout = $nlcore->cfg->verify->timeout[$this->module];
        $cd = $nlcore->cfg->verify->cd[$this->module];
        if ($nlcore->db->initRedis()) {
            // 嘗試儲存到 Redis
            $redis = $nlcore->db->redis;
            $key = $this->redisKeyName($this->module);
            if ($redis->exists($key)) {
                // 如果有舊的驗證碼，先檢查時間是否允許再重新獲取下一個驗證碼
                if ($redis->TTL($key) > ($timeout - $cd)) {
                    $nlcore->msg->stopmsg(2020601, strval($timeout - $cd));
                } else {
                    // 移除舊的驗證碼
                    $redis->del($key);
                }
            }
            // 建立 val : 驗證碼,重試次數
            $val = $this->code . ',0';
            // 儲存驗證碼
            $redis->setex($key, $timeout, $val);
        } else {
            // 嘗試儲存到 MySQL
            $tableStr = $nlcore->cfg->db->tables['encryption'];
            $columnArr = ['vc2time'];
            $whereDic = ['apptoken' => $nlcore->sess->appToken];
            $result = $nlcore->db->select($columnArr, $tableStr, $whereDic);
            if ($result[0] >= 2000000) {
                $nlcore->msg->stopmsg(2020601);
            } else if ($result[0] == 1010000) {
                // 如果有舊的驗證碼，先檢查時間是否允許再重新獲取下一個驗證碼
                $data = $result[2][0];
                $starttime = $data['vc2time'];
                $ctime = time() - strtotime($starttime);
                if ($ctime < $cd) {
                    $nlcore->msg->stopmsg(2020601, strval($ctime));
                } else {
                    // 移除舊的驗證碼
                    $this->removeSqlvcode();
                }
            }
            // 儲存驗證碼
            $updateDic = [
                'vc2code' => strval($this->code),
                'vc2time' => $nlcore->safe->getdatetime(),
                'vc2type' => $this->module,
                'vc2try' => 0
            ];
            $result = $nlcore->db->update($updateDic, $tableStr, $whereDic);
            if ($result[0] >= 2000000) {
                $nlcore->msg->stopmsg(2020601);
            }
        }
    }
    /**
     * @description: 檢查驗證碼是否正確，並建立預分配令牌（客戶端輸入）
     * @param  array argReceived 客戶端提交資訊陣列
     * @return array 準備返回客戶端的資訊
     */
    function chkVCode(array $argReceived):array {
        global $nlcore;
        // 檢查輸入
        if (!isset($argReceived["vcode"])) {
            $nlcore->msg->stopmsg(2020600, 'null');
        }
        if (!isset($argReceived["type"])) {
            $nlcore->msg->stopmsg(2020605, 'null');
        }
        if (strcmp($argReceived["type"], "sms") != 0 && strcmp($argReceived["type"], "mail") != 0) {
            $nlcore->msg->stopmsg(2020605, $argReceived["type"]);
        }
        $this->check($argReceived["vcode"], $argReceived["type"]);
        // 建立預分配令牌 [新的預分配令牌,起始時間,結束時間]
        $preTokenArr = $nlcore->sess->preTokenNew();
        $returnArr = $nlcore->msg->m(0, 1000000);
        if (count($preTokenArr) >= 3) {
            $returnArr["pretoken"] = $preTokenArr[0];
            $returnArr["pretokenstart"] = $preTokenArr[1];
            $returnArr["pretokenend"] = $preTokenArr[2];
        }
        return $returnArr;
    }

    /**
     * @description: 檢查驗證碼是否正確
     * @param Int code 驗證碼
     * @param String module 功能模块
     */
    function check(int $code, string $module = ""): void {
        global $nlcore;
        if ($code < 100000 || $code > 999999) $nlcore->msg->stopmsg(2020600);
        if (strlen($module) > 0) $this->module = $module;
        if ($nlcore->db->initRedis()) {
            // 嘗試從 Redis 載入
            $redis = $nlcore->db->redis;
            $key = $this->redisKeyName($this->module);
            // 檢查是否有驗證碼資料
            if ($redis->exists($key)) {
                $vals = explode(',', $redis->get($key));
                $saveCode = intval($vals[0]);
                $saveTry = intval($vals[1]);
                // 檢查驗證碼是否匹配
                if ($saveCode != $code) {
                    // 不匹配，增加重試次數
                    $saveTry++;
                    if ($saveTry > $nlcore->cfg->verify->maxtry[$this->module]) {
                        $redis->del($key);
                        $nlcore->msg->stopmsg(2020602);
                    } else {
                        // 儲存新的重試次數
                        $val = $saveCode . ',' . $saveTry;
                        $redis->setex($key, $redis->TTL($key), $val);
                    }
                    $nlcore->msg->stopmsg(2020600);
                }
            } else {
                $nlcore->msg->stopmsg(2020604);
            }
            // 刪除已經驗證透過的資訊
            $redis->del($key);
            $this->removeSqlvcode();
        } else {
            // 嘗試從 MySQL 載入
            // 檢查是否有驗證碼資料
            $tableStr = $nlcore->cfg->db->tables['encryption'];
            $columnArr = ['vc2code', 'vc2time', 'vc2type', 'vc2try'];
            $whereDic = ['apptoken' => $nlcore->sess->appToken];
            $result = $nlcore->db->select($columnArr, $tableStr, $whereDic);
            if ($result[0] == 1010000) {
                $data = $result[2][0];
                $saveCode = intval($data['vc2code']);
                $saveTry = intval($data['vc2try']);
                $saveType = $data['vc2type'];
                $saveTime = strtotime($data['vc2time']);
                // 檢查驗證碼是否匹配、是否超時
                if (time() - $saveTime < $nlcore->cfg->verify->timeout[$this->module]) {
                    // 超时，删除条目
                    $this->removeSqlvcode();
                    $nlcore->msg->stopmsg(2020604);
                } else if (strcmp($saveType, $this->module) == 0 && $saveCode != $code) {
                    // 不匹配，增加重試次數
                    $saveTry++;
                    if ($saveTry > $nlcore->cfg->verify->maxtry[$this->module]) {
                        $this->removeSqlvcode();
                        $nlcore->msg->stopmsg(2020602);
                    } else {
                        // 儲存新的重試次數
                        $updateDic = [
                            'vc2try' => strval($saveTry)
                        ];
                        $result = $nlcore->db->update($updateDic, $tableStr, $whereDic);
                        if ($result[0] >= 2000000) {
                            $nlcore->msg->stopmsg(2020602);
                        }
                        $nlcore->msg->stopmsg(2020600);
                    }
                }
            } else {
                $nlcore->msg->stopmsg(2020604);
            }
        }
    }
    /**
     * @description: 儲存資訊傳送歷史記錄
     * @param String sender 郵箱或手機號
     * @param String txt 傳送文字
     * @param String resultinfo 傳送結果
     */
    function saveHistory(string $sender, string $txt, string $resultinfo) {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables['history'];
        $insertDic = [
            'userhash' => $nlcore->sess->appToken,
            'apptoken' => $nlcore->sess->userHash,
            'ipid' => strval($nlcore->sess->ipId),
            'operation' => 'USER_SEND_' . strtoupper($this->module),
            'sender' => $sender,
            'process' => $txt,
            'result' => $resultinfo
        ];
        $nlcore->db->insert($tableStr, $insertDic);
    }
    /**
     * @description: 移除 SQL 中的舊驗證碼
     */
    function removeSqlvcode(): void {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables['encryption'];
        $whereDic = ['apptoken' => $nlcore->sess->appToken];
        $updateDic = [
            'vc2code' => null,
            'vc2time' => null,
            'vc2type' => null,
            'vc2try' => 0
        ];
        $result = $nlcore->db->update($updateDic, $tableStr, $whereDic);
        if ($result[0] >= 2000000) {
            $nlcore->msg->stopmsg(2020602);
        }
    }
    /**
     * @description: 建立驗證碼，並向傳送文字內填充資訊
     * @param String appname 應用顯示名稱
     * @param Int timeout 多少秒有效
     * @param String template 文字模板
     * @return String 替換後的文字
     */
    function msgAddInfo(string $appname, int $timeout, string $template): string {
        $time = strval($timeout / 60);
        $txt = str_replace(['{code}', '{time}', '{appname}'], [strval($this->code), $time, $appname], $template);
        return $txt;
    }
}
