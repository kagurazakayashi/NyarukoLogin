<?php
class nyafunc {
    private $logfile = null; //记录详细调试信息到文件
    /**
     * @description: 检查登录凭据是邮箱还是手机号
     * @param String loginstr 要检查的登录凭据字符串
     * @return Int 0:直接将错误返回给客户端 0:邮箱 1:手机号
     */
    function logintype($loginstr) {
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
     * @description: 检查指定信息地址是否已经存在于数据库
     * @param Int logintype 要检查的凭据类型 0:邮箱 1:手机号 2:哈希
     * @param String loginstr 要检查的登录凭据字符串
     * @return Bool 是否已经存在。如果出现多个结果则直接将错误返回客户端
     */
    function isalreadyexists($logintype,$loginstr) {
        global $nlcore;
        $logintypearr = ["mail","tel","hash"];
        $logintypestr = $logintypearr[$logintype];
        $whereDic = [$logintypearr[$logintype] => $loginstr];
        $result = $nlcore->db->scount($nlcore->cfg->db->tables["users"],$whereDic);
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
     * @description: 生成使用者暱稱四位碼
     * @param String name 新的暱稱
     * @param String userhash 使用者雜湊
     * @return Int 新生成的四位數ID
     */
    function genuserid(string $name, string $userhash) {
        global $nlcore;
        $currid = [];
        $tableStr = $nlcore->cfg->db->tables["info"];
        $columnArr = ["nameid"];
        $whereDic = ["userhash" => $userhash];
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        if ($result[0] != 1010000) $nlcore->msg->stopmsg(2040114);
        $nameids = $result[2];
        foreach ($nameids as $nameid) {
            $nowid = intval($nameid["nameid"]);
            array_push($currid,$nowid);
        }
        $nameidbook = [];
        for ($i = 1000; $i < 9999; $i++) {
            if (in_array($i,$currid)) continue;
            array_push($nameidbook,$i);
        }
        $nameidbookcount = count($nameidbook);
        if ($nameidbookcount == 0) $this->nlcore->msg->stopmsg(2040106,$name);
        $nameidi = rand(0, $nameidbookcount);
        return $nameidbook[$nameidi];
    }
    /**
     * @description: 檢查該使用者是否已存在
     * @param String mergename 暱稱#四位碼
     * 或使用：
     * @param String name 暱稱
     * @param String nameid 四位碼
     * @return Bool 是否有此使用者
     */
    function useralreadyexists($mergename=null,$name=null,$nameid=null) {
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
        $result = $nlcore->db->scount($nlcore->cfg->db->tables["info"],$whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040200);
        $datacount = $result[2][0]["count(*)"];
        if ($datacount > 0) return true;
        return false;
    }
    /**
     * @description: 檢查是否需要輸入驗證碼，並根據配置決定顯示哪種驗證碼
     * @param String gender 性別名稱
     * @param String localization 本地化性別名稱
     * @param Int list 性別列表ID
     * @return String 性別資訊查詢結果
     */
    function getgender( int $id=-1, string $gender="",string $localization="",int $list=-1) {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["gender"];
        $whereDic = [];
        if ($id > 0) $whereDic["id"] = $id;
        if (strlen($gender) > 0) $whereDic["gender"] = $gender;
        if (strlen($localization) > 0) $whereDic["localization"] = $localization;
        $whereDic["list"] = ($list >= 0) ? $list : $nlcore->cfg->app->genderlist;
        $result = $nlcore->db->select([],$tableStr,$whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040605);
        return $result[2];
    }
    /**
     * @description: 檢查是否需要輸入驗證碼，並根據配置決定顯示哪種驗證碼
     * @param Int 失敗次數計數
     * @return String 需要的驗證方式
     */
    function needcaptcha($loginfail) {
        global $nlcore;
        $needcaptcha = $nlcore->cfg->verify->needcaptcha;
        $nowmode = "";
        $nownum = 0;
        foreach($needcaptcha as $key => $value){
            if ($loginfail >= $value && $value > $nownum) {
                $nowmode = $key;
                $nownum = $value;
            }
        }
        return $nowmode;
    }
    /**
     * @description: 写入历史记录
     * @param String userhash 用户哈希
     * @param String totptoken 应用识别码
     * @param String code 错误代码
     * @param String process 过程记录
     * @param String session 当前会话
     */
    function writehistory($operation,$code,$userHash,$totpToken,$ipid,$sender,$process=null,$session=null) {
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
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040112);
    }
    /**
     * @description: 取出密码提示问题
     * @param String userhash 用户哈希
     * @param Boolean all : true=顺序全部取出 false=乱序取出随机两个
     * @return Array<String> 密码提示问题数组
     */
    function getquestion($userHash,$all=false) {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["protection"];
        $columnArr = ["question1","question2","question3"];
        $whereDic = ["userhash" => $userHash];
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        if ($result[0] != 1010000) $nlcore->msg->stopmsg(2040301);
        $questions = $result[2][0];
        if (!isset($questions["question1"]) || $questions["question1"] == "" || !isset($questions["question2"]) || $questions["question2"] == "" || !isset($questions["question3"]) || $questions["question3"] == "") {
            $nlcore->msg->stopmsg(2040301);
        }
        $returnarr = [$questions["question1"],$questions["question2"],$questions["question3"]];
        shuffle($returnarr);
        array_pop($returnarr);
        return $returnarr;
    }
    /**
     * @description: 取得使用者個性化資訊
     * @param String userhash 使用者雜湊
     * @param Array dbresult 自定義資料庫查詢返回結果輸入，用於合併查詢其他自定義使用者資訊表
     * @return Array<Array> 當前使用者資訊
     */
    function getuserinfo(string $userHash,array $dbresult=null,$getfileinfo=true):array {
        global $nlcore;
        $result = null;
        if ($dbresult) {
            $result = $dbresult;
        } else {
            $tableStr = $nlcore->cfg->db->tables["info"];
            $columnArr = ["userhash","belong","infotype","name","nameid","gender","pronoun","address","profile","description","image","background"];
            $whereDic = ["userhash" => $userHash];
            $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        }
        if ($result[0] != 1010000 || !isset($result[2][0])) $nlcore->msg->stopmsg(2040206);
        $nowuserinfo = $result[2][0];
        if ($getfileinfo) {
            $filenone = ["path"=>""];
            $nowuserinfo["image"] = strlen($nowuserinfo["image"]) > 1 ? $this->imagesurl($nowuserinfo["image"],$filenone) : [$filenone];
            $nowuserinfo["background"] = strlen($nowuserinfo["background"]) > 1 ? $this->imagesurl($nowuserinfo["background"],$filenone) : [$filenone];
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
     * @return Array 子賬戶雜湊和詳細資訊
    */
    function subaccount(string $mainuserhash,bool $getuserinfos=false):array {
        global $nlcore;
        $columnArr = ["userhash"];
        $tableStr = $nlcore->cfg->db->tables["info"];
        $whereDic = ["belong" => $mainuserhash];
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        $childs = [];
        if ($result[0] == 1010000) {
            $childs = $result[2];
            if ($getuserinfos) {
                for ($i=0; $i < count($childs); $i++) {
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
     * @description: 查詢當前子賬戶是否屬於此賬戶
     * @param String mainuserhash 主賬戶雜湊
     * @param String subuserhash 子賬戶雜湊
     * @param Bool getuserinfos 是否查詢子賬戶的詳細資訊
     * @return Array [是否屬於,有則返回子賬戶否則返回主賬戶雜湊,可選子賬戶詳細資訊]
    */
    function issubaccount(string $mainuserhash,string $subuserhash,bool $getuserinfos=false) {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["info"];
        $whereDic = [
            "userhash" => $subuserhash,
            "belong" => $mainuserhash
        ];
        $result = $nlcore->db->scount($tableStr,$whereDic);
        if (intval($result[2][0]["count(*)"]) == 1) {
            if ($getuserinfos) {
                $nowuserinfo = $this->getuserinfo($subuserhash);
                return [true,$subuserhash,$nowuserinfo];
            }
            return [true,$subuserhash];
        }
        return [false,$mainuserhash];
    }
    /**
     * @description: 获取目的地文件夹
     * @return Array[String] [完整绝对路径,存储区文件夹路径,日期文件夹路径] (有/结尾)
     * 返回示例： ["/wwwroot/upload/2020/03/21/","/wwwroot/upload/","2020/03/21/"]
     */
    function savepath($confdir="uploaddir",$mkdir=true,$subdir="",$datedir=-1) {
        global $nlcore;
        $uploadconf = $nlcore->cfg->app->upload;
        $uploadpath = $uploadconf[$confdir];
        if (substr($uploadpath, 0, 1) != DIRECTORY_SEPARATOR) {
            //不是绝对路径的话，补绝对路径
            $uploadpath = pathinfo(__FILE__)["dirname"]."/../".$uploadpath;
        }
        $uploadpath = $nlcore->safe->parentfolder($uploadpath).DIRECTORY_SEPARATOR;
        $dirpath = $uploadpath;
        $datedirstr = null;
        $chmod = $uploadconf["chmod"];
        if ($subdir != "") {
            $uploadpath .= $subdir;
        }
        if ($datedir == 1 || ($uploadconf["datedir"] && $subdir == "" && $datedir != 0)) {
            $datedirstr = date('Y').DIRECTORY_SEPARATOR.date('m').DIRECTORY_SEPARATOR.date('d');
            $uploadpath .= $datedirstr;
            if ($mkdir && !is_dir($uploadpath)) {
                mkdir($uploadpath,$chmod,true);
            }
        } else if ($mkdir && !is_dir($uploadpath)) {
            mkdir($uploadpath,$chmod,true);
        }
        $uploadpath = $nlcore->safe->mergerepeatchar($uploadpath.DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR);
        return [$uploadpath,$dirpath,$datedirstr.DIRECTORY_SEPARATOR];
    }
    /**
     * @description: 获取上传的某张图片的所有清晰度的完整文件名
     * @param String dirpath 文件所在文件夹相对路径（2019/01/02/xxxx.jpg）
     * @param Array none 找不到内容或错误时需要返回的信息
     * @return Array<Array> 文件信息数组，包括文件名、支持的清晰度名、支持的格式名，以便客户端合并为完整的路径。
     */
    function imageurl(string $dirpath, array $none=[]):array {
        global $nlcore;
        $dirarr = explode(DIRECTORY_SEPARATOR,$nlcore->safe->dirsep($dirpath));
        $file = array_pop($dirarr);
        $dir = implode(DIRECTORY_SEPARATOR,$dirarr);
        $fulldir = $this->savepath("uploaddir",$mkdir=false,$dir);
        $nowdir = $fulldir[0];
        if (!is_dir($nowdir)) {
            $this->log("W/MediaURL","找不到文件夹: ".strval($nowdir));
            return $none;
        }
        $filesnames = scandir($nowdir);
        $sizenames = [];
        $extnames = [];
        foreach ($filesnames as $nowfilename) {
            if ($nowfilename != '.' && $nowfilename != '..') {
                $nowfilenamearr = explode('.',$nowfilename);
                if (count($nowfilenamearr) == 3 && $nowfilenamearr[0] == $file) {
                    if (!in_array($nowfilenamearr[1], $sizenames)) array_push($sizenames, $nowfilenamearr[1]);
                    if (!in_array($nowfilenamearr[2], $extnames)) array_push($extnames, $nowfilenamearr[2]);
                }
            }
        }
        if (count($sizenames) == 0 || count($extnames) == 0) {
            $this->log("W/MediaURL","找不到目标图片: ".strval($nowdir).$file);
            return $none;
        }
        $recommendsize = $nlcore->cfg->app->recommendsize;
        $fsize = "";
        for ($i=0; $i < count($recommendsize); $i++) {
            $nowsize = $recommendsize[$i];
            if (in_array($nowsize,$sizenames)) {
                $fsize = $nowsize;
                break;
            }
        }
        $recommendext = $nlcore->cfg->app->recommendext;
        $fext = "";
        for ($i=0; $i < count($recommendext); $i++) {
            $nowext = $recommendext[$i];
            if (in_array($nowext,$extnames)) {
                $fext = $nowext;
                break;
            }
        }
        $fileinfo = [
            "path" => $nlcore->safe->urlsep($dir."/".$file),
            "size" => $sizenames,
            "fsize" => $fsize,
            "ext" => $extnames,
            "fext" => $fext
        ];
        return $fileinfo;
    }
    /**
     * @description: 获取上传的多张图片的所有清晰度的完整文件名
     * @param String dirpaths 文件所在文件夹相对路径（2019/01/02/xxxx.jpg）（使用逗号分隔符）
     * @param Array none 找不到内容或错误时需要返回的信息
     * @return Array<Array> 文件信息二维数组，包括文件名、支持的清晰度名、支持的格式名，以便客户端合并为完整的路径。
     */
    function imagesurl(string $dirpaths, array $none=[]):array {
        $fileinfos = [];
        $dirpatharr = explode(",", $dirpaths);
        for ($i=0; $i < count($dirpatharr); $i++) {
            $fileinfos[$i] = $this->imageurl($dirpatharr[$i],$none);
        }
        return (count($fileinfos) > 0) ? $fileinfos : $none;
    }
    /**
     * @description: 获取设备ID
     * @param String totptoken 设备代码
     * @return Int 此设备在设备表中的ID
     */
    function getdeviceid($totpToken) {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["encryption"];
        $columnArr = ["devid"];
        $whereDic = ["apptoken" => $totpToken];
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        if ($result[0] >= 2000000 || !isset($result[2][0]["devid"])) $nlcore->msg->stopmsg(2040210);
        return $result[2][0]["devid"];
    }
    /**
     * @description: 获取设备信息
     * @param String deviceid 设备ID
     * @param Bool onlytype 是否只返回设备类型
     * @return Array<String> 设备信息
     */
    function getdeviceinfo($deviceid,$onlytype=false) {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["device"];
        $columnArr = ["type"];
        if (!$onlytype) array_push($columnArr,"os","device","osver");
        $whereDic = ["id" => $deviceid];
        $resultdev = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        if (!isset($resultdev[2][0])) $nlcore->msg->stopmsg(2040212);
        if ($onlytype) {
            if (!isset($resultdev[2][0]["type"])) $nlcore->msg->stopmsg(2040213);
            return $resultdev[2][0]["type"];
        }
        return $resultdev[2][0];
    }
    /**
     * @description: 从用户[昵称#唯一码]获取用户哈希（自带安全检查）
     * @param String/Array<String> fullnickname 完整昵称或已经分好的[名称,ID]数组
     * @return Array<String> [昵称,ID,用户哈希(没查到则没有)]
     */
    function fullnickname2userhash($fullnickname) {
        global $nlcore;
        $namearr = is_array($fullnickname) ? $fullnickname : explode("#",$fullnickname);
        if (count($namearr) != 2) $nlcore->msg->stopmsg(2050001);
        $name = $namearr[0];
        $nlcore->safe->safestr($name,true,true);
        $nameid = intval($namearr[1]);
        if ($nameid < 1000 || $nameid > 9999) $nlcore->msg->stopmsg(2050001);
        //通过安全性检查，查询数据库
        $tableStr = $nlcore->cfg->db->tables["info"];
        $columnArr = ["userhash"];
        $whereDic = ["name" => $name, "nameid" => $nameid];
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        if ($result[0] == 1010001) {
            return [$name,$nameid];
        } else if ($result[0] != 1010000 || !isset($result[2][0]["userhash"])) {
            $nlcore->msg->stopmsg(2050002);
        }
        $users = $result[2];
        if (count($users) > 1) $nlcore->msg->stopmsg(2040101);
        $userHash = $users[0]["userhash"];
        return [$name,$nameid,$userHash];
    }
    /**
     * @description: 从用户哈希获取用户[昵称#唯一码]
     * @param String/Array<String> fullnickname 完整昵称或已经分好的[名称,ID]数组
     * @return Null/String 用户哈希
     */
    function userhash2fullnickname($userHash="") {
        global $nlcore;
        $tableStr = $nlcore->cfg->db->tables["info"];
        $columnArr = ["name","nameid"];
        $whereDic = ["userhash" => $userHash];
        $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        if ($result[0] == 1010001) {
            return null;
        } else if ($result[0] != 1010000) {
            $nlcore->msg->stopmsg(2040200);
        }
        if (count($result[2][0]) != 2 || !isset($result[2][0]["name"]) || !isset($result[2][0]["nameid"])) {
            $nlcore->msg->stopmsg(2050002);
        }
        return implode("#",$result[2][0]);
    }

    /**
     * @description: 记录运行时产生的警告信息
     * @param String tag 主题
     * @param String logstr 信息字符串
     */
    function log(string $tag,string $logstr):void {
        global $nlcore;
        $logfilepath = $nlcore->cfg->db->logfile_nl;
        if ($logfilepath == null || $logfilepath == "") return;
        if ($logfilepath) {
            $ipaddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
            $proxyaddr = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? "@".$_SERVER['HTTP_X_FORWARDED_FOR'] : "";
            $logstr = "[".$nlcore->safe->getdatetime()[1]."][".$ipaddr.$proxyaddr."][".$tag."] ".$logstr.PHP_EOL;
            if (!$this->logfile) $this->logfile = fopen($logfilepath,"a");
            fwrite($this->logfile,$logstr);
        }
    }

    /**
     * @description: 析构，关闭日志文件
     */
    function __destruct() {
        if ($this->logfile) {
            fclose($this->logfile);
            $this->logfile = null;
        }
        unset($this->logfile);
    }
}
?>
