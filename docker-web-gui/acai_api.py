"""
Requests a la API de Acai, auth tokens y conversión PHP/Twig.
"""

import base64
import json
import os
import re
import tempfile
import time
import urllib.error
import urllib.request

from config import (
    ACAI_AUTH_URL,
    _get_ssl_context,
    keychain_get,
    load_config,
)


def acai_request(auth_header):
    """Hace POST a la API de auth de Acai Code y devuelve el JSON de respuesta."""
    req = urllib.request.Request(
        ACAI_AUTH_URL,
        data=b"",
        headers={
            "Authorization": auth_header,
            "Content-Type": "application/json",
            "Content-Length": "0",
        },
        method="POST",
    )
    ctx = _get_ssl_context()
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=15) as resp:
            return json.loads(resp.read().decode())
    except urllib.error.HTTPError as e:
        body = e.read().decode()
        try:
            return json.loads(body)
        except json.JSONDecodeError:
            return {"success": False, "error": "HTTP {}: {}".format(e.code, body[:200])}
    except Exception as e:
        return {"success": False, "error": str(e)}


def _parse_php_json(raw):
    """Parse JSON from PHP response, tolerating leading warnings/notices."""
    try:
        return json.loads(raw)
    except json.JSONDecodeError:
        # PHP may prepend warnings before JSON — find first { or [
        for i, ch in enumerate(raw):
            if ch in ('{', '['):
                return json.loads(raw[i:])
        raise


def acai_web_request(domain, ssl_enabled, payload, timeout=30):
    """Makes a POST request to a remote Acai CMS viewer_functions.php endpoint."""
    scheme = "https" if ssl_enabled else "http"
    url = "{}://{}/cms/lib/viewer_functions.php".format(scheme, domain)
    data = json.dumps(payload).encode()
    req = urllib.request.Request(
        url,
        data=data,
        headers={
            "Content-Type": "application/json",
            "Content-Length": str(len(data)),
        },
        method="POST",
    )
    ctx = _get_ssl_context()
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=timeout) as resp:
            raw = resp.read().decode()
            return _parse_php_json(raw)
    except urllib.error.HTTPError as e:
        body = e.read().decode()
        try:
            return _parse_php_json(body)
        except (json.JSONDecodeError, ValueError):
            return {"error": "HTTP {}: {}".format(e.code, body[:200])}
    except Exception as e:
        return {"error": str(e)}


def acai_web_request_zip(domain, ssl_enabled, payload, timeout=120):
    """Download ZIP from Acai endpoint, return temp file path."""
    scheme = "https" if ssl_enabled else "http"
    url = "{}://{}/cms/lib/viewer_functions.php".format(scheme, domain)
    data = json.dumps(payload).encode()
    req = urllib.request.Request(
        url,
        data=data,
        headers={
            "Content-Type": "application/json",
            "Content-Length": str(len(data)),
        },
        method="POST",
    )
    ctx = _get_ssl_context()
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=timeout) as resp:
            content_type = resp.getheader("Content-Type", "")
            body = resp.read()
            # If server returned JSON instead of ZIP, it's an error
            if "application/json" in content_type:
                try:
                    err = json.loads(body.decode())
                    return {"error": err.get("error", err.get("message", "Unknown error"))}
                except json.JSONDecodeError:
                    return {"error": "Unexpected JSON response"}
            # Save binary to temp file
            fd, tmp_path = tempfile.mkstemp(suffix=".zip", prefix="acai_pack_")
            try:
                os.write(fd, body)
            finally:
                os.close(fd)
            return {"path": tmp_path}
    except urllib.error.HTTPError as e:
        body = e.read().decode()
        try:
            err = json.loads(body)
            return {"error": err.get("error", "HTTP {}".format(e.code))}
        except json.JSONDecodeError:
            return {"error": "HTTP {}: {}".format(e.code, body[:200])}
    except Exception as e:
        return {"error": str(e)}


