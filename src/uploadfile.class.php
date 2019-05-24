<?php
// require_once 'vendor/autoload.php';
class uploadfile {
    function getuploadfile() {
        global $nlcore;
        // if (!isset($_FILES["file"])) return false;
        $uploadconf = $nlcore->cfg->app->upload;
        $uploadconf["type"] = $nlcore->cfg->app->uploadtype;
        //整理文件详细信息数组
        $files = $nlcore->safe->dicvals2arrsdic($this->filearr($_FILES));
        //准备文件存储路径(绝对路径,/结尾)
        $savedir = $this->savepath($uploadconf["uploaddir"],$uploadconf["datedir"],$uploadconf["chmod"]).DIRECTORY_SEPARATOR; //目标存储文件夹
        $stmpdir = $this->savepath($uploadconf["tmpdir"]).DIRECTORY_SEPARATOR; //二压临时文件夹
        //遍历文件资讯
        $returnarr = [];
        foreach ($files as $nowfile) {
            $mediatype = $this->chkfile($nowfile,$uploadconf); //检查文件
            $code = 1000000;
            $tmpfile = $nowfile["tmp_name"];
            if (is_numeric($mediatype)) {
                $code = $mediatype; //返回错误
                $returnfileinfo = ["code" => $code];
            } else {
                $returnfileinfo = $mediatype;
                $returnfileinfo["code"] = $code;
            }
            $newfilename = $nlcore->safe->randstr(64); //创建文件名
            $twotmpfile = $uploadconf["tmpdir"];
            $savefile = $savedir.$newfilename; //最终存储文件完整路径
            $savetmpfile = $stmpdir.$newfilename; //最终存储临时文件完整路径
            //是视频还是图片？
            if ($mediatype == "image") {
                //如果是图片文件则直接进行二压
                $nowfilename = $savefile.'.'.$key;
                foreach ($nlcore->cfg->app->imageresize as $key => $value) {
                    $newfiles = $this->resizeto($tmpfile, $nowfilename, $mediatype[1], $value, $uploadconf["quality"]);
                    $returnfileinfo["files"] = $newfiles;
                }
            } else if ($mediatype == "video") {
                //如果是视频文件则移动到临时目录进行二压，生成临时操作指令
                //暂无需要，预留位置暂不开发
            }
            return $returnfileinfo;
        }
        return null;
    }

    /**
     * @description: 缩小图片（如果已经小于设定值则输出原尺寸）
     * @param Float imageWidth 原始图片宽度
     * @param Float imageHeight 原始图片高度
     * @param Float maxWidth 目标尺寸宽度
     * @param Float maxHeight 目标尺寸高度
     * @return Array<Float> 新的宽高
     */
    function getresize($imageWidth,$imageHeight,$maxWidth,$maxHeight) {
        $newWidth = $imageWidth;
        $newHeight = $imageHeight;
        $imageScale = $imageWidth / $imageHeight;
        $maxScale = $maxWidth / $maxHeight;
        if ($maxScale < $imageScale) {
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $imageScale;
        } else if ($maxScale > $imageScale) {
            $newHeight = $maxHeight;
            $newWidth = $maxHeight * $imageScale;
        }
        return [$newWidth,$newHeight];
    }

    function resizeto($imagefile,$tofile,$type,$size,$quality) {
        $imagick = new Imagick($imagefile);
        $imagick->stripImage(); //去除图片信息
        $imageWidth = $imagick->getImageWidth();
        $imageHeight = $imagick->getImageHeight();
        if ($imageWidth <= 0 || $imageHeight <= 0) {
            $imageWidth = imagesx($srcImg);
            $imageHeight = imagesy($srcImg);
        }
        $newsize = $this->getresize($imageWidth,$imageHeight,$size[0],$size[1]);
        if ($type == "gif") {
            $transparent = new ImagickPixel("transparent");
            $newimagick = new Imagick();
            foreach($imagick as $img){
                echo '=';
                $page = $img->getImagePage();
                $tmp = new Imagick();
                $tmp->newImage($page['width'], $page['height'], $transparent, 'gif');
                $tmp->compositeImage($img, Imagick::COMPOSITE_OVER, $page['x'], $page['y']);
                $tmp->thumbnailImage($newsize[0], $newsize[1], true);
                $tmp->setFormat("gif");
                $newimagick->addImage($tmp);
                $newimagick->setImagePage($tmp->getImageWidth(), $tmp->getImageHeight(), 0, 0);
                $newimagick->setImageDelay($img->getImageDelay());
                $newimagick->setImageDispose($img->getImageDispose());
            }
            $newimagick->setFormat("gif");
            $savefilename = $tofile.".gif";
            $newimagick->writeImages($savefilename,true);
            return [$savefilename];
        } else {
            $imagick->adaptiveResizeImage($newsize[0], $newsize[1]);
            $imagick->setImageCompressionQuality($quality); //图片质量
            $webp = $imagick;
            $webp->setFormat("webp");
            $savefilename1 = $tofile.".webp";
            $webp->writeImage($savefilename1);
            $jpeg = $imagick;
            $jpeg->setFormat("jpeg");
            $savefilename2 = $tofile.".jpg";
            $jpeg->writeImage($savefilename2);
            return [$savefilename1,$savefilename2];
        }
    }

