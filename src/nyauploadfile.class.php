<?php
declare(strict_types=1);

/**
 * 檔案上傳處理
 *
 * 處理客戶端上傳的圖片與影片檔案，生成縮圖計劃並調用後端二壓服務。
 *
 * @package NyarukoLogin
 */
// require_once 'vendor/autoload.php';
class nyauploadfile {
    private $mediainfo = null;
    /**
     * 功能入口：獲取上傳檔案的資訊
     *
     * @param array $argReceived 客戶端提交資訊陣列
     * @return array 準備返回到客戶端的資訊陣列
     */
    function getuploadfile(array $argReceived): array {
        global $nlcore;
        if (!isset($_FILES["file"])) $nlcore->msg->stopmsg(2050104);
        $uploadconf = $nlcore->cfg->app->upload;
        $uploadconf["type"] = $nlcore->cfg->app->uploadtype;
        //整理文件详细信息数组
        $files = $nlcore->safe->dicvals2arrsdic($this->filearr($_FILES));
        //准备文件存储路径
        $savedirarr = $nlcore->func->savepath("uploaddir");
        $relativepaths = $savedirarr[2]; //相对路径
        $savedir = $savedirarr[0]; //目标存储文件夹
        $stmpdirs = $nlcore->func->savepath("tmpdir", false, "", 0)[0] . DIRECTORY_SEPARATOR;
        // $stmpdir = $stmpdirs[0]; //二压临时文件夹
        // $uploaddirstrcount = strlen($savedir);
        //遍历文件资讯
        $returnfile = [];
        $returnClientData = [];
        $returnfilepath = "";
        foreach ($files as $nowfile) {
            $mediatype = $this->chkfile($nowfile, $uploadconf); //检查文件
            $code = 1000000;
            $info = [];
            $tmpfile = $nowfile["tmp_name"];
            if ($mediatype["code"] != 0) {
                $nlcore->msg->stopmsg($mediatype["code"], $mediatype["info"]);
            } else {
                $info["type"] = $mediatype;
                $info["code"] = $code;
            }
            $newfilename = $nlcore->safe->millisecondtimestamp() . "_" . $nlcore->safe->randhash("", false, false); //创建临时文件名
            // $newfilename = md5_file($tmpfile); //文件哈希值创建文件名
            // $savefile = $savedir.$newfilename; //最终存储文件完整路径
            $extension = $mediatype["extension"]; //扩展名
            $savetmpfile = substr($stmpdirs, 0, -1) . $newfilename . '.' . $extension; //临时文件
            $rediskey = "";
            // $savetmpfileconf = $stmpdir.$newfilename.'.json'; //临时文件计划
            $info["files"] = [];
            $nowextres = [];
            $sizearr = [];
            $mediainfojson = [];
            $imagesize = getimagesize($tmpfile);
            $mediainfojson["width"] = intval($imagesize[0]);
            $mediainfojson["height"] = intval($imagesize[1]);
            $filejsonarr = [
                "type" => $mediatype["media"],
                "temp" => $savetmpfile,
                "todir" => $savedir,
                "toname" => $newfilename,
                "info" => $mediainfojson
            ];
            $returnfilepath = $nlcore->safe->urlsep($relativepaths . $newfilename);
            $useto = isset($argReceived["usefor"]) ? $argReceived["usefor"] : "def";
            //是视频还是图片？
            if ($mediatype["media"] == "image") {
                $rediskey = $nlcore->cfg->db->redis_tables["convertimage"];
                if ($extension == "gif") {
                    $nowextres = ["gif"];
                } else {
                    $nowextres = ["jpg", "webp"];
                }
                if (isset($nlcore->cfg->app->imageresize[$useto])) {
                    $imageresize = $nlcore->cfg->app->imageresize[$useto];
                } else {
                    $nlcore->msg->stopmsg(2050106);
                }
                $filejsonarr["to"] = $this->addResizeJob($imageresize, $mediainfojson, $nowextres, $sizearr);
            } else if ($mediatype["media"] == "video") {
                $rediskey = $nlcore->cfg->db->redis_tables["convertvideo"];
                $nowextres = ["mp4"];
                if (isset($nlcore->cfg->app->videoresize[$useto])) {
                    $videoresize = $nlcore->cfg->app->videoresize[$useto];
                } else {
                    $nlcore->msg->stopmsg(2050106);
                }
                $mediainfojson = $this->getvideomediainfo($tmpfile, ["width", "height", "duration", "bit_rate"]);
                $mediainfojson["width"] = intval($mediainfojson["width"]);
                $mediainfojson["height"] = intval($mediainfojson["height"]);
                $mediainfojson["duration"] = floatval($mediainfojson["duration"]);
                $mediainfojson["bit_rate"] = intval($mediainfojson["bit_rate"]);
                $filejsonarr["to"] = $this->addResizeJob($videoresize, $mediainfojson, $nowextres, $sizearr);
            }
            $filejsonarr["info"] = $mediainfojson;
            list($timesub1, $timesub2) = explode(' ', microtime());
            $ftag = (float)sprintf('%.0f', (floatval($timesub1) + floatval($timesub2)) * 1000);
            $ftag = strval(dechex($ftag));
            $nowfileres = [
                "path" => $returnfilepath,
                "tmpt" => $ftag,
                "size" => $sizearr,
                "ext" => $nowextres,
                "info" => $mediainfojson
            ];
            array_push($info["files"], $nowfileres);
            // $tmpfile -> $savetmpfile
            move_uploaded_file($nowfile["tmp_name"], $savetmpfile);
            $configfiledata = json_encode($filejsonarr);
            // 将转换计划写入 Redis
            // if (!$nlcore->db->initRedis()) die();
            $redis = $nlcore->db->redis;
            $rediskey .= $nlcore->safe->millisecondtimestamp() . $newfilename;
            $redis->set($rediskey, $configfiledata);
            $mserver = $nlcore->cfg->app->mserver;
            // 記錄日誌
            $logfilepath = $nlcore->cfg->db->logfile_sh;
            $logfile = null;
            if (strlen($logfilepath) > 0) {
                $logfile = fopen($logfilepath, "a");
                fwrite($logfile, "===== " . date('Y-m-d H:i:s') . " =====\n[REDIS KEY] " . $rediskey . "\n[REDIS VAL] " . $configfiledata . "\n");
            }
            // 調用GO程序，開始進行後台二壓
            if ($mserver != "") {
                $execlog = "/dev/null";
                if (($mediatype["media"] == "image" && !$redis->exists("ic")) || $mediatype["media"] == "video" && !$redis->exists("vc")) {
                    $curl = curl_init();
                    $url = $mserver . $mediatype["media"];
                    curl_setopt($curl, CURLOPT_URL, $url);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    $httpresponse = curl_exec($curl);
                    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    curl_close($curl);
                    if ($httpCode != "200") {
                        if (strlen($logfilepath) > 0) {
                            fwrite($logfile, "[CURL URL] " . $url . "\n[CURL CODE] [!ERROR!] " . $httpCode . "\n[CURL RES] " . $httpresponse . "\n");
                            fclose($logfile);
                        }
                        $nlcore->msg->stopmsg(2060201, strval($httpCode));
                    } else if (strlen($logfilepath) > 0) {
                        fwrite($logfile, "[CURL URL] " . $url . "\n[CURL CODE] " . $httpCode . "\n[CURL RES] " . $httpresponse . "\n");
                    }
                }
            }
            fclose($logfile);
            array_push($returnfile, $info);
        }
        $returnClientData["filegroups"] = $returnfile;
        $returnClientData["code"] = 1000000;
        $returnClientData["filecount"] = count($files);
        return $returnClientData;
    }

