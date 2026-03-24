#!/usr/bin/env python3
"""UserValidator 測試腳本：發送驗證請求，解析 PASETO 令牌內容
依賴: pip install requests
"""

import json
import os
import re
import sys

import requests
from parse import parse_paseto

DEFAULT_URL = "http://192.168.1.48:9080/auth/login"
VERIFY_URL = "http://192.168.1.48:9080/auth/verify"
# PASETO v2 local 對稱密鑰預設值（與 UserValidator.yaml 一致）
DEFAULT_SECRET_KEY_HEX = ""
CONFIG_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "UserValidator.yaml")


def load_secret_key() -> str:
    """從同目錄的 UserValidator.yaml 讀取 paseto_secret_key，支援舊格式（單一行）與新格式（字典）

    舊格式：paseto_secret_key: "404142..."
    新格式：paseto_secret_key:
              1748736000: "404142..."
              1779840000: "606162..."
    """
    try:
        with open(CONFIG_PATH, "r", encoding="utf-8") as f:
            lines = f.readlines()
    except OSError:
        return DEFAULT_SECRET_KEY_HEX

    active_lines = [line for line in lines if not line.strip().startswith("#")]
    content = "".join(active_lines)

    # 匹配所有 64 字元的十六進位字串（PASETO 金鑰格式）
    keys = re.findall(r'"([0-9a-fA-F]{64})"', content)
    if keys:
        # 若為字典格式則回傳最後出現的金鑰（對應最新時間戳，位於 YAML 區塊結尾）
        return keys[-1]
    return DEFAULT_SECRET_KEY_HEX


def print_token_info(token_str: str, secret_key_hex: str | None = None) -> None:
    """輸出令牌結構概覽與所有 claims（由 parse.py 解析）"""
    result = parse_paseto(token_str, key=secret_key_hex)

    raw = result["raw"]
    vi = result["version_info"]
    alg = result["algorithm"]
    footer = result["footer"]
    payload_info = result["payload"]
    warnings = result.get("warnings", [])

    print("=" * 60)
    print("PASETO 令牌結構")
    print("=" * 60)
    print(f"  版本 (version):   {raw.get('version', '?')}")
    print(f"  用途 (purpose):   {raw.get('purpose', '?')}")
    print(f"  段數 (segments):  {raw.get('segment_count', '?')}")
    print(f"  長度 (length):    {len(token_str)} chars")

    if vi:
        if vi.get("supports_implicit_assertions"):
            print(f"  隱性斷言:         支援")
        if vi.get("note"):
            print(f"  版本備註:         {vi['note']}")

    if alg:
        print(f"  演算法風格:       {alg.get('style', '?')}")
        print(f"  演算法名稱:       {alg.get('name', '?')}")

    if warnings:
        for w in warnings:
            print(f"  [警告] {w}")

    if footer.get("present"):
        print(f"  Footer:           有")
        parsed = footer.get("parsed")
        if parsed is not None:
            if isinstance(parsed, dict):
                print(f"  Footer 內容:      {json.dumps(parsed, ensure_ascii=False)}")
            else:
                print(f"  Footer 內容:      {parsed}")
        if footer.get("note"):
            print(f"  Footer 備註:      {footer['note']}")
    else:
        print(f"  Footer:           無")

    print()
    print("=" * 60)
    print("令牌宣告 (Claims)")
    print("=" * 60)

    if payload_info.get("note"):
        print(f"  [備註] {payload_info['note']}")

    standard_claims = payload_info.get("standard_claims")
    if standard_claims:
        print()
        print("  標準宣告 (Standard Claims):")
        for key, info in standard_claims.items():
            label = info.get("label", key)
            value = info.get("value")
            formatted = info.get("formatted")
            parsed_val = info.get("parsed")

            if value is None and not payload_info.get("decodable"):
                print(f"    {label}: (已加密)")
                continue

            display = str(value) if value is not None else "(無)"
            if formatted:
                display = f"{value} → {formatted}"
            print(f"    {label}: {display}")

            if parsed_val is not None and parsed_val != value:
                if isinstance(parsed_val, (dict, list)):
                    print(f"      (解析: {json.dumps(parsed_val, ensure_ascii=False)})")
                else:
                    print(f"      (解析: {parsed_val})")

    custom_claims = payload_info.get("custom_claims")
    if custom_claims:
        print()
        print("  自定義宣告 (Custom Claims):")
        for k, v in custom_claims.items():
            if isinstance(v, (dict, list)):
                print(f"    {k}: {json.dumps(v, ensure_ascii=False)}")
            else:
                print(f"    {k}: {v}")


