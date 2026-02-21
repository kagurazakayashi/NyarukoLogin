"""
Yashi PASETO 令牌解析庫
===============

提供 PASETO（Platform-Agnostic SEcurity TOkens）令牌的結構解析功能，
對 public 令牌解碼其載荷宣告，對 local 令牌支援可選金鑰解密。

用法
----
::

    from PASETO內容構成庫 import parse_paseto

    result = parse_paseto("v2.public.eyJleHAiOi...")
    print(result["version_info"])   # 訪問版本詳情
    print(result["algorithm"])      # 訪問演算法資訊
    print(result["payload"])        # 訪問載荷內容

    # 對 local 令牌傳入金鑰以解密：
    result = parse_paseto("v2.local.xxx", key=bytes.fromhex("7071...8f"))

返回結構
--------

``parse_paseto()`` 返回一個字典，包含以下頂層鍵：:

    {
        "valid": bool,           # 令牌是否透過基礎校驗
        "raw": {                 # 原始段資訊
            "version": str,      # 版本字串，如 "v2"
            "purpose": str,      # 用途字串，如 "public" / "local"
            "payload_raw": str,  # payload 段的原始 Base64url 字串
            "footer_raw": str|None,  # footer 段的原始 Base64url 字串
            "segment_count": int,    # 段數（3 或 4）
        },
        "version_info": {        # 版本詳情
            "version": str,
            "supports_implicit_assertions": bool,   # v3/v4 支援隱性斷言
            "note": str,
        },
        "algorithm": {           # 演算法資訊
            "style": str,        # "NIST 相容" 或 "現代"
            "name": str,         # 演算法名稱
            "version": str,
            "purpose": str,
        },
        "payload": {             # 載荷解析結果
            "decodable": bool,   # 是否可解碼（public=True, local=有金鑰）
            "note": str|None,    # 補充說明
            "claims": dict|None,      # 全部原始宣告
            "standard_claims": dict,  # 標準宣告（始終 7 項）
            "custom_claims": dict|None,   # 自定義宣告
        },
        "footer": {              # Footer 解析結果
            "present": bool,
            "raw": str|None,
            "parsed": dict|str|None,
            "note": str|None,
        },
        "warnings": list[str],   # 異常提示列表
    }

注意事項
--------

- 金鑰須為 32 位元組（256 位）的對稱金鑰，所有版本共用同一金鑰。
- v3/v4 的隱性斷言（implicit_assertion）須在呼叫時透過引數傳入。
- 本庫不執行 public 令牌的簽名驗證（需要非對稱公鑰，不在 scope 內）。
"""

import base64
import hashlib
import hmac
import json
import struct
from datetime import datetime, timezone
from typing import Any

# ──────────────────────────────────────────────────────────────────
# 可選密碼學依賴
# ──────────────────────────────────────────────────────────────────

try:
    from nacl.bindings import crypto_aead_xchacha20poly1305_ietf_decrypt
    from nacl.exceptions import CryptoError as _NaclCryptoError
    _NACL_AVAILABLE = True
except ImportError:
    _NACL_AVAILABLE = False
    _NaclCryptoError = Exception

try:
    from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
    from cryptography.hazmat.primitives import hashes
    from cryptography.hazmat.primitives.kdf.hkdf import HKDF
    _CRYPTOGRAPHY_AVAILABLE = True
except ImportError:
    _CRYPTOGRAPHY_AVAILABLE = False

# ──────────────────────────────────────────────────────────────────
# 模組級常量
# ──────────────────────────────────────────────────────────────────

_VALID_VERSIONS = frozenset({"v1", "v2", "v3", "v4"})
_VALID_PURPOSES = frozenset({"local", "public"})