    /**
     * 新增二壓計劃目標
     *
     * @param array $resizeList    尺寸設定陣列
     * @param array $mediainfojson 媒體資訊字典
     * @param array $nowextres     目標副檔名陣列
     * @param array &$sizearr      尺寸列表陣列（傳參考）
     * @return array 二壓計劃目標資訊二維陣列
     */
    function addResizeJob(array $resizeList, array $mediainfojson, array $nowextres, array &$sizearr): array {
        $sizesavepath = [];
        foreach ($resizeList as $key => $sizes) {
            // 檢查是否能提供該尺寸
            $newSizeArr = null;
            if ($sizes[0] == 0 && $sizes[1] == 0) {
                $newSizeArr = [$mediainfojson["width"], $mediainfojson["height"], true];
            } else {
                $newSizeArr = $this->getresize($mediainfojson["width"], $mediainfojson["height"], $sizes[0], $sizes[1]);
            }
            if ($newSizeArr[2]) {
                // 為每個副檔名新增轉換任務
                $newSizeArr[2] = $sizes[2];
                foreach ($nowextres as $convextension) {
                    $newsizes = $newSizeArr;
                    $nowsizefile = $key . '.' . $convextension;
                    array_unshift($newsizes, $nowsizefile);
                    array_push($sizesavepath, $newsizes);
                }
                array_push($sizearr, $key);
            }
        }
        return $sizesavepath;
    }

