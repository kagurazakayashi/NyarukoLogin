<?php

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class nyaaliyun {
    public $resultMessage = '';
    /**
     * @description: 建立郵件驗證碼
     * @param String toAddress 目標地址，多個 email 地址可以用逗號分隔，最多100個地址。
     * @param String subject 郵件主題
     * @param String htmlBody 郵件 html 正文，限制28K。
     * @param String textBody 郵件 text 正文，限制28K。
     * @param String tagName 標籤
     * @param String fromAlias 發信人暱稱，長度小於15個字元。例如:發信人暱稱設定為”小紅”，發信地址為 test@example.com，收信人看到的發信地址為"小紅"<test@example.com>。預設為應用名。
     * @return Bool 郵件傳送是否成功
     * 可透過 $this->resultMessage 獲取詳細資訊
     */
    function singleSendMail(string $toAddress, string $subject, string $htmlBody, string $textBody = '', string $tagName = '', string $fromAlias = ''): bool {
        global $nlcore;
        $smtpCfg = $nlcore->cfg->verify->smtp;
        $aliMailCfg = $nlcore->cfg->verify->aliyun['mail'];
        $regionId = $aliMailCfg['RegionId'];
        $accountName = $smtpCfg['Username']; // *管理控制檯中配置的發信地址。
        $addressType = $aliMailCfg['AddressType']; // *地址型別。取值： 0：為隨機賬號 1：為發信地址
        $replyToAddress = strlen($smtpCfg['ReplyToAddr']) > 0 ? $smtpCfg['ReplyToAddr'] : "false"; // *使用管理控制檯中配置的回信地址（狀態必須是驗證透過）。
        $replyAddress = $smtpCfg['ReplyToAddr']; // 回信地址
        $replyAddressAlias = $smtpCfg['ReplyToName']; // 回信地址別稱
        $clickTrace = $aliMailCfg['ClickTrace']; // 1：為開啟資料跟蹤功能 0（預設）：為關閉資料跟蹤功能。
        if (strlen($fromAlias) > 0) $fromAlias = $smtpCfg['FromName'];
        // 建立第三方介面查詢
        $query = [];
        if (strlen($regionId) > 0) $query['RegionId'] = $regionId;
        if (strlen($accountName) > 0) $query['AccountName'] = $accountName;
        if (strlen($addressType) > 0) $query['AddressType'] = $addressType;
        if (strlen($replyToAddress) > 0) $query['ReplyToAddress'] = $replyToAddress;
        if (strlen($toAddress) > 0) $query['ToAddress'] = $toAddress;
        if (strlen($subject) > 0) $query['Subject'] = $subject;
        if (strlen($tagName) > 0) $query['TagName'] = $tagName;
        if (strlen($htmlBody) > 0) $query['HtmlBody'] = $htmlBody;
        if (strlen($textBody) > 0) $query['TextBody'] = $textBody;
        if (strlen($fromAlias) > 0) $query['FromAlias'] = $fromAlias;
        if (strlen($replyAddress) > 0) $query['ReplyAddress'] = $replyAddress;
        if (strlen($replyAddressAlias) > 0) $query['ReplyAddressAlias'] = $replyAddressAlias;
        if (strlen($clickTrace) > 0) $query['ClickTrace'] = $clickTrace;
        $accessKeyIdSecret = $aliMailCfg['accessKeyIdSecret'];
        AlibabaCloud::accessKeyClient($accessKeyIdSecret[0], $accessKeyIdSecret[1])
            ->regionId($regionId)
            ->asDefaultClient();
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dm')
                // ->scheme('https') // https | http
                ->version('2015-11-23')
                ->action('SingleSendMail')
                ->method('POST')
                ->host('dm.aliyuncs.com')
                ->options([
                    'query' => $query,
                ])
                ->request();
            $this->resultMessage = $result->toArray();
            return true;
        } catch (ClientException $e) {
            $this->resultMessage = $e->getErrorMessage();
            return false;
        } catch (ServerException $e) {
            $this->resultMessage = $e->getErrorMessage();
            return false;
        }
    }
    /**
     * @description: 建立簡訊驗證碼
     * @param String phoneNumbers
     * - 接收簡訊的手機號碼。格式：
     *   - 國內簡訊：11位手機號碼，例如15951955195。
     *   - 國際/港澳臺訊息：國際區號+號碼，例如85200000000。
     * - 支援對多個手機號碼傳送簡訊，手機號碼之間以英文逗號（,）分隔。上限為1000個手機號碼。批次呼叫相對於單條呼叫及時性稍有延遲。
     *   - 驗證碼簡訊建議使用單獨傳送的方式。
     * @param Int code 6位驗證碼
     * @return Bool 簡訊傳送是否成功
     * 可透過 $this->resultMessage 獲取詳細資訊
     */
    function sendSms(string $phoneNumbers, int $code): bool {
        global $nlcore;
        $aliSmsCfg = $nlcore->cfg->verify->aliyun['sms'];
        $regionId = $aliSmsCfg['RegionId'];
        $signName = $aliSmsCfg['SignName'];
        $templateCode = $aliSmsCfg['TemplateCode'];
        $smsUpExtendCode = $aliSmsCfg['SmsUpExtendCode'];
        $outId = $aliSmsCfg['OutId'];
        // 建立第三方介面查詢
        $query = [];
        if (strlen($regionId) > 0) $query['RegionId'] = $regionId;
        if (strlen($phoneNumbers) > 0) $query['PhoneNumbers'] = $phoneNumbers;
        if (strlen($signName) > 0) $query['SignName'] = $signName;
        if (strlen($templateCode) > 0) $query['TemplateCode'] = $templateCode;
        $query['TemplateParam']  = json_encode(['code' => strval($code)]);
        if (strlen($smsUpExtendCode) > 0) $query['SmsUpExtendCode'] = $smsUpExtendCode;
        if (strlen($outId) > 0) $query['OutId'] = $outId;
        $accessKeyIdSecret = $aliSmsCfg['accessKeyIdSecret'];
        AlibabaCloud::accessKeyClient($accessKeyIdSecret[0], $accessKeyIdSecret[1])
            ->regionId($regionId)
            ->asDefaultClient();
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => $query,
                ])
                ->request();
            $this->resultMessage = $result->toArray();
            if ($this->resultMessage && isset($this->resultMessage["Code"]) && strcmp($this->resultMessage["Code"], "OK") == 0) {
                return true;
            } else {
                return false;
            }
        } catch (ClientException $e) {
            $this->resultMessage = $e->getErrorMessage();
            return false;
        } catch (ServerException $e) {
            $this->resultMessage = $e->getErrorMessage();
            return false;
        }
    }
}
