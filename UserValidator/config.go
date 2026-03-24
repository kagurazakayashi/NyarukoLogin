package main

import (
	"encoding/hex"
	"fmt"
	"os"
	"path/filepath"
	"sort"
	"strings"

	"github.com/kagurazakayashi/libNyaruko_Go/nyanats"
	"gopkg.in/yaml.v3"
)

// natsPublishConfig 對外發布訊息的設定（發送給 gateway-db 等微服務）
type natsPublishConfig struct {
	DBRequestSubject string `json:"db_request_subject" yaml:"db_request_subject"` // 資料庫請求的 NATS 主题
	DBRequestTimeout string `json:"db_request_timeout" yaml:"db_request_timeout"` // 資料庫請求逾時時間，格式如 "5s"
}

// pasetoSecretKeyConfig 用於解碼 paseto_secret_key 設定欄位，支援兩種格式：
//
//	舊格式（單一字串）：
//	  paseto_secret_key: "404142..."
//
//	新格式（金鑰輪替字典）：
//	  paseto_secret_key:
//	    1704067200: "404142..."
//	    1748736000: "606162..."
//
// 金鑰鍵值為 UNIX 整數時間戳，用於識別金鑰的生效時間與輪替順序
type pasetoSecretKeyConfig struct {
	Keys map[int64]string // 時間戳 → 十六進位金鑰字串的對應表
}

// UnmarshalYAML 實作 yaml.Unmarshaler，支援單一字串或字典兩種 YAML 格式
func (p *pasetoSecretKeyConfig) UnmarshalYAML(value *yaml.Node) error {
	switch value.Kind {
	case yaml.ScalarNode:
		// 舊格式：單一十六進位字串，以時間戳 0 作為預設標記
		p.Keys = map[int64]string{0: value.Value}
		return nil

	case yaml.MappingNode:
		// 新格式：{時間戳: 金鑰字串} 字典
		p.Keys = make(map[int64]string, len(value.Content)/2)
		for i := 0; i < len(value.Content); i += 2 {
			keyNode := value.Content[i]
			valNode := value.Content[i+1]

			var ts int64
			if err := keyNode.Decode(&ts); err != nil {
				return fmt.Errorf("paseto_secret_key 時間戳必須為 UNIX 整數: %v", err)
			}
			p.Keys[ts] = valNode.Value
		}
		return nil

	default:
		return fmt.Errorf("paseto_secret_key 必須為十六進位字串或 {時間戳: 金鑰} 字典")
	}
}

// pasetoKeyEntry 單一解碼後的金鑰條目，包含生效時間戳與金鑰位元組
type pasetoKeyEntry struct {
	Timestamp int64  // UNIX 時間戳，作為金鑰識別碼 (key ID)
	Key       []byte // 解碼後的 32-byte (256-bit) 對稱金鑰
}

// pasetoKeyRing 金鑰環，儲存所有已解碼的 PASETO 對稱金鑰，按時間戳降序排列
// Keys[0] 永遠為最新金鑰，用於簽發新令牌；驗證時依序嘗試所有金鑰
type pasetoKeyRing struct {
	Keys []pasetoKeyEntry // 金鑰列表，按時間戳降序（最新優先）
}

// SigningKey 取得簽發新令牌用的金鑰（即最新金鑰）
// 若金鑰環為空則回傳 nil
func (r *pasetoKeyRing) SigningKey() []byte {
	if len(r.Keys) == 0 {
		return nil
	}
	return r.Keys[0].Key
}

// SigningEntry 取得簽發新令牌用的金鑰條目（含金鑰與 kid 時間戳）
// 若金鑰環為空則回傳 nil
func (r *pasetoKeyRing) SigningEntry() *pasetoKeyEntry {
	if len(r.Keys) == 0 {
		return nil
	}
	return &r.Keys[0]
}

// ToKeyRing 將 YAML 設定中的金鑰字典轉換為已排序的金鑰環
// 每個金鑰均需為 64 個十六進位字元（解碼後 32 bytes），否則回傳錯誤
func (p *pasetoSecretKeyConfig) ToKeyRing() (*pasetoKeyRing, error) {
	if len(p.Keys) == 0 {
		return nil, fmt.Errorf("PASETO 金鑰未設定：paseto_secret_key 為空")
	}

	entries := make([]pasetoKeyEntry, 0, len(p.Keys))
	for ts, hexKey := range p.Keys {
		key, err := hex.DecodeString(hexKey)
		if err != nil || len(key) != 32 {
			return nil, fmt.Errorf("PASETO 金鑰無效 (時間戳 %d): 需要 64 個十六進位字元 (32 bytes)，目前為 %d bytes", ts, len(key))
		}
		entries = append(entries, pasetoKeyEntry{Timestamp: ts, Key: key})
	}

	// 依時間戳降序排列，最新金鑰在最前面
	sort.Slice(entries, func(i, j int) bool {
		return entries[i].Timestamp > entries[j].Timestamp
	})

	return &pasetoKeyRing{Keys: entries}, nil
}

// serviceConfig 服務設定結構
type serviceConfig struct {
	NatsConfig          nyanats.NatsConfig    `json:"nats_config" yaml:"nats_config"`
	NatsSubjects        []string              `json:"nats_subjects" yaml:"nats_subjects"`                 // NATS 訂閱主题列表（每個主题對應一個 HTTP 路徑）
	NatsSubject         string                `json:"nats_subject" yaml:"nats_subject"`                   // 單一訂閱主题（向後相容，已棄用）
	PasetoSecretKey     pasetoSecretKeyConfig `json:"paseto_secret_key" yaml:"paseto_secret_key"`         // PASETO 對稱金鑰設定（支援單一金鑰或金鑰輪替字典）
	PasetoConfig        pasetoConfig          `json:"paseto_config" yaml:"paseto_config"`
	NatsPublish         natsPublishConfig     `json:"nats_publish" yaml:"nats_publish"`                   // 對外發布訊息設定
	TokenClaimsMapping  tokenClaimsMapping    `json:"token_claims_mapping" yaml:"token_claims_mapping"`   // 令牌 claims 與上游資料的欄位映射
}

// getSubjects 合併 nats_subjects 與 nats_subject，回傳完整的主題列表
func (c *serviceConfig) getSubjects() []string {
	if len(c.NatsSubjects) > 0 {
		return c.NatsSubjects
	}
	if c.NatsSubject != "" {
		return []string{c.NatsSubject}
	}
	return nil
}

// pasetoConfig PASETO 令牌參數設定
type pasetoConfig struct {
	Version     string `json:"paseto_version" yaml:"paseto_version"` // PASETO 協定版本 (v1/v2)
	TokenTTL    string `json:"token_ttl" yaml:"token_ttl"`           // 令牌有效時長（預設值），如 "24h"、"2h"、"30m"
	MaxTokenTTL string `json:"max_token_ttl" yaml:"max_token_ttl"`   // 令牌有效時長上限（選填），限制登入請求中的 expires 不得超過此值；未設定時以 token_ttl 為上限
}

// tokenClaimsMapping 令牌 claims 與上游服務回傳資料欄位的對應關係
// 只有 sub、iss、aud 三個 claims 可被上游資料覆蓋
// iat、nbf、exp、jti、kid 為系統自動計算，不可透過上游資料對應
type tokenClaimsMapping struct {
	Sub      string `json:"sub" yaml:"sub"` // sub (Subject) 對應的上游資料欄位名
	Issuer   string `json:"iss" yaml:"iss"` // iss (Issuer) 對應的上游資料欄位名
	Audience string `json:"aud" yaml:"aud"` // aud (Audience) 對應的上游資料欄位名
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