def _php_block_to_twig(php_code):
    """Convert a decoded PHP block to its Twig equivalent."""
    php = php_code.strip()
    # t_var(t($var,'field')) → {{ var.field | translate }}
    m = re.match(r'<\?(?:php)?\s+echo\s+t_var\(t\(\$(\w+),\s*[\'"]([^\'"]+)[\'"]\)\);\s*\?>', php)
    if m:
        return "{{ " + m.group(1) + "." + m.group(2) + " | translate }}"
    # func(t($var,'field')) → {{ var.field | func }}
    m = re.match(r'<\?(?:php)?\s+echo\s+(\w+)\(t\(\$(\w+),\s*[\'"]([^\'"]+)[\'"]\)\);\s*\?>', php)
    if m:
        return "{{ " + m.group(2) + "." + m.group(3) + " | " + m.group(1) + " }}"
    # t($var,'field') → {{ var.field }}
    m = re.match(r'<\?(?:php)?\s+echo\s+t\(\$(\w+),\s*[\'"]([^\'"]+)[\'"]\);\s*\?>', php)
    if m:
        return "{{ " + m.group(1) + "." + m.group(2) + " }}"
    # t_var('text') → {{ 'text' | translate }}
    m = re.match(r'<\?(?:php)?\s+echo\s+t_var\([\'"](.+?)[\'"]\);\s*\?>', php)
    if m:
        return "{{ '" + m.group(1) + "' | translate }}"
    # func($var) → {{ var | func }}
    m = re.match(r'<\?(?:php)?\s+echo\s+(\w+)\(\$(\w+)\);\s*\?>', php)
    if m:
        return "{{ " + m.group(2) + " | " + m.group(1) + " }}"
    # $var → {{ var }}
    m = re.match(r'<\?(?:php)?\s+echo\s+\$(\w+);\s*\?>', php)
    if m:
        return "{{ " + m.group(1) + " }}"
    # BuilderModule('name',[...]) → <name></name>
    m = re.match(r'<\?(?:php)?\s+echo\s+BuilderModule\([\'"](\w+)[\'"],\s*\[.*?\]\);\s*\?>', php)
    if m:
        return "<" + m.group(1) + "></" + m.group(1) + ">"
    # Fallback: return decoded PHP as-is
    return php


def _convert_real_to_twig(content):
    """Replace |*base64*| blocks in real_header/real_footer with Twig equivalents."""
    def _replace(match):
        try:
            decoded = base64.b64decode(match.group(1)).decode("utf-8")
            return _php_block_to_twig(decoded)
        except Exception:
            return match.group(0)
    return re.sub(r'\|\*([A-Za-z0-9+/=]+)\*\|', _replace, content)


def save_hooks_from_api(hooks_data, hooks_dir):
    """Save hooks from API response as .php files in hooks/ directory."""
    os.makedirs(hooks_dir, exist_ok=True)
    count = 0
    for hook in hooks_data:
        endpoint = hook.get("endPoint", "")
        code = hook.get("code", "")
        if not endpoint or not code or code == "code_hidden_for_security":
            continue
        # /api/search/ -> api.search.php
        parts = [p for p in endpoint.strip("/").split("/") if p]
        if not parts:
            continue
        filename = ".".join(parts) + ".php"
        # |*<base64>*| -> raw PHP
        if code.startswith("|*") and code.endswith("*|"):
            code = code[2:-2]
        try:
            decoded = base64.b64decode(code).decode("utf-8", errors="replace")
        except Exception:
            continue
        filepath = os.path.join(hooks_dir, filename)
        with open(filepath, "w", encoding="utf-8") as f:
            f.write(decoded)
        count += 1
    return count


def refresh_acai_token(acai_file):
    """Re-authenticate with Acai and update token in .acai file.

    Uses stored credentials (config username + Keychain password) and the
    domain info already present in the .acai marker to obtain a fresh token.
    Returns (token, error_msg).
    """
    try:
        with open(str(acai_file), "r", encoding="utf-8") as f:
            acai_data = json.load(f)
    except Exception as e:
        return "", "Error leyendo .acai: {}".format(e)

    domain_name = acai_data.get("domain", "")
    if not domain_name:
        return "", "Sin dominio en .acai"

    config = load_config()
    username = config.get("acai_username", "")
    password = keychain_get("acai")
    if not username or not password:
        return acai_data.get("token", ""), ""  # No credentials, keep existing token

    # Step 1: SimpleAuth
    creds = base64.b64encode("{}:{}".format(username, password).encode()).decode()
    result = acai_request("SimpleAuth {}".format(creds))
    if not result.get("success") and not result.get("data"):
        return "", "Auth fallido: {}".format(result.get("error", ""))

    data = result.get("data", {})
    session_hash = data.get("hash", "")
    domains = data.get("domains", [])

    # Find matching domain num
    domain_num = ""
    for d in domains:
        if isinstance(d, dict) and d.get("domain") == domain_name:
            domain_num = str(d.get("num", ""))
            break

    if not domain_num:
        return "", "Dominio '{}' no encontrado en la cuenta".format(domain_name)

    # Step 2: Login with domain
    creds2 = base64.b64encode("{}:{}:{}".format(
        username, session_hash, domain_num
    ).encode()).decode()
    result2 = acai_request("Login {}".format(creds2))
    if not result2.get("success") and not result2.get("data"):
        return "", "Login fallido: {}".format(result2.get("error", ""))

    data2 = result2.get("data", {})
    token = data2.get("token", data2.get("renewToken", ""))
    token_hash = data2.get("tokenHash", "")

    if not token:
        return "", "No se obtuvo token"

    # Update .acai file
    acai_data["token"] = token
    acai_data["tokenHash"] = token_hash
    acai_data["token_updated"] = time.time()
    try:
        with open(str(acai_file), "w", encoding="utf-8") as f:
            json.dump(acai_data, f, ensure_ascii=False, indent=2)
    except Exception as e:
        return token, "Token obtenido pero error guardando: {}".format(e)

    return token, ""
