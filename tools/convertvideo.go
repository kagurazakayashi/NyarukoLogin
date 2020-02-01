/*@Author: 0wew0
 * @Date: 2020-03-03 17:44:13
 * @LastEditTime: 2020-03-03 17:45:15
 * @LastEditors: 0wew0
 * @Description: In User Settings Edit
 * @FilePath: /zyz/user/tools/go/cleandb/cleandb.go
 */
 package main

 import (
	 "crypto/md5"
	 "encoding/hex"
	 "encoding/json"
	 "flag"
	 "fmt"
	 "io/ioutil"
	 "math"
	 "os"
	 "os/exec"
	 "path/filepath"
	 "strings"
 
	 "github.com/go-redis/redis"
 )
 
 //Filejson is json的结构.
 type Filejson struct {
	 Type   string                 `json:"type"`
	 Temp   string                 `json:"temp"`
	 Todir  string                 `json:"todir"`
	 Toname string                 `json:"toname"`
	 Info   map[string]interface{} `json:"info"`
	 To     [][]interface{}        `json:"to"`
 }
 
 // var uploadtmp string
 var watermarkimage string
 var nickname string
 var wmfont string
 var keypath string
 
 var verbose bool
 
 //redis中所有image的key
 var arrkeys []string
 
 // redis设置
 // 声明一个全局的redisdb变量
 var redisdb *redis.Client
 var redisaddress string = "127.0.0.1:6379"
 var redispassword string = "4XQa7P8kVN1EUgopyU1v43D7tZhYe8jc"
 var redisdbid int = 10
 
 func main() {
	 flag.BoolVar(&verbose, "v", false, "是否显示输出")
	 flag.StringVar(&redisaddress, "r", "127.0.0.1:6379", "redis地址（带端口）")
	 flag.StringVar(&redispassword, "rp", "RfryNXkEUG3OnyDI06zK1mqWA7oQslqvc8IEgHh78BpACCaUZIN44zrlUyDIq8xL3unaZJpWd592DrJifvymOdLHCAIN0ycg1TzvatE2tJiu40kr06Aub1vfjYGIWadevBm70UDTClutBxWTjInt3fsZomDXQvYjrRktguqJeGT0RgfJA95XgTDQGqp2Eo7D33EhIU8zSQpjy3e97Bbl5yFvoqERz3wUBvcFd7K95Eas4DZpld3NV7fuk1tdh7Xa", "redis密码")
	 flag.IntVar(&redisdbid, "rid", 1, "redis数据库ID")
	 // flag.StringVar(&uploadtmp, "path", "/mnt/wwwroot/zyz/upload_tmp", "需要扫描的路径")
	 flag.StringVar(&watermarkimage, "wmimage", "/mnt/wwwroot/zyz/img/logo.png", "水印logo位置")
	 flag.StringVar(&nickname, "nick", "@择择#213", "指定删除多少行")
	 flag.StringVar(&wmfont, "wmfont", "simhei.ttf", "水印字体")
	 flag.StringVar(&keypath, "kpath", "/mnt/wwwroot/zyz/user/tools/go/convertfile/file/encrypt.keyinfo", "视频加密key地址")
	 flag.Parse()
 
	 fmt.Println("STRART convertbideo v1.0")
	 err := initRedis()
	 check(0, "initRedis", err)
 
	 allkeys()
 }
 
 func allkeys() {
	 rkeys := redisdb.Keys("v_*")
	 arrkeys := rkeys.Val()
	 if verbose {
		 fmt.Println(arrkeys)
	 }
	 if len(arrkeys) > 0 {
		 runConvert()
	 } else {
		 rdel := redisdb.Del("vc")
		 if verbose {
			 fmt.Println("删除处理视频状态key", rdel)
		 }
		 redisdb.Close()
	 }
 }
 
 func runConvert() {
	 allkeysjson, err := json.Marshal(arrkeys)
	 check(0, "创建正在处理视频状态key出错", err)
 
	 err = redisdb.Set("vc", allkeysjson, 0).Err()
	 check(0, "创建正在处理视频状态key出错", err)
 
	 if len(arrkeys) > 0 {
		 for _, flv := range arrkeys {
			 val2, err := redisdb.Get(flv).Result()
			 check(0, "redisdb.Get.Result", err)
 
			 var someOne Filejson
			 if err := json.Unmarshal([]byte(val2), &someOne); err == nil {
				 if err == redis.Nil {
					 fmt.Println(flv, " does not exists")
				 } else if err != nil {
					 check(0, "视频json错误", err)
				 } else {
					 // fmt.Println(someOne)
					 // if njson.Type == "video" {
					 mkdir(someOne.Todir)
					 getVideoSizeMakeThumbnail(someOne.Temp, someOne.Todir, someOne.Toname, someOne.Info)
					 // }
					 for _, v := range someOne.To {
						 if verbose {
							 fmt.Println("正在处视频：", someOne.Temp, someOne.Todir, someOne.Toname, someOne.Info, v)
						 }
						 scaleVideo(someOne.Temp, someOne.Todir, someOne.Toname, someOne.Info, v)
					 }
					 filemd5(readfile(someOne.Temp), someOne.Todir, someOne.Toname)
 
					 rdel := redisdb.Del(flv)
					 if verbose {
						 fmt.Println("删除处理视频key", rdel)
					 }
					 err := os.Remove(someOne.Temp)
					 check(0, "del file", err)
				 }
			 } else {
				 fmt.Println(err)
			 }
		 }
	 }
	 allkeys()
 }
 
 // 初始化连接
 func initRedis() (err error) {
	 redisdb = redis.NewClient(&redis.Options{
		 Addr:     redisaddress,
		 Password: redispassword, // no password set
		 DB:       redisdbid,     // use default DB
	 })
 
	 _, err = redisdb.Ping().Result()
	 check(0, "redisdb.Ping.Result", err)
	 return nil
 }
 
 func getFilesList(path string) []string {
	 var fl []string
	 err := filepath.Walk(path, func(path string, f os.FileInfo, err error) error {
		 if f == nil {
			 return err
		 }
		 if f.IsDir() {
			 return nil
		 }
		 isjson := strings.Split(path, ".")
		 isjsonlast := len(isjson) - 1
		 if isjson[isjsonlast] == "json" {
			 fl = append(fl, path)
		 }
		 return nil
	 })
	 check(0, "filepath.Walk", err)
	 return fl
 }
 
 func readfile(fp string) []byte {
	 configfile, err := os.OpenFile(fp, os.O_RDONLY, 0755)
	 defer configfile.Close()
	 check(0, "os.OpenFile", err)
	 fi, _ := configfile.Stat()
	 data := make([]byte, fi.Size())
	 n, err := configfile.Read(data)
	 check(0, "configfile.Read", err)
	 // fmt.Println(string(data[:n]))
	 return data[:n]
 }
 
 // func readJSON(data []byte) *Filejson {
 // 	var filejson Filejson
 // 	data = []byte(os.ExpandEnv(string(data)))
 // 	err := json.Unmarshal(data, &filejson)
 // 	check(0,"json.Unmarshal", err)
 // 	return &filejson
 // }
 
 func mkdir(todir string) {
	 cmd := exec.Command("mkdir", "-p", todir)
	 stdout, err := cmd.StdoutPipe()
	 check(0, "cmd.StdoutPipe", err)
	 defer stdout.Close()
	 err = cmd.Start()
	 check(0, "cmd.Start", err)
	 opBytes, err := ioutil.ReadAll(stdout)
	 check(0, "ioutil.ReadAll", err)
	 fmt.Println(string(opBytes))
 }
 
 func filemd5(data []byte, todir string, toname string) {
	 // cmd := exec.Command("mkdir", "-p", todir)
	 // stdout, err := cmd.StdoutPipe()
	 // check(err)
	 // defer stdout.Close()
	 // err = cmd.Start()
	 // check(err)
	 // opBytes, err := ioutil.ReadAll(stdout)
	 // check(err)
	 // fmt.Println(string(opBytes))
 
	 ret := md5.Sum(data)
	 MD5Str := hex.EncodeToString(ret[:])
	 // fmt.Printf("\n-----%s-----", MD5Str)
	 // 将保存的字符串转换为字节流
	 str := []byte(MD5Str)
	 // 保存到文件
	 tomd5 := fmt.Sprintf("%s/%s.md5", todir, toname)
	 // fmt.Printf("\n-----%s-----\n", tomd5)
	 err := ioutil.WriteFile(tomd5, str, 0666)
	 check(0, "ioutil.WriteFile", err)
 }
 
 //getvideosize is 获取视频尺寸并在目标文件夹生成对应的缩略图
 func getVideoSizeMakeThumbnail(temp string, todir string, toname string, info map[string]interface{}) {
	 fmt.Println("getVideoSizeMakeThumbnail")
	 // screenshotpath := ""
	 //生成截图
	 // tempimagepath := strings.Split(temp, ".")
	 duration := info["duration"].(float64)
	 if duration >= 10 {
		 duration = 10
	 } else if duration >= 0 {
		 duration = duration / 2
	 } else {
		 duration = 0
	 }
	 screenshotpath := fmt.Sprintf("%s/%s.jpg", todir, toname)
	 cmd := exec.Command("ffmpeg", "-i", temp, "-ss", fmt.Sprintf("%s", duration), "-vframes", "1", "-q:v", "8", "-f", "image2", screenshotpath, "-y")
	 stdout, err := cmd.StdoutPipe()
	 check(0, "cmd.StdoutPipe", err)
	 defer stdout.Close()
	 err = cmd.Start()
	 check(0, "cmd.Start", err)
 
	 // var w sync.WaitGroup
	 // w.Add(1)
	 // go func() {
	 opBytes, err := ioutil.ReadAll(stdout)
	 check(0, "ioutil.ReadAll", err)
	 fmt.Printf("opBytes:%s\n", string(opBytes))
	 // 	w.Done()
	 // }()
	 // w.Wait()
	 fmt.Printf("-screenshotpath-:%s\n", screenshotpath)
 
	 // //读取截图
	 // file, _ := os.Open(screenshotpath)
	 // defer file.Close()
	 // img, _, err := image.Decode(file)
	 // check(0,"image.Decode", err)
 
	 // b := img.Bounds()
	 // srcWidth := b.Max.X
	 // srcHeight := b.Max.Y
 
	 // fmt.Printf("width = %d\n", srcWidth)
	 // fmt.Printf("height = %d\n", srcHeight)
 
	 // //计算缩略图宽高
	 // ratio := math.Min(1280/float64(srcWidth), 720/float64(srcHeight))
	 // thumbnailW := int(math.Ceil(float64(srcWidth) * ratio))
	 // thumbnailH := int(math.Ceil(float64(srcHeight) * ratio))
 
	 // //生成缩略图
	 // tothumbnailpath := fmt.Sprintf("%s/%s.jpg", todir, toname)
	 // img = imaging.Resize(img, thumbnailW, thumbnailH, imaging.Lanczos)
	 // err = imaging.Save(img, tothumbnailpath)
	 // check(0,"imaging.Save", err)
	 // return screenshotpath
 }
 
 // func createImage(textName string) {
 
 // 	imgfile, _ := os.Create(textName + ".png")
 // 	defer imgfile.Close()
 // 	//创建位图,坐标x,y,长宽x,y
 // 	img := image.NewNRGBA(image.Rect(0, 0, 100, 40))
 // 	/*
 // 		// 画背景,这里可根据喜好画出背景颜色
 // 		for y := 0; y < dy; y++ {
 // 			for x := 0; x < dx; x++ {
 // 				//设置某个点的颜色，依次是 RGBA
 // 				img.Set(x, y, color.RGBA{uint8(x), uint8(y), 0, 255})
 // 			}
 // 		}
 // 	*/
 // 	//读字体数据
 // 	fontBytes, err := ioutil.ReadFile("consola.TTF")
 // 	check(0,"ioutil.ReadFile", err)
 
 // 	font, err := freetype.ParseFont(fontBytes)
 // 	check(0,"freetype.ParseFont", err)
 
 // 	c := freetype.NewContext()
 // 	c.SetDPI(72)
 // 	c.SetFont(font)
 // 	c.SetFontSize(40)
 // 	c.SetClip(img.Bounds())
 // 	c.SetDst(img)
 // 	c.SetSrc(image.White)
 // 	//设置字体显示位置
 // 	pt := freetype.Pt(5, 20+int(c.PointToFixed(40)>>8))
 // 	_, err = c.DrawString(textName, pt)
 // 	check(0,"c.DrawString", err)
 // 	//保存图像到文件
 // 	err = png.Encode(imgfile, img)
 // 	check(0,"png.Encode", err)
 
 // }
 func scaleVideo(temp string, todir string, toname string, info map[string]interface{}, tofile []interface{}) {
	 //计算目标视频文件宽高
	 ratio := math.Min(tofile[1].(float64)/info["width"].(float64), tofile[2].(float64)/info["height"].(float64))
	 toimageW := int(math.Ceil(float64(info["width"].(float64)) * ratio))
	 toimageW = toimageW / 2 * 2
	 toimageH := int(math.Ceil(float64(info["height"].(float64)) * ratio))
	 toimageH = toimageH / 2 * 2
	 fmt.Printf("toimageW = %d\n", toimageW)
	 fmt.Printf("toimageH = %d\n", toimageH)
 
	 vf := fmt.Sprintf("scale=%d:%d,'overlay=main_w-overlay_w-5:main_h-overlay_h-20',\"drawtext=fontfile=%s: text=%s:x=w-tw-5:y=h-th-5:fontsize=%d:fontcolor=%s\"", toimageW, toimageH, wmfont, nickname, 12, "white")
	 // vf := fmt.Sprintf("scale=%d:%d,'overlay=main_w-overlay_w-5:main_h-overlay_h-20'", toimageW, toimageH)
	 bitrate := fmt.Sprintf("%fk", tofile[3].(float64))
	 tspath := strings.Split(tofile[0].(string), ".")
	 tofileTSPath := fmt.Sprintf("%s/%s.%s", todir, toname, tspath[0])
	 // println(tofileTSPath)
	 tofilepath := fmt.Sprintf("%s/%s.%s", todir, toname, tofile[0])
	 // println(vf)
	 // println(temp)
	 // println(tofilepath)
	 // cmd := exec.Command("ffmpeg", "-i", temp, "-i", "logo.png", "-strict", "-2", "-filter_complex", vf, "-b:v", bitrate, tofilepath, "-hide_banner", "-y")
	 tempvideopath := strings.Split(temp, ".")
	 shfile := fmt.Sprintf("%s.%s.sh", tempvideopath[0], tofile[0])
	 // println(shfile)
	 sh := fmt.Sprintf("cat %s >>convertvideo.log && echo >>convertvideo.log && date >>convertvideo.log && /usr/local/ffmpeg/bin/ffmpeg -i %s -i %s -strict -2 -filter_complex %s -b:v %s -hls_time 5 -hls_key_info_file %s -hls_playlist_type vod -hls_segment_filename \"%s.%s.ts\" %s -hide_banner -y >>convertvideo.log 2>&1 && echo ===== >>convertvideo.log && rm -f %s", shfile, temp, watermarkimage, vf, bitrate, keypath, tofileTSPath, "%d", tofilepath, shfile)
	 content := []byte(sh)
	 err := ioutil.WriteFile(shfile, content, 0777)
	 check(1, "ioutil.WriteFile", err)
	 cmd := exec.Command("sh", shfile)
	 println(shfile)
	 stdout, err := cmd.StdoutPipe()
	 check(1, "cmd.StdoutPipe", err)
	 defer stdout.Close()
	 err = cmd.Start()
	 check(1, "cmd.Start", err)
	 opBytes, err := ioutil.ReadAll(stdout)
	 check(1, "ioutil.ReadAll", err)
	 fmt.Println(string(opBytes))
 }
 
 //对错误检查的封装
 func check(code int, msg string, err error) {
	 if err != nil {
		 fmt.Printf("msg:%s\nerror:%s\n", msg, err)
		 if code == 1 {
			 os.Exit(code)
		 }
	 }
 }
 