_ALGORITHMS = {
    "v1": {
        "local":  {"style": "NIST 相容", "name": "AES-256-CTR + HMAC-SHA384"},
        "public": {"style": "NIST 相容", "name": "RSASSA-PSS 2048 + SHA384"},
    },
    "v2": {
        "local":  {"style": "現代",      "name": "XChaCha20-Poly1305"},
        "public": {"style": "現代",      "name": "Ed25519"},
    },
    "v3": {
        "local":  {"style": "NIST 相容", "name": "AES-256-CTR + HMAC-SHA384"},
        "public": {"style": "NIST 相容", "name": "ECDSA NIST P-384 + SHA384"},
    },
    "v4": {
        "local":  {"style": "現代",      "name": "XChaCha20-Poly1305"},
        "public": {"style": "現代",      "name": "Ed25519"},
    },
}

_STANDARD_CLAIMS = frozenset({"iss", "sub", "aud", "exp", "nbf", "iat", "jti"})

_CLAIM_LABELS = {
    "iss": "簽發者 (Issuer)",
    "sub": "主體 (Subject)",
    "aud": "接收方 (Audience)",
    "exp": "過期時間 (Expiration)",
    "nbf": "生效時間 (Not Before)",
    "iat": "簽發時間 (Issued At)",
    "jti": "令牌唯一 ID (JWT ID)",
}

# ──────────────────────────────────────────────────────────────────
# 內部工具函式
# ──────────────────────────────────────────────────────────────────


def _b64url_decode(data: str) -> bytes:
    """Base64url 解碼，自動補齊填充符。"""
    padding = 4 - len(data) % 4
    if padding != 4:
        data += "=" * padding
    return base64.urlsafe_b64decode(data)


def _try_parse_json(raw: bytes) -> Any | None:
    """嘗試將位元組串解析為 JSON 物件，失敗返回 None。"""
    try:
        return json.loads(raw)
    except (json.JSONDecodeError, UnicodeDecodeError):
        return None


def _try_deep_parse_value(value: Any) -> Any:
    """若值為 JSON 格式字串，嘗試解析為 Python 物件；否則原樣返回。"""
    if isinstance(value, str):
        stripped = value.strip()
        if (stripped.startswith("{") and stripped.endswith("}")) or \
           (stripped.startswith("[") and stripped.endswith("]")):
            try:
                return json.loads(stripped)
            except (json.JSONDecodeError, ValueError):
                pass
    return value


def _try_format_datetime(claims: dict, key: str) -> str | None:
    """嘗試將宣告中的 ISO 8601 時間字串轉換為本地時間格式。"""
    value = claims.get(key)
    if not isinstance(value, str):
        return None
    try:
        dt = datetime.fromisoformat(value)
        if dt.tzinfo is None:
            dt = dt.replace(tzinfo=timezone.utc)
        local_dt = dt.astimezone()
        return local_dt.strftime("%Y-%m-%d %H:%M:%S %Z")
    except (ValueError, OverflowError):
        return value


# ──────────────────────────────────────────────────────────────────
# PAE（Pre-Authentication Encoding）
# ──────────────────────────────────────────────────────────────────


def _pae(pieces: list[bytes]) -> bytes:
    """PASETO 規範的預認證編碼（Pre-Authentication Encoding）。

    將多個位元組陣列按 LE64(長度) + 資料的格式串聯，防止標準攻擊。

    Args:
        pieces: 待編碼的位元組陣列列表

    Returns:
        編碼後的連線位元組串
    """
    output = struct.pack("<Q", len(pieces))
    for piece in pieces:
        output += struct.pack("<Q", len(piece))
        output += piece
    return output


# ──────────────────────────────────────────────────────────────────
# 各版本 local 令牌解密實現
# ──────────────────────────────────────────────────────────────────


