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
	PasetoConfig    pasetoConfig       `json:"paseto_config" yaml:"paseto_config"`
}

// pasetoConfig PASETO 令牌參數設定
type pasetoConfig struct {
	Version   string `json:"paseto_version" yaml:"paseto_version"` // PASETO 協定版本 (v1/v2)
	TokenTTL  string `json:"token_ttl" yaml:"token_ttl"`           // 令牌有效時長，如 "24h"、"30m"、"7d"
	Issuer    string `json:"issuer" yaml:"issuer"`                 // 簽發者 (iss claim)，選填
	Audience  string `json:"audience" yaml:"audience"`             // 受眾 (aud claim)，選填
	NotBefore string `json:"not_before" yaml:"not_before"`         // 生效時間偏移，如 "0s" 為立即生效、"5m" 為五分鐘後生效
	EnableJTI bool   `json:"enable_jti" yaml:"enable_jti"`         // 是否為每個令牌產生唯一識別碼 (jti claim)
	Footer    string `json:"footer" yaml:"footer"`                 // 自訂 Footer 明文，選填
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