    /**
     * 計算縮小圖片後的寬高
     *
     * @param float $imageWidth  原始圖片寬度
     * @param float $imageHeight 原始圖片高度
     * @param float $maxWidth    目標尺寸寬度
     * @param float $maxHeight   目標尺寸高度
     * @return array{0:float,1:float,2:bool} 新的寬高及是否需要調整
     */
    function getresize(float $imageWidth, float $imageHeight, float $maxWidth, float $maxHeight): array {
        $newWidth = $imageWidth;
        $newHeight = $imageHeight;
        $imageScale = $imageWidth / $imageHeight;
        $maxScale = $maxWidth / $maxHeight;
        if ($maxScale <= $imageScale) {
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $imageScale;
        } else if ($maxScale > $imageScale) {
            $newHeight = $maxHeight;
            $newWidth = $maxHeight * $imageScale;
        }
        $returnArr = null;
        if ($newWidth >= $imageWidth && $newHeight >= $imageHeight) {
            $returnArr = [$imageWidth, $imageHeight, false];
        } else {
            $returnArr = [$newWidth, $newHeight, true];
        }
        return $returnArr;
    }
    /**
     * 將圖片限制到指定尺寸並壓縮
     *
     * @param string $imagefile   圖片暫存檔完整路徑
     * @param string $savefile    最終儲存檔案路徑（不含副檔名）
     * @param string $key         尺寸鍵名
     * @param string $type        副檔名
     * @param array  $sizequality 最大尺寸和清晰度 [寬, 高, 壓縮比]
     * @return array 檔案完整路徑陣列及是否找到重複檔案
     */
    function resizeto(string $imagefile, string $savefile, string $key, string $type, array $sizequality): array {
        $imagick = new Imagick($imagefile);
        $imagick->stripImage(); //去除图片信息
        $isresize = ($sizequality[0] <= 0 || $sizequality[1] <= 0) ? false : true;
        $isrequality = ($sizequality[2] <= 0) ? false : true;
        if ($isresize) {
            $imageWidth = $imagick->getImageWidth();
            $imageHeight = $imagick->getImageHeight();
            if ($imageWidth <= 0 || $imageHeight <= 0) {
                $imageWidth = imagesx($srcImg);
                $imageHeight = imagesy($srcImg);
            }
            $newsize = $this->getresize($imageWidth, $imageHeight, $sizequality[0], $sizequality[1]);
        }
        $isexists = false;
        if ($type == "gif") {
            $transparent = new ImagickPixel("transparent");
            $newimagick = new Imagick();
            foreach ($imagick as $img) {
                $page = $img->getImagePage();
                $tmp = new Imagick();
                $tmp->newImage($page['width'], $page['height'], $transparent, 'gif');
                $tmp->compositeImage($img, Imagick::COMPOSITE_OVER, $page['x'], $page['y']);
                if ($isresize) $tmp->adaptiveResizeImage($newsize[0], $newsize[1]);
                if ($isrequality) $tmp->setImageCompressionQuality(1);
                $tmp->setFormat("gif");
                $newimagick->addImage($tmp);
                $newimagick->setImagePage($tmp->getImageWidth(), $tmp->getImageHeight(), 0, 0);
                $newimagick->setImageDelay($img->getImageDelay());
                $newimagick->setImageDispose($img->getImageDispose());
                $tmp->destroy();
            }
            $newimagick->setFormat("gif");
            $savefilename = $savefile . "." . $key . ".gif";
            if (file_exists($savefilename)) $isexists = true;
            $newimagick->writeImages($savefilename, true);
            $newimagick->destroy();
            return [[$savefilename], $isexists];
        } else {
            if ($isresize) $imagick->adaptiveResizeImage($newsize[0], $newsize[1]);
            if ($isrequality) $imagick->setImageCompressionQuality($sizequality[2]); //图片质量
            $webp = $imagick;
            $webp->setFormat("webp");
            $savefilename1 = $savefile . "." . $key . ".webp";
            $dechextime = strval(dechex(time()));
            if (file_exists($savefilename1)) $isexists = true;
            $webp->writeImage($savefilename1);
            $jpeg = $imagick;
            $jpeg->setFormat("jpeg");
            $savefilename2 = $savefile . "." . $key . ".jpg";
            if (file_exists($savefilename2)) $isexists = true;
            $jpeg->writeImage($savefilename2);
            $webp->destroy();
            $jpeg->destroy();
            $imagick->destroy();
            return [[$savefilename1, $savefilename2], $isexists];
        }
    }
    /**
     * 檢查檔案是否符合要求
     *
     * @param array $nowfile    當前檔案資訊
     * @param array $uploadconf 上傳設定陣列
     * @param array $enable     允許上傳的媒體類別
     * @return array [錯誤代碼(0正常), MIME 類型, 副檔名, 媒體類別]
     */
    function chkfile(array $nowfile, array $uploadconf, array $enable = ["image", "video"]): array {
        //检查错误代码
        if ($nowfile["error"] != 0) {
            return ["code" => 2050100, "info" => $nowfile["error"]];
        }
        //检查文件大小是否超过限制
        $maxsize = $uploadconf["maxsize"];
        if ($nowfile["size"] > $maxsize["all"]) {
            return ["code" => 2050101, "info" => $nowfile["size"]];
        }
        //检查文件类型是否符合
        $fi = new finfo(FILEINFO_MIME_TYPE); //FILEINFO_MIME_TYPE FILEINFO_EXTENSION
        $mime_type = $fi->file($nowfile["tmp_name"]);
        //mediatype [MIME类型, 扩展名, 媒体类别(图片/视频)] ，来自设置 uploadtype 。
        $mediatype = null;
        foreach ($uploadconf["type"] as $typename => $typevalue) {
            foreach ($typevalue as $typearr) {
                if ($mime_type == $typearr[0]) {
                    $mediatype = [
                        "mime" => $typearr[0],
                        "extension" => $typearr[1]
                    ];
                    $mediatype["media"] = $typename;
                    break;
                }
            }
        }
        if (!$mediatype || !in_array($mediatype["media"], $enable)) {
            return ["code" => 2050102, "info" => $mime_type . ($mediatype["media"] ?? "")]; //$mediatype["media"]; //
        }
        if ($mediatype["media"] == "video") { //如果是视频
            //当前类型的限制大小
            if ($nowfile["size"] > $maxsize["video"]) {
                return ["code" => 2050101, "info" => strval($nowfile["size"])];
            }
            //检查视频时长是否太长
            $videoduration = intval($this->getvideomediainfo($nowfile["tmp_name"], ["duration"])[0]);
            if ($videoduration > $uploadconf["videoduration"]) {
                return ["code" => 2050103, "info" => strval($videoduration)];
            }
        } else if ($mediatype["media"] == "image") { //如果是图片
            //当前类型的限制大小
            if ($mediatype["extension"] == "gif" && $nowfile["size"] > $maxsize["gif"]) {
                return ["code" => 2050101, "info" => $nowfile["size"]];
            } else if ($nowfile["size"] > $maxsize["image"]) {
                return ["code" => 2050101, "info" => $nowfile["size"]];
            }
        }
        $mediatype["code"] = 0;
        return $mediatype;
    }
    /**
     * 獲取影片詳細資訊
     *
     * @param string   $file 影片檔案路徑
     * @param string[] $key  要獲取的屬性名稱陣列
     * @return ?array 取得的影片資訊，無匹配鍵時返回 null
     */
    function getvideomediainfo(string $file, array $key): ?array {
        if ($this->mediainfo == null) {
            global $nlcore;
            $logger = null;
            $ffprobe = FFMpeg\FFProbe::create($nlcore->cfg->app->ffconf, $logger);
            $this->mediainfo = $ffprobe->streams($file)->videos()->first()->all();
        }
        if (count($key) > 1) {
            $rval = [];
            foreach ($key as $nowkey) {
                if (isset($this->mediainfo[$nowkey])) {
                    $rval[$nowkey] = $this->mediainfo[$nowkey];
                } else {
                    $rval[$nowkey] = null;
                }
            }
            return $rval;
        } else if (isset($this->mediainfo[$key[0]])) {
            return [$this->mediainfo[$key[0]]];
        }
        return null;
    }
    /**
     * 透過讀取檔案頭判斷檔案真實類型（已廢棄）
     *
     * @param string $filename 檔案完整路徑
     * @return string 檔案副檔名
     */
    function getImagetype(string $filename): string {
        $file = fopen($filename, 'rb');
        $bin = fread($file, 2); //只读2字节
        fclose($file);
        $strInfo = @unpack('C2chars', $bin);
        $typeCode = intval($strInfo['chars1'] . $strInfo['chars2']);
        $fileType = '';
        switch ($typeCode) {
            case 255216:
                $fileType = 'jpg';
                break;
            case 7173:
                $fileType = 'gif';
                break;
            case 6677:
                $fileType = 'bmp';
                break;
            case 13780:
                $fileType = 'png';
                break;
            default:
                $fileType = $typeCode;
        }
        return $fileType;
    }
    /**
     * 合併多 name 上傳的檔案
     *
     * @param array $files $_FILES 陣列
     * @return array 轉換後的檔案資訊陣列
     */
    function filearr(array $files): array {
        if (!$files || count($files) == 0) return [];
        $filearr = null;
        $filekeys = array_keys($files);
        //取所有文件数组
        foreach ($files as $fileskey => $filesvalue) {
            $nowfileskey = array_keys($filesvalue);
            //初始化文件信息数组
            if ($filearr == null) {
                $filearr = array();
                foreach ($nowfileskey as $nowfileskeyname) {
                    $filearr[$nowfileskeyname] = array();
                }
                $filearr["form"] = array();
            }
            if (is_array($filesvalue[$nowfileskey[0]])) {
                $firstadd = true;
                foreach ($filesvalue as $fkey => $fvalue) {
                    if ($firstadd) {
                        for ($i = 0; $i < count($fvalue); $i++) {
                            array_push($filearr["form"], $fileskey);
                        }
                        $firstadd = false;
                    }
                    $filearr[$fkey] = array_merge($filearr[$fkey], $fvalue);
                }
            } else {
                foreach ($filesvalue as $fkey => $fvalue) {
                    array_push($filearr[$fkey], $fvalue);
                }
                array_push($filearr["form"], $fileskey);
            }
        }
        return $filearr;
    }
}