def _decrypt_v1_local(key: bytes, n: bytes, c: bytes, t: bytes,
                      footer: bytes) -> bytes:
    """v1.local 解密：AES-256-CTR + HMAC-SHA384，金鑰透過 HKDF 派生。

    Args:
        key: 原始 32 位元組對稱金鑰
        n: 從令牌中提取的 32 位元組 nonce
        c: 密文
        t: 48 位元組 HMAC-SHA384 認證標籤
        footer: footer 原始位元組（可能為空）

    Returns:
        解密後的明文載荷位元組

    Raises:
        ValueError: HMAC 驗證失敗
    """
    if not _CRYPTOGRAPHY_AVAILABLE:
        raise ImportError("v1 解密需要 cryptography 庫: pip install cryptography")

    ek = HKDF(
        algorithm=hashes.SHA384(), length=32,
        salt=n[:16], info=b"paseto-encryption-key",
    ).derive(key)
    ak = HKDF(
        algorithm=hashes.SHA384(), length=32,
        salt=n[:16], info=b"paseto-auth-key-for-aead",
    ).derive(key)

    h = b"v1.local."
    pre_auth = _pae([h, n, c, footer])
    expected_t = hmac.new(ak, pre_auth, hashlib.sha384).digest()
    if not hmac.compare_digest(t, expected_t):
        raise ValueError("v1.local HMAC 驗證失敗，金鑰或令牌不匹配")

    decryptor = Cipher(algorithms.AES(ek), modes.CTR(n[16:])).decryptor()
    return decryptor.update(c) + decryptor.finalize()


def _decrypt_v2_local(key: bytes, n: bytes, c: bytes,
                      footer: bytes) -> bytes:
    """v2.local 解密：XChaCha20-Poly1305。

    Args:
        key: 原始 32 位元組對稱金鑰
        n: 從令牌中提取的 24 位元組 nonce
        c: 密文 + Poly1305 標籤（16 位元組）
        footer: footer 原始位元組（可能為空）

    Returns:
        解密後的明文載荷位元組

    Raises:
        ImportError: PyNaCl 不可用
        ValueError: 認證失敗
    """
    if not _NACL_AVAILABLE:
        raise ImportError("v2 解密需要 PyNaCl 庫: pip install pynacl")

    h = b"v2.local."
    aad = _pae([h, n, footer])
    return crypto_aead_xchacha20poly1305_ietf_decrypt(c, aad, n, key)


def _decrypt_v3_local(key: bytes, n: bytes, c: bytes, t: bytes,
                      footer: bytes, implicit: bytes) -> bytes:
    """v3.local 解密：AES-256-CTR + HMAC-SHA384 + HKDF（含隱性斷言）。

    Args:
        key: 原始 32 位元組對稱金鑰
        n: 從令牌中提取的 32 位元組 nonce
        c: 密文
        t: 48 位元組 HMAC-SHA384 認證標籤
        footer: footer 原始位元組（可能為空）
        implicit: 隱性斷言原始位元組

    Returns:
        解密後的明文載荷位元組

    Raises:
        ValueError: HMAC 驗證失敗
    """
    if not _CRYPTOGRAPHY_AVAILABLE:
        raise ImportError("v3 解密需要 cryptography 庫: pip install cryptography")

    e = HKDF(
        algorithm=hashes.SHA384(), length=48, salt=None,
        info=b"paseto-encryption-key" + n,
    )
    a = HKDF(
        algorithm=hashes.SHA384(), length=48, salt=None,
        info=b"paseto-auth-key-for-aead" + n,
    )
    tmp = e.derive(key)
    ek = tmp[:32]
    n2 = tmp[32:]
    ak = a.derive(key)

    h = b"v3.local."
    pre_auth = _pae([h, n, c, footer, implicit])
    expected_t = hmac.new(ak, pre_auth, hashlib.sha384).digest()
    if not hmac.compare_digest(t, expected_t):
        raise ValueError("v3.local HMAC 驗證失敗，金鑰、令牌或隱性斷言不匹配")

    decryptor = Cipher(algorithms.AES(ek), modes.CTR(n2)).decryptor()
    return decryptor.update(c) + decryptor.finalize()