def main() -> None:
    url = sys.argv[1] if len(sys.argv) > 1 else DEFAULT_URL
    verify_url = sys.argv[2] if len(sys.argv) > 2 else VERIFY_URL
    secret_key_hex = load_secret_key()

    payload = {
        "username": "admin",
        "password": "123456",
        "app": "login-verify-test",
    }

    print("=" * 60)
    print("請求資訊")
    print("=" * 60)
    print(f"  URL:     {url}")
    print(f"  Body:    {json.dumps(payload)}")
    print()

    try:
        resp = requests.post(url, json=payload, timeout=10)
    except requests.ConnectionError:
        print(f"[錯誤] 無法連線到 {url}，請確認 UserValidator 服務已啟動")
        sys.exit(1)

    print("=" * 60)
    print("原始回應")
    print("=" * 60)
    print(f"  HTTP Status: {resp.status_code} {resp.reason}")
    print(f"  Headers:")
    for k, v in resp.headers.items():
        print(f"    {k}: {v}")
    print()
    print(resp.text)

    token = None
    try:
        data = resp.json()
        print()
        print("=" * 60)
        print("格式化回應 (JSON)")
        print("=" * 60)
        print(json.dumps(data, indent=2, ensure_ascii=False))

        token = data.get("token", "")
        if token:
            print()
            print_token_info(token, secret_key_hex)
        elif data.get("success") and (data.get("sub") or data.get("username")):
            # 登入回應直接回傳 claims（無 token 欄位），直接顯示使用者資訊
            print()
            print("=" * 60)
            print("登入成功 (Login Success)")
            print("=" * 60)
            print(f"  使用者名稱 (sub):    {data.get('sub', '(無)')}")
            print(f"  簽發時間 (iat):      {data.get('iat', '(無)')}")
            print(f"  到期時間 (exp):      {data.get('exp', '(無)')}")
            print()
            print("[備註] 未取得 token 欄位，略過後續核實測試")
            sys.exit(0)

    except json.JSONDecodeError:
        print()
        print("[備註] 回應並非 JSON 格式，略過結構化輸出")

    print()

    # --------------------------------------------------------------------
    # 令牌核實測試 (/auth/verify)
    # --------------------------------------------------------------------
    if not token:
        print("[略過] 未取得令牌，無法進行核實測試")
        return

    print("=" * 60)
    print("令牌核實請求 (/auth/verify)")
    print("=" * 60)
    print(f"  URL:     {verify_url}")
    verify_payload = {"token": token}
    print(f"  Body:    {json.dumps(verify_payload, ensure_ascii=False)}")
    print()

    try:
        verify_resp = requests.post(verify_url, json=verify_payload, timeout=10)
    except requests.ConnectionError:
        print(f"[錯誤] 無法連線到 {verify_url}，請確認 UserValidator 服務已啟動")
        return

    print("=" * 60)
    print("核實回應")
    print("=" * 60)
    print(f"  HTTP Status: {verify_resp.status_code} {verify_resp.reason}")
    print(f"  Headers:")
    for k, v in verify_resp.headers.items():
        print(f"    {k}: {v}")
    print()
    print(verify_resp.text)

    try:
        verify_data = verify_resp.json()
        print()
        print("=" * 60)
        print("格式化回應 (JSON)")
        print("=" * 60)
        print(json.dumps(verify_data, indent=2, ensure_ascii=False))

        if verify_data.get("success"):
            print()
            print("令牌核實成功:")
            print(f"  主體 (sub):      {verify_data.get('sub', '(無)')}")
            print(f"  使用者 (username): {verify_data.get('username', '(無)')}")
            print(f"  應用 (aud/app):   {verify_data.get('app', '(無)')}")
            print(f"  簽發時間 (iat):   {verify_data.get('iat', '(無)')}")
            print(f"  到期時間 (exp):   {verify_data.get('exp', '(無)')}")
        else:
            print()
            print(f"令牌核實失敗: {verify_data.get('message', '(無)')}")

    except json.JSONDecodeError:
        print()
        print("[備註] 回應並非 JSON 格式，略過結構化輸出")

    print()


if __name__ == "__main__":
    main()
