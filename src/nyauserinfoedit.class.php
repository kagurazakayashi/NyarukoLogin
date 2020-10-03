<?php

/**
 * @description: 檢查使用者資訊
 * @package NyarukoLogin
 */
class userInfoEdit {
    private $argReceived;
    private $userHash;
    private $tableStr;
    private $updateDic = [];
    private $whereDic;
    /**
     * @description: 構造：載入從客戶端輸入的資訊，取出所需資訊
     * @param nyacore NyarukoLogin 核心
     * @param Array argReceived 客戶端輸入的資訊（$nlcore->sess->decryptargv）
     * @param Array userHash 使用者雜湊（$nlcore->safe->userHash）
     *   如果不提供使用者雜湊，則只進行有限的檢查，並且不能進行資料庫更新。
     */
    function __construct(array $argReceived, string $userHash = "") {
        global $nlcore;
        $this->argReceived = $argReceived;
        $this->tableStr = $nlcore->cfg->db->tables["info"];
        // 檢查使用哪個使用者操作
        if (strlen($userHash) > 0) {
            $this->userHash = $userHash;
            if (isset($this->argReceived["userhash"]) && strcmp($this->userHash, $this->argReceived["userhash"]) != 0) {
                $subuser = $this->argReceived["userhash"];
                if (strcmp($this->userHash, $subuser) != 0) {
                    if (!$nlcore->safe->is_rhash64($subuser)) $nlcore->msg->stopmsg(2070003, "S-" . $subuser);
                    $issub = $nlcore->func->issubaccount($this->userHash, $subuser)[0];
                    if ($issub == false) $nlcore->msg->stopmsg(2070004, "S-" . $subuser);
                    $this->userHash = $subuser;
                }
            }
            $this->whereDic = ["userhash" => $this->userHash];
        }
    }
    /**
     * @description: 批量檢查並加入更新計劃
     * @param Array updateDic ["條目名稱"=>"條目內容"]，不传则直接从用户提交中搜索
     * @return Array 執行結果
     */
    function batchUpdate(array $updateDic = []): void {
        if (count($updateDic) == 0) $updateDic = $this->argReceived;
        if (isset($updateDic["name"])) {
            $this->verifyName($updateDic["name"]);
            $this->verifyNameId($updateDic["name"]);
        }
        if (isset($updateDic["gender"])) $this->verifyGender(intval($updateDic["gender"]), true);
        if (isset($updateDic["pronoun"])) $this->verifyPronoun($updateDic["pronoun"]);
        if (isset($updateDic["address"])) $this->verifyAddress($updateDic["address"]);
        if (isset($updateDic["profile"])) $this->verifyProfile($updateDic["profile"]);
        if (isset($updateDic["description"])) $this->verifyDescription($updateDic["description"]);
        if (isset($updateDic["image"])) $this->verifyImage($updateDic["image"]);
        if (isset($updateDic["background"])) $this->verifyBackground($updateDic["background"]);
    }
    /**
     * @description: 檢查輸入字元串
     * @param String str 字元串
     * @param String func 功能名稱
     */
    function verifyString(string $str, string $func): void {
        global $nlcore;
        if (mb_strlen($str, "utf-8") > $nlcore->cfg->app->maxLen[$func]) {
            // 太長
            $nlcore->msg->stopmsg(2040105, $str);
        }
        // 檢查異常符號
        $nlcore->safe->safestr($str, true, false);
        // 檢查敏感詞
        $nlcore->safe->wordfilter($str, true);
        $this->updateDic[$func] = $str;
    }
    /**
     * @description: 檢查輸入媒體檔案
     * @param String paths 媒體檔案路徑（逗號分隔）
     * @param String func 功能名稱
     */
    function verifyFile(string $paths, string $func): void {
        global $nlcore;
        $filesarr = explode(",", $paths);
        foreach ($filesarr as $nowfile) {
            if (!$nlcore->safe->ismediafilename($nowfile)) {
                $nlcore->msg->stopmsg(2050107, $nowfile);
            }
        }
        $this->updateDic[$func] = $paths;
    }
    /**
     * @description: 執行資料庫更新
     * @return Array 已更新的列名
     */
    function sqlc(): array {
        if (strlen($this->userHash) == 0 || !$this->whereDic || strlen($this->whereDic) == 0) return [];
        global $nlcore;
        $ukeys = array_keys($this->updateDic);
        $result = $nlcore->db->update($this->updateDic, $this->tableStr, $this->whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040604, implode(",", $ukeys));
        return $ukeys;
    }
    /**
     * @description: 檢查暱稱（會重新生成暱稱唯一碼）
     * @param String name 新的暱稱
     */
    function verifyName(string $name): void {
        $this->verifyString($name, "name");
    }
    /**
     * @description: 會重新生成暱稱唯一碼
     * @param String name 新的暱稱
     */
    function verifyNameId(string $name): void {
        if (strlen($this->userHash) == 0) return;
        global $nlcore;
        $nameid = $nlcore->func->genuserid($name, $this->userHash);
        $this->updateDic["nameid"] = $nameid;
    }
    /**
     * @description: 檢查人稱代詞
     * @param String pronoun 新的人稱代詞
     */
    function verifyPronoun(int $pronoun): void {
        global $nlcore;
        if ($pronoun < 0 || $pronoun > 2) $nlcore->msg->stopmsg(2040601, $pronoun);
        $this->updateDic["pronoun"] = $pronoun;
    }
    /**
     * @description: 檢查性別
     * @param Int gender 新的性別ID
     * @param Bool autoPronoun 自動檢查人稱代詞
     */
    function verifyGender(int $gender, bool $autoPronoun = true): void {
        global $nlcore;
        $genders = $nlcore->func->getgender($gender);
        if (count($genders) == 0) {
            $nlcore->msg->stopmsg(2040600, strval($gender));
        }
        $this->updateDic["gender"] = $gender;
        if ($autoPronoun) $this->verifyPronoun($genders[0]["person"]);
    }
    /**
     * @description: 檢查地址
     * @param String address 新的地址
     */
    function verifyAddress(string $address): void {
        $this->verifyString($address, "address");
    }
    /**
     * @description: 檢查簽名
     * @param String profile 新的簽名
     */
    function verifyProfile(string $profile): void {
        $this->verifyString($profile, "profile");
    }
    /**
     * @description: 檢查介紹
     * @param String description 新的介紹
     */
    function verifyDescription(string $description): void {
        $this->verifyString($description, "description");
    }
    /**
     * @description: 檢查頭像
     * @param String image 新的頭像路徑
     */
    function verifyImage(string $image): void {
        $this->verifyFile($image, "image");
    }
    /**
     * @description: 檢查背景
     * @param String background 新的背景路徑
     */
    function verifyBackground(string $background): void {
        $this->verifyFile($background, "background");
    }
}
