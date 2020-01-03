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
	"strconv"
	"strings"
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

var uploadtmp string
var watermarkimage string
var nickname string
var wmfont string
var keypath string

// 图片水印+文字水印
// convert pic.jpg -resize 1000x1000 sy.jpg -gravity southeast -geometry +0+20 -gravity southeast -fill white -pointsize 16 -draw "text 5,5 'www.zeyuze.com'" -quality 80% -composite ok.jpg

func main() {
	flag.StringVar(&uploadtmp, "path", "/mnt/wwwroot/zyz/upload_tmp", "需要扫描的路径")
	flag.StringVar(&watermarkimage, "wmimage", "/mnt/wwwroot/zyz/img/logo.png", "水印logo位置")
	flag.StringVar(&nickname, "nick", "@择择#213", "指定删除多少行")
	flag.StringVar(&wmfont, "wmfont", "simhei.ttf", "水印字体")
	flag.StringVar(&keypath, "kpath", "/mnt/wwwroot/zyz/user/tools/go/convertfile/file/encrypt.keyinfo", "指定删除多少行")
	flag.Parse()
	var fl = getFilesList(uploadtmp)
	if len(fl) > 0 {
		for _, flv := range fl {
			njson := readJSON(readfile(flv))
			// println(njson.Temp)
			if njson.Type == "video" {
				mkdir(njson.Todir)
				getVideoSizeMakeThumbnail(njson.Temp, njson.Todir, njson.Toname)
			}
			for _, v := range njson.To {
				mkdir(njson.Todir)
				if njson.Type == "image" {
					scaleImage(njson.Temp, njson.Todir, njson.Toname, v)
				} else {
					// screenshotpath := getVideoSizeMakeThumbnail(njson.Temp, njson.Todir, njson.Toname)
					// fmt.Println(screenshotpath)
					scaleVideo(njson.Temp, njson.Todir, njson.Toname, njson.Info, v)
				}
			}
			filemd5(readfile(njson.Temp), njson.Todir, njson.Toname)
		}
	}
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
	check("filepath.Walk", err)
	return fl
}

func readfile(fp string) []byte {
	configfile, err := os.OpenFile(fp, os.O_RDONLY, 0755)
	defer configfile.Close()
	check("os.OpenFile", err)
	fi, _ := configfile.Stat()
	data := make([]byte, fi.Size())
	n, err := configfile.Read(data)
	check("configfile.Read", err)
	// fmt.Println(string(data[:n]))
	return data[:n]
}

func readJSON(data []byte) *Filejson {
	var filejson Filejson
	data = []byte(os.ExpandEnv(string(data)))
	err := json.Unmarshal(data, &filejson)
	check("json.Unmarshal", err)
	return &filejson
}

func mkdir(todir string) {
	cmd := exec.Command("mkdir", "-p", todir)
	stdout, err := cmd.StdoutPipe()
	check("cmd.StdoutPipe", err)
	defer stdout.Close()
	err = cmd.Start()
	check("cmd.Start", err)
	opBytes, err := ioutil.ReadAll(stdout)
	check("ioutil.ReadAll", err)
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
	check("ioutil.WriteFile", err)
}

