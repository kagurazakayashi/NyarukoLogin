package main

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"

	"github.com/kagurazakayashi/libNyaruko_Go/nyanats"
	"gopkg.in/yaml.v3"
)

// serviceConfig 服務設定結構
type serviceConfig struct {
	NatsConfig      nyanats.NatsConfig `json:"nats_config" yaml:"nats_config"`
	NatsSubject     string             `json:"nats_subject" yaml:"nats_subject"`
	PasetoSecretKey string             `json:"paseto_secret_key" yaml:"paseto_secret_key"`
}

// loadConfig 載入 YAML 設定檔
// 若 configPath 為空，則自動尋找與執行檔同名的 .yaml 檔案
func loadConfig(configPath string) (*serviceConfig, error) {
	if configPath == "" {
		exePath, err := os.Executable()
		if err != nil {
			exePath = os.Args[0]
		}
		exeBase := filepath.Base(exePath)
		configPath = strings.TrimSuffix(exeBase, filepath.Ext(exeBase)) + ".yaml"
	}

	data, err := os.ReadFile(configPath)
	if err != nil {
		return nil, fmt.Errorf("無法讀取設定檔: %w (路徑: %s)", err, configPath)
	}

	var cfg serviceConfig
	if err := yaml.Unmarshal(data, &cfg); err != nil {
		return nil, fmt.Errorf("無法解析設定檔: %w", err)
	}

	return &cfg, nil
}
