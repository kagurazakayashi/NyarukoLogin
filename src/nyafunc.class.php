<?php
declare(strict_types=1);

/**
 * 通用工具函式類
 *
 * 提供使用者登入憑據檢查、使用者存在性查詢、暱稱管理、
 * 歷史記錄寫入、檔案路徑處理、裝置資訊查詢等功能。
 *
 * @package NyarukoLogin
 */
class nyafunc {
    private $logfile = null; //记录详细调试信息到文件
    /**
     * 檢查登入憑據是電子郵件還是手機號碼
     *
     * @param string $loginstr 要檢查的登入憑據字串
     * @return int 0: 電子郵件, 1: 手機號碼（不符則透過 stopmsg 終止執行）
     */
    function logintype(string $loginstr): int {
        global $nlcore;
        $telareaarr = $nlcore->safe->telarea($loginstr);
        if ($nlcore->safe->isPhoneNumCN($telareaarr[1])) {
            return 1;
        } else if ($nlcore->safe->isEmail($loginstr)) {
            return 0;
        } else {
            $nlcore->msg->stopmsg(2020206);
            return -1;
        }
    }
    /**
     * 檢查指定資訊地址是否已經存在於資料庫
     *
     * @param int $logintype 要檢查的憑據型別：0: 電子郵件, 1: 手機號碼, 2: 雜湊
     * @param string $loginstr 要檢查的登入憑據字串
     * @return bool 是否已經存在（如果出現多個結果則透過 stopmsg 終止執行）
     */
    function isalreadyexists(int $logintype, string $loginstr): bool {
        global $nlcore;
        $logintypearr = ["mail", "tel", "hash"];
        $logintypestr = $logintypearr[$logintype];
        $whereDic = [$logintypearr[$logintype] => $loginstr];
        $result = $nlcore->db->scount($nlcore->cfg->db->tables["users"], $whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040100);
        $datacount = intval($result[2][0]["count(*)"]);
        if ($datacount == 0) {
            return false;
        } else if ($datacount == 1) {
            // $nlcore->msg->stopmsg(2040102);
            return true;
        } else {
            $nlcore->msg->stopmsg(2040101);
        }
    }
    /**
     * 生成使用者暱稱四位碼
     *
     * @param string $name 新的暱稱
     * @param string $userhash 使用者雜湊
     * @return int 新生成的四位數 ID
     */
    function genuserid(string $name, string $userhash): int {
        global $nlcore;
        $currid = [];
        // 檢查這個暱稱所對應的所有碼
        $tableStr = $nlcore->cfg->db->tables["info"];
        $columnArr = ["nameid"];
        $whereDic = ["name" => $name];
        $result = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        if ($result[0] == 1010001) {
            // 獲取所有已有暱稱碼
            $nameids = $result[2];
            if ($nameids) {
                foreach ($nameids as $nameid) {
                    $nowid = intval($nameid["nameid"]);
                    array_push($currid, $nowid);
                }
            }
        } else if ($result[0] >= 2000000) {
            $nlcore->msg->stopmsg(2040114);
        }
        // 查詢所有可用碼
        $nameidbook = [];
        for ($i = 1000; $i < 9999; $i++) {
            if (in_array($i, $currid)) continue;
            array_push($nameidbook, $i);
        }
        // 檢查還有沒有剩餘碼，在剩餘碼中隨機一個
        $nameidbookcount = count($nameidbook);
        if ($nameidbookcount == 0) $nlcore->msg->stopmsg(2040106, $name);
        $nameidi = rand(0, $nameidbookcount);
        return $nameidbook[$nameidi];
    }
    /**
     * 檢查該使用者是否已存在
     *
     * @param string|null $mergename 暱稱#四位碼 格式（可選）
     * @param string|null $name 暱稱（可選，若提供 mergename 則自動解析）
     * @param string|int|null $nameid 四位碼（可選）
     * @return bool 是否有此使用者
     */
    function useralreadyexists(string|null $mergename = null, string|null $name = null, string|int|null $nameid = null): bool {
        global $nlcore;
        if ($mergename) {
            $namearr = explode("#", $mergename);
            $nameid = end($namearr);
            if (count($namearr) > 2) {
                array_pop($namearr);
                $name = implode("#", $namearr);
            } else {
                $name = $namearr[0];
            }
        }
        $whereDic = [
            "name" => $name,
            "nameid" => $nameid
        ];
        $result = $nlcore->db->scount($nlcore->cfg->db->tables["info"], $whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040200);
        $datacount = $result[2][0]["count(*)"];
        if ($datacount > 0) return true;
        return false;
    }
    /**
     * 獲取所選性別資訊
     *
     * @param int $id 性別 ID（可選，-1 為不使用）
     * @param string $gender 性別名稱（可選）
     * @param string $localization 本地化性別名稱（可選）
     * @param int $list 性別列表 ID（可選，-1 使用預設）
     * @return array|null 性別資訊查詢結果
     */
    function getgender(int $id = -1, string $gender = "", string $localization = "", int $list = -1): array|null {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["gender"];
        $whereDic = [];
        if ($id > 0) $whereDic["id"] = $id;
        if (strlen($gender) > 0) $whereDic["gender"] = $gender;
        if (strlen($localization) > 0) $whereDic["localization"] = $localization;
        $whereDic["list"] = ($list >= 0) ? $list : $nlcore->cfg->app->genderlist;
        $result = $nlcore->db->select([], $tableStr, $whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040605);
        return $result[2];
    }
    /**
     * 檢查是否需要輸入驗證碼，並根據配置決定顯示哪種驗證碼
     *
     * @param int $loginfail 失敗次數計數
     * @return string 需要的驗證方式（空字串為不需要）
     */
    function needcaptcha(int $loginfail): string {
        global $nlcore;
        $needcaptcha = $nlcore->cfg->verify->needcaptcha;
        $nowmode = "";
        $nownum = 0;
        foreach ($needcaptcha as $key => $value) {
            if ($loginfail >= $value && $value > $nownum) {
                $nowmode = $key;
                $nownum = $value;
            }
        }
        return $nowmode;
    }
    /**
     * 寫入歷史記錄
     *
     * @param string $operation 操作名稱
     * @param int $code 回傳代碼
     * @param string $userHash 使用者雜湊
     * @param string $totpToken 應用識別碼
     * @param int $ipid IP 記錄 ID
     * @param string $sender 發送者
     * @param string|null $process 過程記錄（可選）
     * @param string|null $session 當前會話（可選）
     * @return void
     */
    function writehistory(string $operation, int $code, string $userHash, string $totpToken, int $ipid, string $sender, string|null $process = null, string|null $session = null): void {
        global $nlcore;
        $insertDic = [
            "userhash" => $userHash,
            "apptoken" => $totpToken,
            "operation" => $operation,
            "sender" => $sender,
            "ipid" => $ipid,
            "process" => $process,
            "result" => $code
        ];
        $tableStr = $nlcore->cfg->db->tables["history"];
        $result = $nlcore->db->insert($tableStr, $insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040112);
    }
    /**
     * 取出密碼提示問題
     *
     * @param string $userHash 使用者雜湊
     * @param bool $all true: 順序全部取出, false: 亂序取出隨機兩個
     * @return array 密碼提示問題陣列
     */
    function getquestion(string $userHash, bool $all = false): array {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["protection"];
        $columnArr = ["question1", "question2", "question3"];
        $whereDic = ["userhash" => $userHash];
        $result = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        if ($result[0] != 1010000) $nlcore->msg->stopmsg(2040301);
        $questions = $result[2][0];
        if (!isset($questions["question1"]) || $questions["question1"] == "" || !isset($questions["question2"]) || $questions["question2"] == "" || !isset($questions["question3"]) || $questions["question3"] == "") {
            $nlcore->msg->stopmsg(2040301);
        }
        $returnarr = [$questions["question1"], $questions["question2"], $questions["question3"]];
        shuffle($returnarr);
        array_pop($returnarr);
        return $returnarr;
    }
    /**
     * 取得使用者個人化資訊
     *
     * @param string $userHash 使用者雜湊
     * @param array $dbresult 自定義資料庫查詢返回結果輸入，用於合併查詢（可選，空陣列表示從 DB 查詢）
     * @param bool $getfileinfo 是否查詢檔案資訊（預設 true）
     * @param array $columnArr 要查詢的欄位
     * @return array 當前使用者資訊
     */
    function getuserinfo(string $userHash, array $dbresult = [], bool $getfileinfo = true, array $columnArr = ["userhash", "belong", "infotype", "name", "nameid", "gender", "pronoun", "age", "address", "profile", "description", "image", "background"]): array {
        global $nlcore;
        $result = null;
        if ($dbresult) {
            $result = $dbresult;
        } else {
            $tableStr = $nlcore->cfg->db->tables["info"];
            $whereDic = ["userhash" => $userHash];
            $result = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        }
        if ($result[0] != 1010000 || !isset($result[2][0])) {
            $nlcore->msg->stopmsg(2040206, $userHash);
        }
        $nowuserinfo = $result[2][0];
        if ($getfileinfo) {
            $filenone = ["path" => ""];
            if (isset($nowuserinfo["image"])) {
                $nowuserinfo["image"] = strlen($nowuserinfo["image"]) > 1 ? $this->imagesurl($nowuserinfo["image"], $filenone) : [$filenone];
            } else {
                $nowuserinfo["image"] = [$filenone];
            }
            if (isset($nowuserinfo["background"])) {
                $nowuserinfo["background"] = strlen($nowuserinfo["background"]) > 1 ? $this->imagesurl($nowuserinfo["background"], $filenone) : [$filenone];
            } else {
                $nowuserinfo["image"] = [$filenone];
            }
        }
        return $nowuserinfo;
        // 棄用：使用者資訊陣列（一個使用者可以關聯多條資訊，但唯一的主資訊一直在陣列第一位）
        // $userinfos = $result[2];
        // $newuserinfos = [];
        // $maininfo = [];
        // for ($i = 0; $i < count($userinfos); $i++) {
        //     $nowuserinfo = $userinfos[$i];
        //     $nowuserinfo["image"] = $this->imagesurl($nowuserinfo["image"]);
        //     $nowuserinfo["background"] = $this->imagesurl($nowuserinfo["background"]);
        //     if (isset($nowuserinfo["belong"])) {
        //         array_push($newuserinfos,$nowuserinfo);
        //     } else {
        //         array_push($maininfo,$nowuserinfo);
        //     }
        // }
        // if (count($maininfo) != 1) $nlcore->msg->stopmsg(2040207);
        // return array_merge($maininfo,$newuserinfos);
    }
    /**
     * @description: 獲取此賬戶所屬的子賬戶資訊
     * @param String mainuserhash 主賬戶雜湊
     * @param Bool getuserinfos 是否查詢每一個子賬戶的詳細資訊
     * @return Array [子賬戶雜湊] 或 [詳細資訊]
     */
    function subaccount(string $mainuserhash, bool $getuserinfos = false): array {
        global $nlcore;
        $columnArr = ["userhash"];
        $tableStr = $nlcore->cfg->db->tables["info"];
        $whereDic = ["belong" => $mainuserhash];
        $result = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        $childs = [];
        if ($result[0] == 1010000) {
            $childs = $result[2];
            if ($getuserinfos) {
                for ($i = 0; $i < count($childs); $i++) {
                    $nowchild = $childs[$i];
                    $nowuserinfo = $this->getuserinfo($nowchild["userhash"]);
                    $childs[$i] = $nowuserinfo;
                }
            }
        } else if ($result[0] == 1010001) {
        } else {
            $nlcore->msg->stopmsg(2070002);
        }
        return $childs;
    }
    /**
     * @description: 查詢當前客戶端提供的引數 "userhash" 是否屬於某個使用者的子賬戶
     * @param  bool  getuserinfos 是否查詢子賬戶的詳細資訊
     * @return array [賬戶詳細資訊] 或 [子賬戶雜湊] 或 [] (客戶端沒有要求驗證)
     */
    function subAccountChk(bool $getuserinfos = false): array {
        global $nlcore;
        if (isset($nlcore->sess->argReceived["userhash"]) && strcmp($nlcore->sess->userHash, $nlcore->sess->argReceived["userhash"]) != 0) {
            $subuser = $nlcore->sess->argReceived["userhash"];
            if (strcmp($nlcore->sess->userHash, $subuser) != 0) {
                if (!$nlcore->safe->is_rhash64($subuser)) $nlcore->msg->stopmsg(2070003, "S-" . $subuser);
                $subInfo = $nlcore->func->issubaccount($nlcore->sess->userHash, $subuser, $getuserinfos);
                $issub = $subInfo[0];
                if ($issub == false) $nlcore->msg->stopmsg(2070004, "S-" . $subuser);
                if ($getuserinfos) {
                    return $subInfo[2] ?? [$subuser];
                } else {
                    return [$subuser];
                }
            }
        }
        return [];
    }
    /**
     * @description: 查詢當前子賬戶是否屬於此賬戶
     * @param String mainuserhash 主賬戶雜湊
     * @param String subuserhash 子賬戶雜湊
     * @param Bool getuserinfos 是否查詢子賬戶的詳細資訊
     * @return Array [是否屬於,有則返回子賬戶否則返回主賬戶雜湊,可選子賬戶詳細資訊]
     */
    function issubaccount(string $mainuserhash, string $subuserhash, bool $getuserinfos = false) {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["info"];
        $whereDic = [
            "userhash" => $subuserhash,
            "belong" => $mainuserhash
        ];
        $result = $nlcore->db->scount($tableStr, $whereDic);
        if (intval($result[2][0]["count(*)"]) == 1) {
            if ($getuserinfos) {
                $nowuserinfo = $this->getuserinfo($subuserhash);
                return [true, $subuserhash, $nowuserinfo];
            }
            return [true, $subuserhash];
        }
        return [false, $mainuserhash];
    }
    /**
     * 獲取目的地資料夾路徑
     *
     * @param string $confdir 設定檔中的目錄鍵名（預設 "uploaddir"）
     * @param bool $mkdir 是否自動建立目錄（預設 true）
     * @param string $subdir 子目錄（可選）
     * @param int $datedir 是否使用日期目錄：-1 依設定, 0 不使用, 1 強制使用
     * @return array [完整絕對路徑, 儲存區資料夾路徑, 日期資料夾路徑]（路徑結尾有 /）
     */
    function savepath(string $confdir = "uploaddir", bool $mkdir = true, string $subdir = "", int $datedir = -1): array {
        global $nlcore;
        $uploadconf = $nlcore->cfg->app->upload;
        $uploadpath = $uploadconf[$confdir];
        if (substr($uploadpath, 0, 1) != DIRECTORY_SEPARATOR) {
            //不是绝对路径的话，补绝对路径
            $uploadpath = pathinfo(__FILE__)["dirname"] . "/../" . $uploadpath;
        }
        $uploadpath = $nlcore->safe->parentfolder($uploadpath) . DIRECTORY_SEPARATOR;
        $dirpath = $uploadpath;
        $datedirstr = null;
        $chmod = $uploadconf["chmod"];
        if ($subdir != "") {
            $uploadpath .= $subdir;
        }
        if ($datedir == 1 || ($uploadconf["datedir"] && $subdir == "" && $datedir != 0)) {
            $datedirstr = date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR . date('d');
            $uploadpath .= $datedirstr;
            if ($mkdir && !is_dir($uploadpath)) {
                mkdir($uploadpath, $chmod, true);
            }
        } else if ($mkdir && !is_dir($uploadpath)) {
            mkdir($uploadpath, $chmod, true);
        }
        $uploadpath = $nlcore->safe->mergerepeatchar($uploadpath . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);
        return [$uploadpath, $dirpath, $datedirstr . DIRECTORY_SEPARATOR];
    }
    /**
     * 獲取上傳圖片的完整檔案名稱資訊
     *
     * @param string $dirpath 檔案所在資料夾相對路徑（例如：2019/01/02/xxxx.jpg）
     * @param array $none 找不到內容或錯誤時需要返回的資訊（預設 []）
     * @return array 檔案資訊陣列，包括路徑、支援的清晰度名、支援的格式名
     */
    function imageurl(string $dirpath, array $none = []): array {
        global $nlcore;
        $dirarr = explode(DIRECTORY_SEPARATOR, $nlcore->safe->dirsep($dirpath));
        $file = array_pop($dirarr);
        $dir = implode(DIRECTORY_SEPARATOR, $dirarr);
        $fulldir = $this->savepath("uploaddir", $mkdir = false, $dir);
        $nowdir = $fulldir[0];
        if (!is_dir($nowdir)) {
            $this->log("W/MediaURL", "找不到文件夹: " . strval($nowdir));
            return $none;
        }
        $filesnames = scandir($nowdir);
        $sizenames = [];
        $extnames = [];
        foreach ($filesnames as $nowfilename) {
            if ($nowfilename != '.' && $nowfilename != '..') {
                $nowfilenamearr = explode('.', $nowfilename);
                if (count($nowfilenamearr) == 3 && $nowfilenamearr[0] == $file) {
                    if (!in_array($nowfilenamearr[1], $sizenames)) array_push($sizenames, $nowfilenamearr[1]);
                    if (!in_array($nowfilenamearr[2], $extnames)) array_push($extnames, $nowfilenamearr[2]);
                }
            }
        }
        if (count($sizenames) == 0 || count($extnames) == 0) {
            $this->log("W/MediaURL", "找不到目标图片: " . strval($nowdir) . $file);
            return $none;
        }
        $recommendsize = $nlcore->cfg->app->recommendsize;
        $fsize = "";
        for ($i = 0; $i < count($recommendsize); $i++) {
            $nowsize = $recommendsize[$i];
            if (in_array($nowsize, $sizenames)) {
                $fsize = $nowsize;
                break;
            }
        }
        $recommendext = $nlcore->cfg->app->recommendext;
        $fext = "";
        for ($i = 0; $i < count($recommendext); $i++) {
            $nowext = $recommendext[$i];
            if (in_array($nowext, $extnames)) {
                $fext = $nowext;
                break;
            }
        }
        $fileinfo = [
            "path" => $nlcore->safe->urlsep($dir . "/" . $file),
            "size" => $sizenames,
            "fsize" => $fsize,
            "ext" => $extnames,
            "fext" => $fext
        ];
        return $fileinfo;
    }
    /**
     * 獲取多張上傳圖片的所有清晰度的完整檔案名稱
     *
     * @param string $dirpaths 檔案所在資料夾相對路徑（逗號分隔）
     * @param array $none 找不到內容或錯誤時需要返回的資訊（預設 []）
     * @return array 檔案資訊二維陣列
     */
    function imagesurl(string $dirpaths, array $none = []): array {
        $fileinfos = [];
        $dirpatharr = explode(",", $dirpaths);
        for ($i = 0; $i < count($dirpatharr); $i++) {
            $fileinfos[$i] = $this->imageurl($dirpatharr[$i], $none);
        }
        return (count($fileinfos) > 0) ? $fileinfos : $none;
    }
    /**
     * 獲取裝置 ID
     *
     * @param string $totpToken 裝置代碼
     * @return int 此裝置在裝置表中的 ID
     */
    function getdeviceid(string $totpToken): int {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["encryption"];
        $columnArr = ["devid"];
        $whereDic = ["apptoken" => $totpToken];
        $result = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        if ($result[0] >= 2000000 || !isset($result[2][0]["devid"])) $nlcore->msg->stopmsg(2040210);
        return $result[2][0]["devid"];
    }
    /**
     * 獲取裝置資訊
     *
     * @param int $deviceid 裝置 ID
     * @param bool $onlytype 是否只返回裝置型別（預設 false）
     * @return array|string 裝置資訊陣列或型別字串
     */
    function getdeviceinfo(int $deviceid, bool $onlytype = false): array|string {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["device"];
        $columnArr = ["type"];
        if (!$onlytype) array_push($columnArr, "os", "device", "osver");
        $whereDic = ["id" => $deviceid];
        $resultdev = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        if (!isset($resultdev[2][0])) $nlcore->msg->stopmsg(2040712);
        if ($onlytype) {
            if (!isset($resultdev[2][0]["type"])) $nlcore->msg->stopmsg(2040213);
            return $resultdev[2][0]["type"];
        }
        return $resultdev[2][0];
    }
    /**
     * 從使用者 [暱稱#唯一碼] 獲取使用者雜湊（自帶安全檢查）
     *
     * @param array $namearr 已經分好的 [名稱,ID] 陣列
     * @return array [暱稱, ID, 使用者雜湊（沒查到則沒有）]
     */
    function fullnickname2userhash(array $namearr): array {
        global $nlcore;
        // $namearr = is_array($fullnickname) ? $fullnickname : explode("#", $fullnickname);
        if (count($namearr) != 2) $nlcore->msg->stopmsg(2070005, implode(',', $namearr));
        $name = $namearr[0];
        $nlcore->safe->safestr($name, true, true);
        $nameid = intval($namearr[1]);
        if ($nameid < 1000 || $nameid > 9999) $nlcore->msg->stopmsg(2070005, strval($nameid));
        //通过安全性检查，查询数据库
        $tableStr = $nlcore->cfg->db->tables["info"];
        $columnArr = ["userhash"];
        $whereDic = ["name" => $name, "nameid" => $nameid];
        $result = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        if ($result[0] == 1010001) {
            return [$name, $nameid];
        } else if ($result[0] != 1010000 || !isset($result[2][0]["userhash"])) {
            $nlcore->msg->stopmsg(2050002);
        }
        $users = $result[2];
        if (count($users) > 1) $nlcore->msg->stopmsg(2040101);
        $userHash = $users[0]["userhash"];
        return [$name, $nameid, $userHash];
    }
    /**
     * 從使用者雜湊獲取使用者 [暱稱,唯一碼]
     *
     * @param string $userHash 使用者雜湊
     * @return array [暱稱, 唯一碼]
     */
    function userHash2nickNameArr(string $userHash = ""): array {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["info"];
        $columnArr = ["name", "nameid"];
        $whereDic = ["userhash" => $userHash];
        $result = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        if ($result[0] == 1010001) {
            return [];
        } else if ($result[0] != 1010000) {
            $nlcore->msg->stopmsg(2040200);
        }
        $nameInfoArr = $result[2][0];
        if (count($nameInfoArr) != 2 || !isset($nameInfoArr["name"]) || !isset($nameInfoArr["nameid"])) {
            $nlcore->msg->stopmsg(2050002);
        }
        return [$nameInfoArr["name"], $nameInfoArr["nameid"]];
    }
    /**
     * [暱稱,唯一碼] 轉換為 "暱稱#唯一碼" 格式
     *
     * @param array $fullnickname userHash2nickNameArr 函式生成的陣列
     * @return string 組裝好的暱稱 ID 字串
     */
    function nickNameArr2nickNameFullStr(array $fullnickname): string {
        global $nlcore;
        $separators = $nlcore->cfg->app->separator;
        $mention = $separators["mention"];
        $namelink = $separators["namelink"];
        return $mention . implode($namelink, $fullnickname);
    }

    /**
     * 記錄執行時期產生的警告資訊
     *
     * @param string $tag 主題
     * @param string $logstr 資訊字串
     * @return void
     */
    function log(string $tag, string $logstr): void {
        global $nlcore;
        $logfilepath = $nlcore->cfg->db->logfile_nl;
        if ($logfilepath == null || $logfilepath == "") return;
        if ($logfilepath) {
            $ipaddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
            $proxyaddr = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? "@" . $_SERVER['HTTP_X_FORWARDED_FOR'] : "";
            $logstr = "[" . $nlcore->safe->getdatetime()[1] . "][" . $ipaddr . $proxyaddr . "][" . $tag . "] " . $logstr . PHP_EOL;
            if (!$this->logfile) $this->logfile = fopen($logfilepath, "a");
            fwrite($this->logfile, $logstr);
        }
    }

    /**
     * 解構子：關閉日誌檔案
     *
     * @return void
     */
    function __destruct() {
        if ($this->logfile) {
            fclose($this->logfile);
            $this->logfile = null;
        }
        unset($this->logfile);
    }
}
