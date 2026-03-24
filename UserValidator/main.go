package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"io"
	"log"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/kagurazakayashi/libNyaruko_Go/nyanats"
)

var (
	outWriter io.Writer = os.Stdout
	errWriter io.Writer = os.Stderr
)

func main() {
	var configPath string
	var logFilePath string

	flag.StringVar(&configPath, "c", "", "yaml 設定檔路徑")
	flag.StringVar(&logFilePath, "o", "", "日誌輸出檔案路徑")
	flag.Parse()

	// 設定日誌輸出（同時寫入終端與檔案）
	if logFilePath != "" {
		logFile, err := os.OpenFile(logFilePath, os.O_CREATE|os.O_WRONLY|os.O_APPEND, 0644)
		if err != nil {
			fmt.Fprintf(os.Stderr, "[錯誤] 無法開啟日誌檔: %v\n", err)
			os.Exit(1)
		}
		defer logFile.Close()

		outWriter = io.MultiWriter(os.Stdout, logFile)
		errWriter = io.MultiWriter(os.Stderr, logFile)
	}

	// 載入設定檔
	cfg, err := loadConfig(configPath)
	if err != nil {
		fmt.Fprintf(errWriter, "[錯誤] %v\n", err)
		os.Exit(1)
	}

	// 解析金鑰環（支援單一金鑰或金鑰輪替字典兩種格式）
	keyRing, err := cfg.PasetoSecretKey.ToKeyRing()
	if err != nil {
		fmt.Fprintf(errWriter, "[錯誤] %v\n", err)
		os.Exit(1)
	}
	fmt.Fprintf(outWriter, "[INFO] PASETO 金鑰數量: %d (最新金鑰時間戳: %d)\n", len(keyRing.Keys), keyRing.Keys[0].Timestamp)

	// 解析資料庫請求設定
	dbSubject := cfg.NatsPublish.DBRequestSubject
	if dbSubject == "" {
		dbSubject = "db_request"
	}

	dbTimeout, err := time.ParseDuration(cfg.NatsPublish.DBRequestTimeout)
	if err != nil || dbTimeout <= 0 {
		dbTimeout = 5 * time.Second
	}

	fmt.Fprintf(outWriter, "[INFO] 資料庫請求主题: %s (逾時: %s)\n", dbSubject, dbTimeout)

	fmt.Fprintf(outWriter, "[INFO] NATS 伺服器: %s:%d\n", cfg.NatsConfig.NatsServerHost, cfg.NatsConfig.NatsServerPort)

	subjects := cfg.getSubjects()
	if len(subjects) == 0 {
		fmt.Fprintf(errWriter, "[錯誤] 未設定任何 NATS 訂閱主題\n")
		os.Exit(1)
	}
	for _, s := range subjects {
		fmt.Fprintf(outWriter, "[INFO] 訂閱主題: %s\n", s)
	}

	// 建立 NATS 連線
	natsLogger := log.New(outWriter, "[NATS] ", 0)
	natsClient := nyanats.NewC(cfg.NatsConfig, natsLogger)

	if err := natsClient.Error(); err != nil {
		fmt.Fprintf(errWriter, "[錯誤] NATS 連線失敗: %v\n", err)
		os.Exit(1)
	}

	// 為每個主題建立獨立訂閱，主題名稱即為路由路徑
	for _, subject := range subjects {
		subj := subject // 閉包捕獲
		err := natsClient.Subscribe(subj, func(m string) string {
			var req bridgeRequest

			if err := json.Unmarshal([]byte(m), &req); err != nil {
				fmt.Fprintf(errWriter, "[錯誤] 無法解析請求: %v\n", err)

				resp, _ := json.Marshal(bridgeResponse{
					StatusCode: 400,
					Headers:    map[string]string{"Content-Type": "application/json; charset=utf-8"},
					Body:       `{"error":"invalid request"}`,
				})
				return string(resp)
			}

			fmt.Fprintf(outWriter, "[INFO] 收到請求: %s 來自 %s\n", subj, req.IP)

			var respBody []byte
			var statusCode int

			// 按訂閱的主题名直接路由（主题名即等於 HTTP 路徑）
			switch subj {
			case "/auth/login":
				result := handleLogin(&req, keyRing, &cfg.PasetoConfig, &cfg.TokenClaimsMapping, natsClient.Request, dbSubject, dbTimeout)
				switch {
				case result.Success:
					statusCode = 200
				case result.Message == "invalid request body":
					statusCode = 400
				case result.Message == "invalid credentials":
					statusCode = 401
				default:
					statusCode = 404
				}
				respBody, _ = json.Marshal(result)

			case "/auth/verify":
				result := handleVerify(&req, keyRing, &cfg.PasetoConfig)
				switch {
				case result.Success:
					statusCode = 200
				case result.Message == "invalid request body",
					result.Message == "token is required":
					statusCode = 400
				default:
					statusCode = 401
				}
				respBody, _ = json.Marshal(result)

			default:
				result := notFoundResponse()
				statusCode = 404
				respBody, _ = json.Marshal(result)
			}

			resp, _ := json.Marshal(bridgeResponse{
				StatusCode: statusCode,
				Headers:    map[string]string{"Content-Type": "application/json; charset=utf-8"},
				Body:       string(respBody),
			})
			return string(resp)
		})

		if err != nil {
			fmt.Fprintf(errWriter, "[錯誤] 訂閱主題 %s 失敗: %v\n", subj, err)
			os.Exit(1)
		}
	}

	fmt.Fprintf(outWriter, "[INFO] 服務已啟動，等待請求中... (Ctrl+C 結束)\n")

	// 優雅關閉：等待系統訊號
	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit

	fmt.Fprintf(outWriter, "[INFO] 正在關閉...\n")
	if err := natsClient.UnsubscribeAll(); err != nil {
		fmt.Fprintf(errWriter, "[錯誤] 取消訂閱失敗: %v\n", err)
	}

	natsClient.Close()
	fmt.Fprintf(outWriter, "[INFO] 關閉完成\n")
}