def _decrypt_v4_local(key: bytes, n: bytes, c: bytes,
                      footer: bytes, implicit: bytes) -> bytes:
    """v4.local 解密：XChaCha20-Poly1305（含隱性斷言）。

    Args:
        key: 原始 32 位元組對稱金鑰
        n: 從令牌中提取的 24 位元組 nonce
        c: 密文 + Poly1305 標籤（16 位元組）
        footer: footer 原始位元組（可能為空）
        implicit: 隱性斷言原始位元組

    Returns:
        解密後的明文載荷位元組

    Raises:
        ImportError: PyNaCl 不可用
        ValueError: 認證失敗
    """
    if not _NACL_AVAILABLE:
        raise ImportError("v4 解密需要 PyNaCl 庫: pip install pynacl")

    h = b"v4.local."
    aad = _pae([h, n, footer, implicit])
    return crypto_aead_xchacha20poly1305_ietf_decrypt(c, aad, n, key)


def _decrypt_local(version: str, key: bytes, payload_bytes: bytes,
                   footer_bytes: bytes, implicit: bytes) -> bytes:
    """根據版本排程到對應的解密函式。

    Args:
        version: 版本字串（v1/v2/v3/v4）
        key: 32 位元組對稱金鑰
        payload_bytes: Base64url 解碼後的 payload 位元組
        footer_bytes: footer 原始位元組
        implicit: 隱性斷言原始位元組

    Returns:
        解密後的明文載荷位元組

    Raises:
        ValueError: 解密失敗或庫不可用
    """
    if version == "v1":
        n = payload_bytes[:32]
        c = payload_bytes[32:-48]
        t = payload_bytes[-48:]
        return _decrypt_v1_local(key, n, c, t, footer_bytes)
    elif version == "v2":
        n = payload_bytes[:24]
        c = payload_bytes[24:]
        return _decrypt_v2_local(key, n, c, footer_bytes)
    elif version == "v3":
        n = payload_bytes[:32]
        c = payload_bytes[32:-48]
        t = payload_bytes[-48:]
        return _decrypt_v3_local(key, n, c, t, footer_bytes, implicit)
    elif version == "v4":
        n = payload_bytes[:24]
        c = payload_bytes[24:]
        return _decrypt_v4_local(key, n, c, footer_bytes, implicit)
    else:
        raise ValueError(f"不支援的版本: {version}")


# ──────────────────────────────────────────────────────────────────
# Payload 宣告解析（public 令牌及解密後的 local 令牌共用）
# ──────────────────────────────────────────────────────────────────


def _build_claims_from_json(payload_bytes: bytes) -> dict:
    """從 JSON 位元組構建標準/自定義宣告字典。

    Returns:
        與 parse_paseto 中 payload 欄位格式一致的字典
    """
    claims = _try_parse_json(payload_bytes)
    if claims is None:
        return {
            "decodable": True,
            "note": "payload 已解碼但非有效 JSON",
            "claims": None,
            "standard_claims": None,
            "custom_claims": None,
        }

    standard = {}
    custom = {}
    for k, v in claims.items():
        if k in _STANDARD_CLAIMS:
            standard[k] = v
        else:
            custom[k] = v

    standard_parsed = {}
    for claim_key in _STANDARD_CLAIMS:
        label = _CLAIM_LABELS.get(claim_key, claim_key)
        if claim_key in standard:
            raw_value = standard[claim_key]
            parsed_value = _try_deep_parse_value(raw_value)
            entry = {"label": label, "value": raw_value, "parsed": parsed_value}
            if claim_key in ("exp", "nbf", "iat"):
                formatted = _try_format_datetime(standard, claim_key)
                if formatted:
                    entry["formatted"] = formatted
        else:
            entry = {"label": label, "value": None, "parsed": None}
        standard_parsed[claim_key] = entry

    custom_parsed = {}
    for k, v in custom.items():
        custom_parsed[k] = _try_deep_parse_value(v)

    return {
        "decodable": True,
        "note": None,
        "claims": claims,
        "standard_claims": standard_parsed,
        "custom_claims": custom_parsed if custom_parsed else None,
    }


def _build_empty_claims() -> dict:
    """構建全 None 的標準宣告字典（用於不可解碼的場景）。"""
    standard_parsed = {}
    for claim_key in _STANDARD_CLAIMS:
        label = _CLAIM_LABELS.get(claim_key, claim_key)
        standard_parsed[claim_key] = {"label": label, "value": None, "parsed": None}
    return {
        "decodable": False,
        "note": None,
        "claims": None,
        "standard_claims": standard_parsed,
        "custom_claims": None,
    }


