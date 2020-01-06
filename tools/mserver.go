package main

import (
	"fmt"
	"io/ioutil"
	"log"
	"net/http"
	"os"
	"os/exec"
	"os/signal"
	"runtime"
	"syscall"
)

// func sayhelloName(w http.ResponseWriter, r *http.Request) {
// 	r.ParseForm()       //解析参数，默认是不会解析的
// 	fmt.Println(r.Form) //这些信息是输出到服务器端的打印信息
// 	fmt.Println("path", r.URL.Path)
// 	fmt.Println("scheme", r.URL.Scheme)
// 	fmt.Println(r.Form["url_long"])
// 	for k, v := range r.Form {
// 		fmt.Println("key:", k)
// 		fmt.Println("val:", strings.Join(v, ""))
// 	}
// 	fmt.Fprintf(w, "Hello astaxie!") //这个写入到w的是输出到客户端的
// }

func main() {
	// http.HandleFunc("/", sayhelloName)     //设置访问的路由
	http.HandleFunc("/image", convertimage)
	http.HandleFunc("/video", convertvideo)
	err := http.ListenAndServe(":1081", nil) //设置监听的端口
	// check("ListenAndServe", err)
	if err != nil {
		log.Fatal("ListenAndServe:", err)
	}
}

func convertimage(w http.ResponseWriter, r *http.Request) {
	go goimage(w)
}

func goimage(w http.ResponseWriter) {
	barname := "/mnt/wwwroot/go/gowebserver/convertimage"
	cmd := exec.Command(barname, "-v")
	// println(barname)
	stdout, err := cmd.StdoutPipe()
	check(w, "cmd.StdoutPipe", err)
	defer stdout.Close()
	err = cmd.Start()
	check(w, "cmd.Start", err)
	opBytes, err := ioutil.ReadAll(stdout)
	check(w, "ioutil.ReadAll", err)
	fmt.Println(string(opBytes))
	// fmt.Fprintf(w, "1000000")
	runtime.Goexit()
}

func convertvideo(w http.ResponseWriter, r *http.Request) {
	go govideo(w)
}

func govideo(w http.ResponseWriter) {
	fmt.Println("govideo")
	barname := "/mnt/wwwroot/go/gowebserver/convertvideo"
	cmd := exec.Command(barname, "-v")
	// println(barname)
	stdout, err := cmd.StdoutPipe()
	check(w, "cmd.StdoutPipe", err)
	defer stdout.Close()
	err = cmd.Start()
	check(w, "cmd.Start", err)
	opBytes, err := ioutil.ReadAll(stdout)
	check(w, "ioutil.ReadAll", err)
	fmt.Println(string(opBytes))
	// fmt.Fprintf(w, "1000000")
	runtime.Goexit()
}

func setupCloseHandler() {
	c := make(chan os.Signal, 2)
	signal.Notify(c, os.Interrupt, syscall.SIGTERM)
	go func() {
		<-c
		fmt.Println("\r- Ctrl+C pressed in Terminal")
		os.Exit(0)
	}()
}

//对错误检查的封装
func check(w http.ResponseWriter, msg string, err error) {
	if err != nil {
		// log.Fatal(msg, ":", err)
		errmsg := fmt.Sprintf("%s:%s", msg, err)
		fmt.Fprintf(w, errmsg)
	}
}
