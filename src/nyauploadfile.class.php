<?php
// require_once 'vendor/autoload.php';
class nyauploadfile {
    private $mediainfo = null;
    /**
     * @description: 功能入口：獲取上傳檔案的資訊
     * @param Array argReceived 客戶端提交資訊陣列
     * @return 準備返回到客戶端的資訊陣列
     */
    function getuploadfile(array $argReceived) {
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
     * @description: 新增二壓計劃目標
     * @param Array resizeList 尺寸設定陣列 [寬,高,其他資訊...]
     * @param Array mediainfojson 媒體資訊字典
     * @param Array nowextres 目標副檔名陣列
     * @param Array &$sizearr 尺寸列表陣列指標
     * @return Array 二壓計劃目標資訊二維陣列
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
     * @description: 计算缩小图片后的宽高（如果已经小于设定值则输出原尺寸）
     * @param Float imageWidth 原始图片宽度
     * @param Float imageHeight 原始图片高度
     * @param Float maxWidth 目标尺寸宽度
     * @param Float maxHeight 目标尺寸高度
     * @return Array<Float,Float,Bool> 新的宽高,以及是否需要调整
     */
    function getresize($imageWidth, $imageHeight, $maxWidth, $maxHeight) {
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
     * @description: 将图片限制到指定尺寸，压缩成更小的 gif 或 jpg+webp 格式，然后存入日期文件夹。
     * @param String imagefile 图片临时文件完整路径
     * @param String savefile 最终存储文件路径（无.）
     * @param String saveext 最终存储文件路径扩展名前补位（在.后添加）
     * @param String type 扩展名
     * @param Array sizequality 最大尺寸和清晰度 [宽,高,在 jpg+webp 模式时的压缩比]
     * @return Array [[文件完整路径],是否找到重复文件]
     */
    function resizeto($imagefile, $savefile, $key, $type, $sizequality) {
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
     * @description: 检查文件是否符合要求
     * @param String nowfile 当前文件完整路径
     * @param Array uploadconf 上传配置数组
     * @param Array enable 允许上传的媒体类别，默认["image","video"]都可以
     * @return Array [错误代码(0正常),MIME类型,扩展名,image还是video]
     */
    function chkfile($nowfile, $uploadconf, $enable = ["image", "video"]) {
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
     * @description: 獲取影片詳細資訊（第一次獲取時會建立快取）
     * composer require php-ffmpeg/php-ffmpeg
     * @param String file 影片檔案路徑
     * @param Array key 要獲取的屬性
     * @return Array 取得的影片資訊
     */
    function getvideomediainfo(string $file, array $key): array {
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
     * @description: 通过读取文件头原始数据，判断文件真实类型（已废弃）
     * @param String filename 文件完整路径
     * @return String 文件扩展名
     */
    function getImagetype($filename) {
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
     * @description: 合并多name上传的文件
     * @param Array $_FILES
     * @return Array 转换后的数组
     */
    function filearr($files) {
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