# ──────────────────────────────────────────────────────────────────
# 核心解析函式
# ──────────────────────────────────────────────────────────────────


def parse_paseto(token: str, key: bytes | str | None = None,
                 implicit_assertion: bytes | str | None = None) -> dict:
    """解析 PASETO 令牌字串，返回結構化字典。

    這是本庫唯一的公開介面。接收一個 PASETO 令牌，按 ``version.purpose.payload[.footer]``
    格式拆解後逐段解析。

    - public 令牌：自動 Base64url 解碼 payload 中的 JSON 宣告。
    - local 令牌：若提供 key，嘗試解密後解析 JSON 宣告。

    Args:
        token: PASETO 令牌字串，如 ``"v2.public.eyJleHAiOi..."``
        key: 可選，32 位元組對稱金鑰（用於 local 令牌解密）。
             可傳入 bytes 或 hex 字串。
        implicit_assertion: 可選，v3/v4 的隱性斷言，
                            bytes 或 JSON 可序列化的物件。

    Returns:
        結構化字典，詳見模組文件中的"返回結構"章節。

    Example:
        >>> result = parse_paseto("v2.public.eyJpc3MiOiAiaHR0cHM6Ly9hdXRoLmV4YW1wbGUuY29tIn0")
        >>> result["version_info"]["version"]
        'v2'
        >>> result["algorithm"]["name"]
        'Ed25519'

        >>> # 解密 local 令牌
        >>> key = bytes.fromhex("707172737475767778797a7b7c7d7e7f808182838485868788898a8b8c8d8e8f")
        >>> r = parse_paseto("v2.local.97TTO...", key=key)
        >>> r["payload"]["claims"]
        {'data': 'this is a signed message', 'exp': '2019-01-01T00:00:00+00:00'}
    """
    # ─── 引數標準化 ───
    if isinstance(key, str):
        key = bytes.fromhex(key)
    if isinstance(implicit_assertion, (dict, list)):
        implicit_assertion = json.dumps(
            implicit_assertion, ensure_ascii=False, separators=(",", ":")
        )
    if isinstance(implicit_assertion, str):
        implicit_assertion = implicit_assertion.encode("utf-8")
    if implicit_assertion is None:
        implicit_assertion = b""

    if key is not None and not isinstance(key, bytes):
        raise TypeError("key 必須為 bytes 或 hex 字串")
    if key is not None and len(key) != 32:
        raise ValueError(f"金鑰長度必須為 32 位元組，當前 {len(key)} 位元組")

    # ─── 初始化返回結構 ───
    result: dict = {
        "raw": {},
        "version_info": {},
        "algorithm": {},
        "payload": {},
        "footer": {},
        "warnings": [],
    }

    token = token.strip()

    # ─── 第 1 步：按 "." 拆分令牌段 ───
    parts = token.split(".")
    if len(parts) < 3 or len(parts) > 4:
        result["warnings"].append(
            f"段數異常（期望 3 或 4，實際 {len(parts)}），非標準 PASETO 令牌"
        )
        if len(parts) < 3:
            return result

    version = parts[0]
    purpose = parts[1]
    payload_raw = parts[2]
    footer_raw = parts[3] if len(parts) == 4 else None

    # ─── 第 2 步：記錄原始段資訊 ───
    result["raw"] = {
        "version": version,
        "purpose": purpose,
        "payload_raw": payload_raw,
        "footer_raw": footer_raw,
        "segment_count": len(parts),
    }

    # ─── 第 3 步：校驗版本號 ───
    if version not in _VALID_VERSIONS:
        result["warnings"].append(
            f"未知版本 '{version}'，合法值為 {sorted(_VALID_VERSIONS)}"
        )

    # ─── 第 4 步：校驗用途 ───
    if purpose not in _VALID_PURPOSES:
        result["warnings"].append(
            f"未知用途 '{purpose}'，合法值為 {sorted(_VALID_PURPOSES)}"
        )

    # ─── 第 5 步：構建版本資訊 ───
    if version in _VALID_VERSIONS:
        result["version_info"] = {
            "version": version,
            "supports_implicit_assertions": version in ("v3", "v4"),
            "note": (
                "支援隱性斷言（implicit assertions），不在令牌字串中，由通訊雙方預先約定"
                if version in ("v3", "v4")
                else "不支援隱性斷言"
            ),
        }

    # ─── 第 6 步：查表確定演算法 ───
    if version in _VALID_VERSIONS and purpose in _VALID_PURPOSES:
        result["algorithm"] = dict(_ALGORITHMS[version][purpose])
        result["algorithm"]["version"] = version
        result["algorithm"]["purpose"] = purpose

    # ─── 第 7 步：解析 footer 原始位元組 ───
    footer_bytes = b""
    if footer_raw:
        try:
            footer_bytes = _b64url_decode(footer_raw)
        except Exception as e:
            result["warnings"].append(f"footer Base64url 解碼失敗: {e}")

    # ─── 第 8 步：解析 payload ───
    if purpose == "local":
        if key is not None:
            # ─── 嘗試解密 local 令牌 ───
            payload_bytes_raw = None
            try:
                payload_bytes_raw = _b64url_decode(payload_raw)
            except Exception as e:
                result["warnings"].append(f"payload Base64url 解碼失敗: {e}")

            if payload_bytes_raw is not None:
                try:
                    plaintext = _decrypt_local(
                        version, key, payload_bytes_raw,
                        footer_bytes, implicit_assertion,
                    )
                except ImportError as e:
                    result["warnings"].append(f"解密庫不可用: {e}")
                    result["payload"] = _build_empty_claims()
                    result["payload"]["note"] = f"缺少密碼學庫: {e}"
                except (_NaclCryptoError, ValueError) as e:
                    result["warnings"].append(f"local 令牌解密失敗: {e}")
                    result["payload"] = _build_empty_claims()
                    result["payload"]["note"] = "解密失敗，金鑰或令牌不匹配"
                else:
                    result["payload"] = _build_claims_from_json(plaintext)
                    result["payload"]["note"] = "已使用金鑰解密"
        else:
            # ─── 無金鑰，不可解碼 ───
            result["payload"] = _build_empty_claims()
            result["payload"]["note"] = (
                "local 令牌的 payload 為密文，需提供 32 位元組金鑰解密後才能檢視內容"
            )
    else:
        # ─── public 令牌：Base64url 解碼 JSON ───
        payload_bytes = None
        try:
            payload_bytes = _b64url_decode(payload_raw)
        except Exception as e:
            result["warnings"].append(f"payload Base64url 解碼失敗: {e}")

        if payload_bytes is not None:
            result["payload"] = _build_claims_from_json(payload_bytes)
        else:
            result["payload"] = _build_empty_claims()
            result["payload"]["note"] = "payload 解碼失敗"

    # ─── 第 9 步：解析 footer（結構化展示） ───
    if footer_raw and footer_bytes:
        footer_parsed = _try_parse_json(footer_bytes)
        if footer_parsed is not None:
            result["footer"] = {
                "present": True,
                "raw": footer_raw,
                "parsed": footer_parsed,
                "note": (
                    "public 令牌的 footer 已被簽名覆蓋（不可篡改）"
                    if purpose == "public"
                    else "local 令牌的 footer 為明文，不加密"
                ),
            }
        else:
            result["footer"] = {
                "present": True,
                "raw": footer_raw,
                "parsed": footer_bytes.decode("utf-8", errors="replace"),
                "note": "footer 非 JSON 格式",
            }
    else:
        result["footer"] = {
            "present": False,
            "raw": None,
            "parsed": None,
        }

    # ─── 第 10 步：最終有效性判定 ───
    if result["warnings"]:
        result["valid"] = False
    elif version in _VALID_VERSIONS and purpose in _VALID_PURPOSES:
        result["valid"] = True
    else:
        result["valid"] = False

    return result