func scaleImage(url string, todir string, toname string, tofile []interface{}) {
	// time.Sleep(500000000)
	cmd := exec.Command("echo", "image")
	resolutionratio := fmt.Sprintf("%sx%s", strconv.FormatFloat(tofile[1].(float64), 'f', -1, 64), strconv.FormatFloat(tofile[2].(float64), 'f', -1, 64))
	precision := fmt.Sprintf("%s%s", strconv.FormatFloat(tofile[3].(float64), 'f', -1, 64), "%")
	// toext := fmt.Sprintf("%s", tofile[0])
	tourl := fmt.Sprintf("%s/%s.%s", todir, toname, tofile[0])
	// fmt.Printf("\n0:%s\n1:%s\n2:%s\n3:%s\n4:%s\n5:%s\n", url, resolutionratio, precision, toname, toext, tourl)
	if precision == "0%" {
		cmd = exec.Command("convert", url, tourl)
	} else {
		wmnickname := fmt.Sprintf("text 29,5 '%s'", nickname)
		cmd = exec.Command("convert", url, "-resize", resolutionratio, watermarkimage, "-gravity", "southeast", "-geometry", "+0+0", "-gravity", "southeast", "-fill", "white", "-font", wmfont, "-pointsize", "16", "-draw", wmnickname, "-quality", precision, "-composite", tourl)
	}
	stdout, err := cmd.StdoutPipe()
	check("cmd.StdoutPipe", err)
	defer stdout.Close()
	err = cmd.Start()
	check("cmd.Start", err)
	opBytes, err := ioutil.ReadAll(stdout)
	check("ioutil.ReadAll", err)
	fmt.Println(string(opBytes))
}

//getvideosize is 获取视频尺寸并在目标文件夹生成对应的缩略图
func getVideoSizeMakeThumbnail(temp string, todir string, toname string) {
	fmt.Println("getVideoSizeMakeThumbnail")
	// screenshotpath := ""
	//生成截图
	// tempimagepath := strings.Split(temp, ".")
	screenshotpath := fmt.Sprintf("%s/%s.jpg", todir, toname)
	cmd := exec.Command("ffmpeg", "-i", temp, "-vframes", "1", "-q:v", "8", "-f", "image2", screenshotpath, "-y")
	stdout, err := cmd.StdoutPipe()
	check("cmd.StdoutPipe", err)
	defer stdout.Close()
	err = cmd.Start()
	check("cmd.Start", err)

	// var w sync.WaitGroup
	// w.Add(1)
	// go func() {
	opBytes, err := ioutil.ReadAll(stdout)
	check("ioutil.ReadAll", err)
	fmt.Printf("opBytes:%s\n", string(opBytes))
	// 	w.Done()
	// }()
	// w.Wait()
	fmt.Printf("-screenshotpath-:%s\n", screenshotpath)

	// //读取截图
	// file, _ := os.Open(screenshotpath)
	// defer file.Close()
	// img, _, err := image.Decode(file)
	// check("image.Decode", err)

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
	// check("imaging.Save", err)
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
// 	check("ioutil.ReadFile", err)

// 	font, err := freetype.ParseFont(fontBytes)
// 	check("freetype.ParseFont", err)

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
// 	check("c.DrawString", err)
// 	//保存图像到文件
// 	err = png.Encode(imgfile, img)
// 	check("png.Encode", err)

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
	barname := fmt.Sprintf("%s.%s.sh", tempvideopath[0], tofile[0])
	// println(barname)
	bat := fmt.Sprintf("echo >>sh.log && date >>sh.log && /usr/local/ffmpeg/bin/ffmpeg -i %s -i %s -strict -2 -filter_complex %s -b:v %s -hls_time 5 -hls_key_info_file %s -hls_playlist_type vod -hls_segment_filename \"%s.%s.ts\" %s -hide_banner -y >>sh.log 2>&1 && echo ========== >>sh.log && echo >>sh.log && rm -f %s", temp, watermarkimage, vf, bitrate, keypath, tofileTSPath, "%d", tofilepath, barname)
	content := []byte(bat)
	err := ioutil.WriteFile(barname, content, 0777)
	check("ioutil.WriteFile", err)
	cmd := exec.Command("sh", barname)
	println(barname)
	stdout, err := cmd.StdoutPipe()
	check("cmd.StdoutPipe", err)
	defer stdout.Close()
	err = cmd.Start()
	check("cmd.Start", err)
	opBytes, err := ioutil.ReadAll(stdout)
	check("ioutil.ReadAll", err)
	fmt.Println(string(opBytes))
}

//对错误检查的封装
func check(msg string, err error) {
	if err != nil {
		fmt.Printf("msg:%s\nerror:%s\n", msg, err)
	}
}