    // 使用 GD 库（已弃用）
    // function resizeto($imagefile,$tofile,$type,$size) {
    //     $srcImg = null;
    //     $isGIF = false;
    //     switch ($type) {
    //         case 'jpg':
    //             $srcImg = imagecreatefromjpeg($imagefile);
    //             break;
    //         case 'png':
    //             $srcImg = imagecreatefrompng($imagefile);
    //             break;
    //         case 'gif':
    //             $srcImg = imagecreatefromgif($imagefile);
    //             $isGIF = true;
    //             break;
    //         case 'webp':
    //             $srcImg = imagecreatefromwebp($imagefile);
    //             break;
    //         case 'bmp':
    //             $srcImg = imagecreatefrombmp($imagefile);
    //             break;
    //         default:
    //             break;
    //     }
    //     if (!$srcImg) return false;
    //     $srcWidth = imagesx($srcImg);
    //     $srcHeight = imagesy($srcImg);
    //     $newsize = $this->getresize($srcWidth,$srcHeight,$size[0],$size[1]);
    //     $newImg = imagecreatetruecolor($newsize[0], $newsize[1]);
    //     echo count($srcImg);
    //     $alpha = imagecolorallocatealpha($newImg, 0, 0, 0, 127);
    //     imagefill($newImg, 0, 0, $alpha);
    //     imagecopyresampled($newImg, $srcImg, 0, 0, 0, 0, $newsize[0], $newsize[1], $srcWidth, $srcHeight);
    //     imagesavealpha($newImg, true);
    //     // imagepng($newImg, $tofile);
    //     // imagewebp($newImg, $tofile.'.webp');
    //     // imagegif($newImg, $tofile.'.gif');
    //     imagedestroy($newImg);
    //     return true;
    // }

    /**
     * @description: 检查文件是否符合要求
     * @param String nowfile 当前文件完整路径
     * @param Array uploadconf 上传配置数组
     * @param Array enable 允许上传的媒体类别，默认["image","video"]都可以
     * @return Array [MIME类型,扩展名,image还是video]
     */
    function chkfile($nowfile,$uploadconf,$enable=["image","video"]) {
        $nowreturnarr = [
            "name" => $nowfile["name"]
        ];
        //检查错误代码
        if ($nowfile["error"] != 0) {
            return 2050100;
        }
        //检查文件大小是否超过限制
        $maxsize = $uploadconf["maxsize"];
        if ($nowfile["size"] > $maxsize["all"]) {
            return 2050101;
        }
        //检查文件类型是否符合
        $fi = new finfo(FILEINFO_MIME_TYPE); //FILEINFO_MIME_TYPE FILEINFO_EXTENSION
        $mime_type = $fi->file($nowfile["tmp_name"]);
        //mediatype [MIME类型, 扩展名, 媒体类别(图片/视频)] ，来自设置 uploadtype 。
        $mediatype = null;
        foreach ($uploadconf["type"] as $typename => $typevalue) {
            foreach ($typevalue as $typearr) {
                if ($mime_type == $typearr[0]) {
                    $mediatype = $typearr;
                    $mediatype[2] = $typename;
                    break;
                }
            }
        }
        if (!$mediatype || !in_array($mediatype[2],$enable)) {
            return 2050102;
        }
        if ($mediatype[2] == "video") { //如果是视频
            //当前类型的限制大小
            if ($nowfile["size"] > $maxsize["video"]) {
                return 2050101;
            }
            //检查视频时长是否太长
            $videoduration = $this->getvideoduration($nowfile["tmp_name"]);
            if ($videoduration > $uploadconf["videoduration"]) {
                return 2050103;
            }
        } else if ($mediatype[2] == "image") { //如果是图片
            //当前类型的限制大小
            if ($mediatype[1] == "gif" && $nowfile["size"] > $maxsize["gif"]) {
                return 2050101;
            } else if ($nowfile["size"] > $maxsize["image"]) {
                return 2050101;
            }
        }
        return $mediatype;
    }

    function getvideoduration($file) {
        $ffprobe = FFMpeg\FFProbe::create();
        $duration = $ffprobe
            ->format($file)
            ->get('duration');
        echo $duration;
    }

    /**
     * @description: 通过读取文件头原始数据，判断文件真实类型（已废弃）
     * @param String filename 文件完整路径
     * @return String 文件扩展名
     */
    function getImagetype($filename)
    {
        $file = fopen($filename, 'rb');
        $bin = fread($file, 2); //只读2字节
        fclose($file);
        $strInfo = @unpack('C2chars', $bin);
        $typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
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
     * @description: 获取目的地文件夹
     * @param String uploadpath 要存储的文件夹
     * @return String 绝对路径,无/结尾
     */
    function savepath($uploadpath,$datedir=false,$chmod=0770) {
        $uploadto = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR.$uploadpath;
        if ($datedir) {
            $uploadto = $uploadto.DIRECTORY_SEPARATOR.date('Y').DIRECTORY_SEPARATOR.date('m').DIRECTORY_SEPARATOR.date('d');
            if (!is_dir($uploadto)) {
                mkdir($uploadto,$chmod,true);
            }
        } else if (!is_dir($uploadto)) {
            mkdir($uploadto,$chmod,true);
        }
        return $uploadto;
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
                        for ($i=0; $i < count($fvalue); $i++) {
                            array_push($filearr["form"],$fileskey);
                        }
                        $firstadd = false;
                    }
                    $filearr[$fkey] = array_merge($filearr[$fkey],$fvalue);
                }
            } else {
                foreach ($filesvalue as $fkey => $fvalue) {
                    array_push($filearr[$fkey],$fvalue);
                }
                array_push($filearr["form"],$fileskey);
            }
        }
        return $filearr;
    }
}
?>
