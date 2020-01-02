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
	"os"
	"os/exec"
	"path/filepath"
	"strconv"
	"strings"
)

//Filejson is json的结构.
type Filejson struct {
	Type   string          `json:"type"`
	Temp   string          `json:"temp"`
	Todir  string          `json:"todir"`
	Toname string          `json:"toname"`
	To     [][]interface{} `json:"to"`
}

var uploadtmp string
var watermarkimage string
var nickname string
var wmfont string

// 图片水印+文字水印
// convert pic.jpg -resize 1000x1000 sy.jpg -gravity southeast -geometry +0+20 -gravity southeast -fill white -pointsize 16 -draw "text 5,5 'www.zeyuze.com'" -quality 80% -composite ok.jpg

func main() {
	flag.StringVar(&uploadtmp, "u", "/mnt/wwwroot/zyz/upload_tmp", "需要扫描的路径")
	flag.StringVar(&watermarkimage, "w", "/mnt/wwwroot/zyz/img/logo.png", "水印logo位置")
	flag.StringVar(&nickname, "n", "择择", "指定删除多少行")
	flag.StringVar(&wmfont, "f", "Noto Sans CJK SC", "指定删除多少行")
	flag.Parse()
	var fl = getFilesList(uploadtmp)
	if len(fl) > 0 {
		for _, flv := range fl {
			njson := readJSON(readfile(flv))
			// println(njson.Temp)
			for _, v := range njson.To {
				if njson.Type == "image" {
					scaleImage(njson.Temp, njson.Todir, njson.Toname, v)
				} else {

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
	check(err)
	return fl
}

func readfile(fp string) []byte {
	configfile, err := os.OpenFile(fp, os.O_RDONLY, 0755)
	defer configfile.Close()
	check(err)
	fi, _ := configfile.Stat()
	data := make([]byte, fi.Size())
	n, err := configfile.Read(data)
	check(err)
	// fmt.Println(string(data[:n]))
	return data[:n]
}

func readJSON(data []byte) *Filejson {
	var filejson Filejson
	data = []byte(os.ExpandEnv(string(data)))
	err := json.Unmarshal(data, &filejson)
	check(err)
	return &filejson
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
	check(err)
}

func scaleImage(url string, todir string, toname string, tofile []interface{}) {
	cmd := exec.Command("mkdir", "-p", todir)
	stdout, err := cmd.StdoutPipe()
	check(err)
	defer stdout.Close()
	err = cmd.Start()
	check(err)
	opBytes, err := ioutil.ReadAll(stdout)
	check(err)
	fmt.Println(string(opBytes))

	// time.Sleep(500000000)
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
	stdout, err = cmd.StdoutPipe()
	check(err)
	defer stdout.Close()
	err = cmd.Start()
	check(err)
	opBytes, err = ioutil.ReadAll(stdout)
	check(err)
	fmt.Println(string(opBytes))
}

//对错误检查的封装
func check(err error) {
	if err != nil {
		fmt.Println("error:", err)
	}
}
