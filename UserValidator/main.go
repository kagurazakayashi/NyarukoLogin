package main

import (
	"encoding/hex"
	"encoding/json"
	"flag"
	"fmt"
	"io"
	"log"
	"os"
	"os/signal"
	"syscall"

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

	// 解碼 PASETO 密鑰
	pasetoKey, err := hex.DecodeString(cfg.PasetoSecretKey)
	if err != nil || len(pasetoKey) != 32 {
		fmt.Fprintf(errWriter, "[錯誤] PASETO 密鑰無效: 需要 64 個十六進位字元 (32 bytes)\n")
		os.Exit(1)
	}

	fmt.Fprintf(outWriter, "[資訊] NATS 伺服器: %s:%d\n", cfg.NatsConfig.NatsServerHost, cfg.NatsConfig.NatsServerPort)
	fmt.Fprintf(outWriter, "[資訊] 訂閱主題: %s\n", cfg.NatsSubject)

	// 建立 NATS 連線
	natsLogger := log.New(outWriter, "[NATS] ", 0)
	natsClient := nyanats.NewC(cfg.NatsConfig, natsLogger)

	if err := natsClient.Error(); err != nil {
		fmt.Fprintf(errWriter, "[錯誤] NATS 連線失敗: %v\n", err)
		os.Exit(1)
	}

	// 訂閱主題並設定回應處理函式
	err = natsClient.Subscribe(cfg.NatsSubject, func(m string) string {
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

		fmt.Fprintf(outWriter, "[資訊] 收到請求: %s %s 來自 %s\n", req.Method, req.Path, req.IP)

		var respBody []byte
		var statusCode int

		switch req.Path {
		case "/validate":
			result := handleLogin(&req, pasetoKey, &cfg.PasetoConfig)
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

		case "/verify":
			result := handleVerify(&req, pasetoKey, &cfg.PasetoConfig)
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
		fmt.Fprintf(errWriter, "[錯誤] 訂閱失敗: %v\n", err)
		os.Exit(1)
	}

	fmt.Fprintf(outWriter, "[資訊] 服務已啟動，等待請求中... (Ctrl+C 結束)\n")

	// 優雅關閉：等待系統訊號
	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit

	fmt.Fprintf(outWriter, "[資訊] 正在關閉...\n")
	if err := natsClient.UnsubscribeAll(); err != nil {
		fmt.Fprintf(errWriter, "[錯誤] 取消訂閱失敗: %v\n", err)
	}

	natsClient.Close()
	fmt.Fprintf(outWriter, "[資訊] 關閉完成\n")
}
