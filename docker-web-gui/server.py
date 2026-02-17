#!/usr/bin/env python3
"""
Docker Web GUI — Servidor HTTP local para gestionar docker-web.sh
Stdlib puro, sin dependencias externas.
"""

import base64
import http.server
import json
import os
import re
import shutil
import socket
import ssl
import subprocess
import sys
import tempfile
import threading
import time
import urllib.parse
import urllib.request
import zipfile
from pathlib import Path

def _get_script_dir():
    """Return the directory containing resources. Handles py2app bundles."""
    # py2app sets this env var to Contents/Resources inside the .app bundle
    if hasattr(sys, '_MEIPASS'):
        return Path(sys._MEIPASS)
    frozen = getattr(sys, 'frozen', None)
    if frozen:
        # py2app: sys.executable is Contents/MacOS/Docker Web GUI
        return Path(sys.executable).resolve().parent.parent / "Resources"
    return Path(__file__).resolve().parent

SCRIPT_DIR = _get_script_dir()
DEFAULT_WEBS_DIR = Path.home() / "webs"
DEFAULT_PORT = 9090
ACAI_AUTH_URL = "https://ws.cocosolution.com/api/auth/"

CONFIG_DIR = Path.home() / ".docker-web-gui"
CONFIG_FILE = CONFIG_DIR / "config.json"
KEYCHAIN_SERVICE = "docker-web-gui"

DEFAULT_CONFIG = {
    "webs_dir": "~/webs",
    "refresh_interval": 15,
    "acai_username": "",
    "theme": "dark",
    "web_base_dir": "",
    "docker_web_sh": "",
}


def load_config():
    """Load config from ~/.docker-web-gui/config.json, returning defaults if missing."""
    try:
        if CONFIG_FILE.exists():
            with open(str(CONFIG_FILE), "r", encoding="utf-8") as f:
                saved = json.load(f)
            config = dict(DEFAULT_CONFIG)
            config.update(saved)
            return config
    except Exception:
        pass
    return dict(DEFAULT_CONFIG)


def save_config(config):
    """Save config to ~/.docker-web-gui/config.json with restricted permissions."""
    try:
        os.makedirs(str(CONFIG_DIR), mode=0o700, exist_ok=True)
        tmp = str(CONFIG_FILE) + ".tmp"
        with open(tmp, "w", encoding="utf-8") as f:
            json.dump(config, f, ensure_ascii=False, indent=2)
        os.chmod(tmp, 0o600)
        os.replace(tmp, str(CONFIG_FILE))
    except Exception as e:
        raise RuntimeError("Error saving config: {}".format(e))


def get_webs_dir():
    """Return the configured webs directory as a Path."""
    config = load_config()
    return Path(os.path.expanduser(config.get("webs_dir", "~/webs")))


def get_web_base_dir():
    """Return the configured web-base directory, or auto-detect next to docker-web.sh."""
    config = load_config()
    val = config.get("web_base_dir", "")
    if val:
        p = Path(os.path.expanduser(val)).resolve()
        if p.is_dir():
            return p
    # web-base always lives next to docker-web.sh on the filesystem
    # Search the real filesystem locations (not inside .app bundle)
    for candidate in [
        SCRIPT_DIR.parent / "web-base",
        Path.home() / "scripts" / "web-base",
    ]:
        if candidate.is_dir():
            return candidate
    return None


def get_docker_web_sh():
    """Return the configured docker-web.sh path, or the default relative to SCRIPT_DIR."""
    config = load_config()
    val = config.get("docker_web_sh", "")
    if val:
        return Path(os.path.expanduser(val)).resolve()
    # Check bundled copy (inside .app Resources)
    bundled = SCRIPT_DIR / "docker-web.sh"
    if bundled.is_file():
        return bundled
    # Check relative to source (dev mode)
    default = SCRIPT_DIR.parent / "docker-web.sh"
    if default.is_file():
        return default
    return None


def keychain_get(account):
    """Read a password from macOS Keychain. Returns None if not found."""
    try:
        proc = subprocess.run(
            ["security", "find-generic-password", "-s", KEYCHAIN_SERVICE, "-a", account, "-w"],
            capture_output=True, text=True, timeout=10,
        )
        if proc.returncode == 0:
            return proc.stdout.strip()
    except Exception:
        pass
    return None


def keychain_set(account, password):
    """Store a password in macOS Keychain (-U updates if exists)."""
    subprocess.run(
        ["security", "add-generic-password", "-U", "-s", KEYCHAIN_SERVICE, "-a", account, "-w", password],
        capture_output=True, text=True, timeout=10,
    )


def keychain_delete(account):
    """Delete a password from macOS Keychain. Ignores errors if not found."""
    subprocess.run(
        ["security", "delete-generic-password", "-s", KEYCHAIN_SERVICE, "-a", account],
        capture_output=True, text=True, timeout=10,
    )


_ssl_ctx_cache = None


def _get_ssl_context():
    """Devuelve un contexto SSL cacheado. En macOS sin certs configurados usa certifi o unverified."""
    global _ssl_ctx_cache
    if _ssl_ctx_cache is not None:
        return _ssl_ctx_cache
    # Intentar con certifi (si esta instalado via pip)
    try:
        import certifi
        _ssl_ctx_cache = ssl.create_default_context(cafile=certifi.where())
        return _ssl_ctx_cache
    except ImportError:
        pass
    # Intentar contexto por defecto del sistema
    ctx = ssl.create_default_context()
    # Test real contra el servidor para ver si funciona
    try:
        test_req = urllib.request.Request(ACAI_AUTH_URL, method="HEAD")
        urllib.request.urlopen(test_req, context=ctx, timeout=5)
        _ssl_ctx_cache = ctx
        return _ssl_ctx_cache
    except urllib.error.URLError:
        # Certs del sistema no funcionan, usar unverified (herramienta local)
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        _ssl_ctx_cache = ctx
        return _ssl_ctx_cache
    except Exception:
        _ssl_ctx_cache = ctx
        return _ssl_ctx_cache


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
            return json.loads(resp.read().decode())
    except urllib.error.HTTPError as e:
        body = e.read().decode()
        try:
            return json.loads(body)
        except json.JSONDecodeError:
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


def find_free_port(start: int) -> int:
    port = start
    while port < start + 100:
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            if s.connect_ex(("127.0.0.1", port)) != 0:
                return port
        port += 1
    return start


def run_cmd(args, timeout=120, env=None):
    """Ejecuta un comando y devuelve (returncode, stdout, stderr)."""
    try:
        proc = subprocess.run(
            args,
            capture_output=True,
            text=True,
            timeout=timeout,
            env=env,
        )
        return proc.returncode, proc.stdout, proc.stderr
    except subprocess.TimeoutExpired:
        return 1, "", "Timeout after {}s".format(timeout)
    except Exception as e:
        return 1, "", str(e)


def _docker_web_env():
    """Build env dict for docker-web.sh with WEB_BASE_DIR set."""
    env = dict(os.environ)
    web_base = get_web_base_dir()
    if web_base:
        env["WEB_BASE_DIR"] = str(web_base)
    return env


def get_projects():
    """Lista proyectos activos usando docker ps con labels."""
    rc, out, err = run_cmd([
        "docker", "ps",
        "--filter", "label=docker-web=true",
        "--format", "{{.ID}}\t{{.Names}}\t{{.Status}}\t{{.Ports}}\t{{.Labels}}"
    ])
    if rc != 0:
        return []

    projects = {}
    for line in out.strip().splitlines():
        if not line.strip():
            continue
        parts = line.split("\t")
        if len(parts) < 5:
            continue
        cid, name, status, ports, labels = parts

        # Extraer project name del label
        proj = ""
        for lbl in labels.split(","):
            lbl = lbl.strip()
            if lbl.startswith("docker-web-project="):
                proj = lbl.split("=", 1)[1]
                break

        if not proj:
            continue

        if proj not in projects:
            projects[proj] = {
                "name": proj,
                "containers": [],
                "status": "running",
                "web_url": "",
                "https_url": "",
                "db_port": "",
                "project_dir": "",
            }

        container_info = {
            "id": cid,
            "name": name,
            "status": status,
            "ports": ports,
        }
        projects[proj]["containers"].append(container_info)

        # Extraer URL web y puerto DB de los puertos
        if name.endswith("-web"):
            match = re.search(r"0\.0\.0\.0:(\d+)->80/tcp", ports)
            if match:
                projects[proj]["web_url"] = "http://localhost:{}".format(match.group(1))
            match = re.search(r"0\.0\.0\.0:(\d+)->443/tcp", ports)
            if match:
                projects[proj]["https_url"] = "https://localhost:{}".format(match.group(1))

        if name.endswith("-db"):
            match = re.search(r"0\.0\.0\.0:(\d+)->3306/tcp", ports)
            if match:
                projects[proj]["db_port"] = match.group(1)

    # Intentar detectar el directorio del proyecto via label o docker inspect
    for proj_name, proj_data in projects.items():
        web_containers = [c for c in proj_data["containers"] if c["name"].endswith("-web")]
        if web_containers:
            # Try label first (works for both acai and local modes)
            rc, out, _ = run_cmd([
                "docker", "inspect",
                "--format", '{{index .Config.Labels "docker-web-dir"}}',
                web_containers[0]["name"]
            ])
            if rc == 0 and out.strip() and out.strip() != "<no value>":
                proj_data["project_dir"] = out.strip()
            else:
                # Fallback: inspect mounts (for old containers without label)
                rc, out, _ = run_cmd([
                    "docker", "inspect",
                    "--format", "{{range .Mounts}}{{if eq .Destination \"/var/www/html\"}}{{.Source}}{{end}}{{end}}",
                    web_containers[0]["name"]
                ])
                if rc == 0 and out.strip():
                    proj_data["project_dir"] = out.strip()

        # Add acai flag
        pdir = proj_data.get("project_dir", "")
        if pdir:
            proj_data["acai"] = (Path(pdir) / ".acai").exists()
        else:
            proj_data["acai"] = False

        # Read acai_domain from .acai file
        if proj_data["acai"] and pdir:
            try:
                with open(str(Path(pdir) / ".acai"), "r", encoding="utf-8") as f:
                    acai_data = json.load(f)
                proj_data["acai_domain"] = acai_data.get("domain", "")
            except Exception:
                proj_data["acai_domain"] = ""
        else:
            proj_data["acai_domain"] = ""

    return list(projects.values())


def sanitize_path(path_str):
    """Valida que el path sea un directorio real y no escape."""
    p = Path(os.path.expanduser(path_str)).resolve()
    if not p.exists():
        return None
    return str(p)


def validate_container_name(name):
    """Solo permite caracteres seguros en nombres de contenedor."""
    return bool(re.match(r'^[a-zA-Z0-9][a-zA-Z0-9_.-]*$', name))


class Handler(http.server.BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        # Silenciar logs de acceso normales
        pass

    def _send_cors_headers(self):
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type, Authorization, X-Acai-Token")

    def do_OPTIONS(self):
        self.send_response(204)
        self._send_cors_headers()
        self.send_header("Content-Length", "0")
        self.end_headers()

    def send_json(self, data, status=200):
        body = json.dumps(data).encode()
        self.send_response(status)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self._send_cors_headers()
        self.end_headers()
        self.wfile.write(body)

    def send_error_json(self, msg, status=400):
        self.send_json({"error": msg}, status)

    def read_body(self):
        length = int(self.headers.get("Content-Length", 0))
        if length == 0:
            return {}
        raw = self.rfile.read(length)
        try:
            return json.loads(raw)
        except json.JSONDecodeError:
            return {}

    def do_GET(self):
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path
        qs = urllib.parse.parse_qs(parsed.query)

        if path == "/" or path == "/index.html":
            self.serve_index()
        elif path == "/api/projects":
            self.handle_get_projects()
        elif path.startswith("/api/logs/"):
            container = path[len("/api/logs/"):]
            self.handle_logs(container)
        elif path == "/api/local-webs":
            self.handle_local_webs()
        elif path == "/api/browse":
            self.handle_browse(qs)
        elif path == "/api/browse-files":
            self.handle_browse_files(qs)
        elif path == "/api/settings":
            self.handle_get_settings()
        elif path == "/api/settings/passwords":
            self.handle_get_passwords()
        elif path == "/api/watcher-logs":
            self.handle_watcher_logs()
        elif path.startswith("/local/"):
            self.handle_local_proxy("GET")
        else:
            self.send_error_json("Not found", 404)

    def do_POST(self):
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path
        body = self.read_body()

        if path == "/api/launch":
            self.handle_launch(body)
        elif path == "/api/stop":
            self.handle_stop(body)
        elif path == "/api/destroy":
            self.handle_destroy(body)
        elif path == "/api/local-webs/delete":
            self.handle_local_webs_delete(body)
        elif path == "/api/local-webs/open":
            self.handle_local_webs_open(body)
        elif path == "/api/local-webs/vscode":
            self.handle_local_webs_vscode(body)
        elif path == "/api/acai/login":
            self.handle_acai_login(body)
        elif path == "/api/acai/select-domain":
            self.handle_acai_select_domain(body)
        elif path == "/api/acai/pull-web":
            self.handle_acai_pull_web(body)
        elif path == "/api/settings":
            self.handle_save_settings(body)
        elif path.startswith("/local/"):
            self.handle_local_proxy("POST")
        else:
            self.send_error_json("Not found", 404)

    def serve_index(self):
        index_path = SCRIPT_DIR / "index.html"
        if not index_path.exists():
            self.send_error_json("index.html not found", 500)
            return
        content = index_path.read_bytes()
        self.send_response(200)
        self.send_header("Content-Type", "text/html; charset=utf-8")
        self.send_header("Content-Length", str(len(content)))
        self.end_headers()
        self.wfile.write(content)

    def handle_get_projects(self):
        projects = get_projects()
        self.send_json({"projects": projects})

    def handle_logs(self, container):
        if not validate_container_name(container):
            self.send_error_json("Invalid container name")
            return
        rc, out, err = run_cmd(["docker", "logs", "--tail", "200", container])
        # docker logs puede escribir a stderr para algunos outputs
        logs = out + err
        self.send_json({"logs": logs})

    def handle_browse(self, qs):
        raw = qs.get("path", [os.path.expanduser("~")])[0]
        path = sanitize_path(raw)
        if not path:
            self.send_error_json("Invalid path")
            return

        p = Path(path)
        if not p.is_dir():
            self.send_error_json("Not a directory")
            return

        entries = []
        try:
            for item in sorted(p.iterdir()):
                if item.name.startswith("."):
                    continue
                if item.is_dir():
                    entries.append({
                        "name": item.name,
                        "path": str(item),
                        "type": "dir",
                    })
        except PermissionError:
            self.send_error_json("Permission denied")
            return

        self.send_json({
            "current": str(p),
            "parent": str(p.parent) if str(p) != "/" else None,
            "entries": entries,
        })

    def handle_browse_files(self, qs):
        raw = qs.get("path", [os.path.expanduser("~")])[0]
        ext = qs.get("ext", ["sql"])[0]
        path = sanitize_path(raw)
        if not path:
            self.send_error_json("Invalid path")
            return

        p = Path(path)
        if not p.is_dir():
            self.send_error_json("Not a directory")
            return

        # Solo permitimos extensiones alfanuméricas
        if not re.match(r'^[a-zA-Z0-9]+$', ext):
            self.send_error_json("Invalid extension")
            return

        entries = []
        try:
            for item in sorted(p.iterdir()):
                if item.name.startswith("."):
                    continue
                if item.is_dir():
                    entries.append({
                        "name": item.name,
                        "path": str(item),
                        "type": "dir",
                    })
                elif item.suffix == ".{}".format(ext):
                    size = item.stat().st_size
                    if size > 1048576:
                        size_str = "{:.1f} MB".format(size / 1048576)
                    elif size > 1024:
                        size_str = "{:.0f} KB".format(size / 1024)
                    else:
                        size_str = "{} B".format(size)
                    entries.append({
                        "name": item.name,
                        "path": str(item),
                        "type": "file",
                        "size": size_str,
                    })
        except PermissionError:
            self.send_error_json("Permission denied")
            return

        self.send_json({
            "current": str(p),
            "parent": str(p.parent) if str(p) != "/" else None,
            "entries": entries,
        })

    def handle_launch(self, body):
        project_dir = body.get("project_dir", "")
        if not project_dir:
            self.send_error_json("project_dir is required")
            return

        path = sanitize_path(project_dir)
        if not path or not Path(path).is_dir():
            self.send_error_json("Invalid project directory")
            return

        docker_web_sh = get_docker_web_sh()
        if not docker_web_sh:
            self.send_error_json("docker-web.sh no configurado. Configúralo en Settings.", 500)
            return
        args = [str(docker_web_sh), path]

        # SQL local
        sql_file = body.get("sql_file", "")
        if sql_file:
            sql_path = sanitize_path(sql_file)
            if not sql_path or not Path(sql_path).is_file():
                self.send_error_json("Invalid SQL file")
                return
            args += ["--sql", sql_path]

        # Remote DB
        remote_host = body.get("remote_host", "")
        if remote_host:
            remote_user = body.get("remote_user", "")
            remote_pass = body.get("remote_pass", "")
            remote_dbname = body.get("remote_dbname", "")
            if not all([remote_user, remote_pass, remote_dbname]):
                self.send_error_json("remote_user, remote_pass, and remote_dbname are required for remote DB")
                return
            args += [
                "--remote-db", remote_host,
                "--remote-user", remote_user,
                "--remote-pass", remote_pass,
                "--remote-dbname", remote_dbname,
            ]
            remote_port = body.get("remote_port", "")
            if remote_port:
                args += ["--remote-port", remote_port]

        # Redis
        if body.get("redis"):
            args.append("--redis")

        # Siempre rebuild (la cache de Docker evita reconstruir si no hay cambios)
        args.append("--rebuild")

        # Acai mode
        if body.get("acai") or (Path(path) / ".acai").exists():
            args.append("--acai")

        # Ejecutar con timeout largo para builds
        rc, out, err = run_cmd(args, timeout=300, env=_docker_web_env())
        # Limpiar códigos ANSI del output
        ansi_escape = re.compile(r'\x1B(?:[@-Z\\-_]|\[[0-?]*[ -/]*[@-~])')
        clean_out = ansi_escape.sub('', out + err)

        self.send_json({
            "success": rc == 0,
            "output": clean_out,
            "returncode": rc,
        })

    def handle_stop(self, body):
        project_dir = body.get("project_dir", "")
        if not project_dir:
            self.send_error_json("project_dir is required")
            return

        path = sanitize_path(project_dir)
        if not path:
            self.send_error_json("Invalid project directory")
            return

        docker_web_sh = get_docker_web_sh()
        if not docker_web_sh:
            self.send_error_json("docker-web.sh no configurado. Configúralo en Settings.", 500)
            return
        rc, out, err = run_cmd([str(docker_web_sh), path, "--stop"], timeout=60, env=_docker_web_env())
        ansi_escape = re.compile(r'\x1B(?:[@-Z\\-_]|\[[0-?]*[ -/]*[@-~])')
        clean_out = ansi_escape.sub('', out + err)

        self.send_json({
            "success": rc == 0,
            "output": clean_out,
        })

    def handle_destroy(self, body):
        project_dir = body.get("project_dir", "")
        if not project_dir:
            self.send_error_json("project_dir is required")
            return

        path = sanitize_path(project_dir)
        if not path:
            self.send_error_json("Invalid project directory")
            return

        docker_web_sh = get_docker_web_sh()
        if not docker_web_sh:
            self.send_error_json("docker-web.sh no configurado. Configúralo en Settings.", 500)
            return
        rc, out, err = run_cmd([str(docker_web_sh), path, "--destroy"], timeout=60, env=_docker_web_env())
        ansi_escape = re.compile(r'\x1B(?:[@-Z\\-_]|\[[0-?]*[ -/]*[@-~])')
        clean_out = ansi_escape.sub('', out + err)

        self.send_json({
            "success": rc == 0,
            "output": clean_out,
        })


    # ---- Local Webs ----

    def handle_local_webs(self):
        """List folders inside the configured webs directory."""
        webs_dir = get_webs_dir()
        if not webs_dir.is_dir():
            self.send_json({"webs": []})
            return

        running_dirs = set()
        for p in get_projects():
            d = p.get("project_dir", "")
            if d:
                running_dirs.add(d)

        webs = []
        try:
            for item in sorted(webs_dir.iterdir()):
                if not item.is_dir() or item.name.startswith("."):
                    continue
                try:
                    stat = item.stat()
                    mtime = stat.st_mtime
                except OSError:
                    mtime = 0
                entry = {
                    "name": item.name,
                    "path": str(item),
                    "modified": mtime,
                    "running": str(item) in running_dirs,
                    "acai": (item / ".acai").is_file(),
                    "has_db": (item / "database.sql").is_file(),
                }
                # Count modules, hooks, assets
                modulos_dir = item / "modulos"
                if modulos_dir.is_dir():
                    entry["modules"] = sum(1 for x in modulos_dir.iterdir() if x.is_dir())
                hooks_dir = item / "hooks"
                if hooks_dir.is_dir():
                    entry["hooks"] = sum(1 for x in hooks_dir.iterdir() if x.suffix == ".php")
                layout_file = item / "layout.json"
                if layout_file.is_file():
                    try:
                        with open(layout_file) as f:
                            layout = json.load(f)
                        entry["assets"] = len(layout.get("librariesJSONt") or []) + len(layout.get("librariesJSONb") or [])
                    except Exception:
                        pass
                webs.append(entry)
        except PermissionError:
            pass

        self.send_json({"webs": webs})

    def handle_local_webs_delete(self, body):
        """Delete a local web folder."""
        folder = body.get("path", "")
        if not folder:
            self.send_error_json("path is required")
            return
        p = Path(folder).resolve()
        webs_dir = get_webs_dir()
        if not str(p).startswith(str(webs_dir.resolve())):
            self.send_error_json("Path must be inside {}".format(webs_dir))
            return
        if not p.is_dir():
            self.send_error_json("Directory not found")
            return
        try:
            shutil.rmtree(str(p))
            self.send_json({"success": True})
        except Exception as e:
            self.send_json({"success": False, "error": str(e)})

    def handle_local_webs_open(self, body):
        """Open a folder in the system file manager."""
        folder = body.get("path", "")
        if not folder:
            self.send_error_json("path is required")
            return
        p = Path(folder).resolve()
        webs_dir = get_webs_dir()
        if not str(p).startswith(str(webs_dir.resolve())):
            self.send_error_json("Path must be inside {}".format(webs_dir))
            return
        if not p.is_dir():
            self.send_error_json("Directory not found")
            return
        try:
            subprocess.Popen(["open", str(p)])
            self.send_json({"success": True})
        except Exception as e:
            self.send_json({"success": False, "error": str(e)})

    def handle_local_webs_vscode(self, body):
        """Open a folder in Visual Studio Code."""
        folder = body.get("path", "")
        if not folder:
            self.send_error_json("path is required")
            return
        p = Path(folder).resolve()
        if not p.is_dir():
            self.send_error_json("Directory not found")
            return
        try:
            subprocess.Popen(["open", "-a", "Visual Studio Code", str(p)])
            self.send_json({"success": True})
        except Exception as e:
            self.send_json({"success": False, "error": str(e)})

    # ---- Settings ----

    def handle_get_settings(self):
        """GET /api/settings — return config + credential flags."""
        config = load_config()
        config["has_acai_password"] = keychain_get("acai") is not None
        config["has_mysql_password"] = keychain_get("mysql") is not None
        self.send_json(config)

    def handle_save_settings(self, body):
        """POST /api/settings — save config and optionally credentials."""
        config = load_config()
        if "webs_dir" in body:
            config["webs_dir"] = body["webs_dir"]
        if "refresh_interval" in body:
            try:
                val = int(body["refresh_interval"])
                if val >= 1:
                    config["refresh_interval"] = val
            except (ValueError, TypeError):
                pass
        if "acai_username" in body:
            config["acai_username"] = body["acai_username"]
        if "theme" in body:
            config["theme"] = body["theme"] if body["theme"] in ("dark", "light") else "dark"
        if "web_base_dir" in body:
            config["web_base_dir"] = body["web_base_dir"]
        if "docker_web_sh" in body:
            config["docker_web_sh"] = body["docker_web_sh"]

        try:
            save_config(config)
        except RuntimeError as e:
            self.send_error_json(str(e), 500)
            return

        # Handle Keychain credentials
        if "acai_password" in body:
            pw = body["acai_password"]
            if pw:
                keychain_set("acai", pw)
            else:
                keychain_delete("acai")
        if "mysql_password" in body:
            pw = body["mysql_password"]
            if pw:
                keychain_set("mysql", pw)
            else:
                keychain_delete("mysql")

        self.send_json({"success": True})

    def handle_get_passwords(self):
        """GET /api/settings/passwords — return stored passwords for prefill."""
        self.send_json({
            "acai_password": keychain_get("acai") or "",
            "mysql_password": keychain_get("mysql") or "",
        })

    # ---- Watcher logs ----

    def handle_watcher_logs(self):
        """GET /api/watcher-logs — return watcher log buffer."""
        with _watcher_log_lock:
            entries = list(_watcher_log)
        self.send_json({"logs": entries})

    # ---- Local Proxy ----

    def handle_local_proxy(self, method):
        """Proxy inverso: /local/{port}/path → http://localhost:{port}/path"""
        parsed = urllib.parse.urlparse(self.path)
        match = re.match(r'^/local/(\d+)(/.*)$', parsed.path)
        if not match:
            self.send_error_json("Expected /local/{port}/path", 400)
            return
        port = int(match.group(1))
        target_path = match.group(2)
        query = parsed.query
        target_url = "http://localhost:{}{}".format(port, target_path)
        if query:
            target_url += "?" + query

        # Read body for POST
        body_data = None
        if method == "POST":
            length = int(self.headers.get("Content-Length", 0))
            if length > 0:
                body_data = self.rfile.read(length)

        # Forward selective headers
        fwd_headers = {}
        for hdr in ("Content-Type", "Authorization", "X-Acai-Token"):
            val = self.headers.get(hdr)
            if val:
                fwd_headers[hdr] = val

        try:
            req = urllib.request.Request(
                target_url,
                data=body_data,
                headers=fwd_headers,
                method=method,
            )
            with urllib.request.urlopen(req, timeout=30) as resp:
                resp_body = resp.read()
                resp_status = resp.status
                resp_content_type = resp.getheader("Content-Type", "application/octet-stream")
        except urllib.error.HTTPError as e:
            resp_body = e.read()
            resp_status = e.code
            resp_content_type = e.headers.get("Content-Type", "application/octet-stream")
        except Exception as e:
            self.send_error_json("Proxy error: {}".format(e), 502)
            return

        self.send_response(resp_status)
        self.send_header("Content-Type", resp_content_type)
        self.send_header("Content-Length", str(len(resp_body)))
        self._send_cors_headers()
        self.end_headers()
        self.wfile.write(resp_body)

    # ---- Acai Code Auth ----

    def handle_acai_login(self, body):
        """Step 1: SimpleAuth — devuelve lista de dominios del usuario."""
        username = body.get("username", "")
        password = body.get("password", "")
        if not username or not password:
            self.send_error_json("username and password are required")
            return

        creds = base64.b64encode("{}:{}".format(username, password).encode()).decode()
        auth_header = "SimpleAuth {}".format(creds)
        result = acai_request(auth_header)

        if not result.get("success") and not result.get("data"):
            self.send_json({
                "success": False,
                "error": result.get("error", result.get("message", "Login failed")),
            }, 401)
            return

        data = result.get("data", {})
        domains = data.get("domains", [])
        session_hash = data.get("hash", "")

        self.send_json({
            "success": True,
            "domains": domains,
            "hash": session_hash,
        })

    def handle_acai_select_domain(self, body):
        """Step 2: Login — selecciona dominio y obtiene token."""
        username = body.get("username", "")
        session_hash = body.get("hash", "")
        domain_num = body.get("domain_num", "")
        if not username or not session_hash or not domain_num:
            self.send_error_json("username, hash, and domain_num are required")
            return

        creds = base64.b64encode("{}:{}:{}".format(
            username, session_hash, domain_num
        ).encode()).decode()
        auth_header = "Login {}".format(creds)
        result = acai_request(auth_header)

        if not result.get("success") and not result.get("data"):
            self.send_json({
                "success": False,
                "error": result.get("error", result.get("message", "Domain selection failed")),
            }, 401)
            return

        data = result.get("data", {})
        token = data.get("token", data.get("renewToken", ""))
        token_hash = data.get("tokenHash", "")
        user_info = data.get("user", {})
        # domain puede venir en data.domain o dentro de user.domain
        domain_info = data.get("domain") or {}
        if not domain_info and isinstance(user_info.get("domain"), dict):
            domain_info = user_info["domain"]

        # Extraer solo lo relevante del user para no exponer todo
        user_summary = {}
        if user_info:
            user_summary = {
                "username": user_info.get("username", ""),
                "fullname": user_info.get("fullname", ""),
                "email": user_info.get("email", ""),
                "isAdmin": user_info.get("isAdmin", "0"),
            }

        # Extraer lo relevante del domain
        domain_summary = {}
        if domain_info:
            domain_summary = {
                "num": domain_info.get("num", ""),
                "domain": domain_info.get("domain", ""),
                "mysql_host": domain_info.get("mysql_host", ""),
                "mysql_db": domain_info.get("mysql_db", ""),
                "mysql_user": domain_info.get("mysql_user", ""),
                "mysql_pass": domain_info.get("mysql_pass", domain_info.get("mysql_password", "")),
                "ssl": domain_info.get("ssl", "0"),
            }

        self.send_json({
            "success": True,
            "token": token,
            "tokenHash": token_hash,
            "domain": domain_summary,
            "user": user_summary,
        })

    def handle_acai_pull_web(self, body):
        """Pull a web from Acai: download ZIP via getAcaiPackFiles and extract."""
        domain = body.get("domain", "")
        ssl_enabled = str(body.get("ssl", "1")) != "0"
        token = body.get("token", "")
        token_hash = body.get("tokenHash", "")
        dest_dir = body.get("dest_dir", "")
        include_uploads = body.get("include_uploads", False)

        if not domain or not token or not token_hash:
            self.send_error_json("domain, token, and tokenHash are required")
            return

        # Default destination
        if not dest_dir:
            dest_dir = str(get_webs_dir() / domain)

        dest = Path(dest_dir).expanduser().resolve()

        steps = []
        errors = []

        # Step 1: Create directory + .acai marker
        try:
            dest.mkdir(parents=True, exist_ok=True)
            acai_marker = dest / ".acai"
            marker_data = {
                "domain": domain,
                "ssl": ssl_enabled,
                "timestamp": time.time(),
            }
            with open(str(acai_marker), "w", encoding="utf-8") as f:
                json.dump(marker_data, f, ensure_ascii=False, indent=2)
            steps.append("Directorio creado en {}".format(dest))
        except Exception as e:
            self.send_json({"success": False, "error": "Error creando directorio: {}".format(e)})
            return

        # Step 2: Download ZIP via getAcaiPackFiles
        zip_path = None
        try:
            payload = {
                "action_ws": "getAcaiPackFiles",
                "token": token,
                "tokenHash": token_hash,
                "uploads": include_uploads,
            }
            result = acai_web_request_zip(domain, ssl_enabled, payload, timeout=120)
            if result.get("error"):
                self.send_json({"success": False, "error": "getAcaiPackFiles: {}".format(result["error"])})
                return
            zip_path = result["path"]
            steps.append("ZIP descargado")
        except Exception as e:
            self.send_json({"success": False, "error": "Error descargando ZIP: {}".format(e)})
            return

        # Step 3: Extract ZIP to destination
        try:
            with zipfile.ZipFile(zip_path, "r") as zf:
                zf.extractall(str(dest))
            steps.append("ZIP extraido")
        except zipfile.BadZipFile:
            self.send_json({"success": False, "error": "El servidor no devolvio un ZIP valido"})
            return
        except Exception as e:
            self.send_json({"success": False, "error": "Error extrayendo ZIP: {}".format(e)})
            return
        finally:
            # Clean up temp file
            try:
                os.unlink(zip_path)
            except OSError:
                pass

        # Count results from extracted directories
        modules_count = 0
        hooks_count = 0
        uploads_count = 0

        modulos_dir = dest / "modulos"
        if modulos_dir.is_dir():
            modules_count = sum(1 for x in modulos_dir.iterdir() if x.is_dir())
        steps.append("{} modulos".format(modules_count))

        hooks_dir = dest / "hooks"
        if hooks_dir.is_dir():
            hooks_count = sum(1 for x in hooks_dir.iterdir() if x.is_file())
        steps.append("{} hooks".format(hooks_count))

        uploads_dir = dest / "uploads"
        if uploads_dir.is_dir():
            uploads_count = sum(1 for x in uploads_dir.rglob("*") if x.is_file())
            steps.append("{} uploads".format(uploads_count))

        # Step 4: Mysqldump (optional, if db_password provided)
        has_db = False
        db_password = body.get("db_password", "")
        if db_password:
            db_host = body.get("db_host", "")
            db_user = body.get("db_user", "")
            db_name = body.get("db_name", "")
            if db_host and db_user and db_name:
                try:
                    dump_file = dest / "database.sql"
                    rc_which, _, _ = run_cmd(["which", "mysqldump"])
                    if rc_which == 0:
                        mysqldump_cmd = [
                            "mysqldump",
                            "-h", db_host,
                            "-u", db_user,
                            "-p{}".format(db_password),
                            "--single-transaction",
                            "--routines",
                            "--triggers",
                            db_name,
                        ]
                    else:
                        mysqldump_cmd = [
                            "docker", "run", "--rm",
                            "mariadb:10.11",
                            "mysqldump",
                            "-h", db_host,
                            "-u", db_user,
                            "-p{}".format(db_password),
                            "--single-transaction",
                            "--routines",
                            "--triggers",
                            db_name,
                        ]

                    rc, out, err = run_cmd(mysqldump_cmd, timeout=120)
                    if rc == 0 and out.strip():
                        clean_dump = re.sub(
                            r'/\*![0-9]+ DEFINER=`[^`]*`@`[^`]*`\*/',
                            '',
                            out,
                        )
                        with open(str(dump_file), "w", encoding="utf-8") as f:
                            f.write(clean_dump)
                        has_db = True
                        steps.append("Base de datos descargada (database.sql)")
                    else:
                        errors.append("mysqldump: {}".format(err.strip()[:200] if err else "sin output"))
                except Exception as e:
                    errors.append("mysqldump: {}".format(e))
            else:
                errors.append("mysqldump: faltan credenciales (host/user/db)")

        self.send_json({
            "success": True,
            "acai": True,
            "project_dir": str(dest),
            "modules_count": modules_count,
            "hooks_count": hooks_count,
            "uploads_count": uploads_count,
            "has_db": has_db,
            "steps": steps,
            "errors": errors,
        })


# ---- Module watcher ----

_watcher_log = []  # [{ts, level, msg}, ...]
_watcher_log_lock = threading.Lock()
MAX_WATCHER_LOG = 200


def _watcher_log_add(level, msg):
    """Add entry to watcher log buffer. level: info|error|warn"""
    with _watcher_log_lock:
        _watcher_log.append({
            "ts": time.time(),
            "level": level,
            "msg": msg,
        })
        if len(_watcher_log) > MAX_WATCHER_LOG:
            _watcher_log[:] = _watcher_log[-MAX_WATCHER_LOG:]
    print("[watcher] {}".format(msg))
    sys.stdout.flush()


def _scan_modules_mtimes(projects):
    """Scan modulos/ dirs of running projects and return {filepath: mtime}."""
    mtimes = {}
    for proj in projects:
        proj_dir = proj.get("project_dir", "")
        if not proj_dir:
            continue
        modulos_dir = Path(proj_dir) / "modulos"
        if not modulos_dir.is_dir():
            continue
        for f in modulos_dir.rglob("*"):
            if f.is_file():
                try:
                    mtimes[str(f)] = f.stat().st_mtime
                except OSError:
                    pass
    return mtimes


def _read_module_files(mod_dir):
    """Read all files from a module directory and return the payload for generateModuleFromString."""
    mod_name = mod_dir.name
    payload = {"id": mod_name, "editMode": True}

    index_base = mod_dir / "index-base.tpl"
    if index_base.is_file():
        payload["html"] = index_base.read_text(encoding="utf-8", errors="replace")

    index_tpl = mod_dir / "index.tpl"
    if index_tpl.is_file():
        payload["htmlParsed"] = index_tpl.read_text(encoding="utf-8", errors="replace")
    elif "html" in payload:
        payload["htmlParsed"] = payload["html"]

    style = mod_dir / "style.css"
    if style.is_file():
        payload["style"] = style.read_text(encoding="utf-8", errors="replace")

    script = mod_dir / "script.js"
    if script.is_file():
        payload["javascript"] = script.read_text(encoding="utf-8", errors="replace")

    hook = mod_dir / "hook.php"
    if hook.is_file():
        payload["hook"] = hook.read_text(encoding="utf-8", errors="replace")

    builder = mod_dir / "builder.json"
    if builder.is_file():
        try:
            config = json.loads(builder.read_text(encoding="utf-8", errors="replace"))
            payload["notParseComponents"] = config.get("notParseComponents", "0")
            payload["label"] = config.get("label", mod_name)
            payload["description"] = config.get("description", "")
        except json.JSONDecodeError:
            pass

    return payload


def _sync_module_to_local(mod_dir, web_url):
    """Send module files to the local Docker container via generateModuleFromString."""
    mod_name = mod_dir.name
    payload = _read_module_files(mod_dir)
    if not payload.get("html") and not payload.get("style"):
        return

    # Extract port from web_url (e.g. http://localhost:8080)
    match = re.search(r':(\d+)', web_url)
    if not match:
        _watcher_log_add("error", "Sync {}: no port in {}".format(mod_name, web_url))
        return
    port = match.group(1)
    payload["action_ws"] = "generateModuleFromString"
    url = "http://localhost:{}/cms/lib/viewer_functions.php".format(port)

    try:
        data = json.dumps(payload).encode()
        req = urllib.request.Request(
            url,
            data=data,
            headers={"Content-Type": "application/json"},
            method="POST",
        )
        with urllib.request.urlopen(req, timeout=30) as resp:
            result = json.loads(resp.read().decode())
        if result.get("error"):
            _watcher_log_add("error", "Sync {}: {}".format(mod_name, result["error"]))
        else:
            _watcher_log_add("info", "Synced: {}".format(mod_name))
    except Exception as e:
        _watcher_log_add("error", "Sync {}: {}".format(mod_name, e))


def _on_module_changed(changed_files, projects):
    """Detect changed modules and sync them to their local Docker containers."""
    # Group changed files by module directory
    changed_modules = {}  # {mod_dir_str: mod_dir_path}
    for filepath in changed_files:
        parts = Path(filepath).parts
        try:
            idx = parts.index("modulos")
            if idx + 1 < len(parts):
                mod_dir = Path(*parts[:idx + 2])
                changed_modules[str(mod_dir)] = mod_dir
                _watcher_log_add("info", "Changed: {}/{}".format(
                    parts[idx + 1], parts[idx + 2] if idx + 2 < len(parts) else ""))
        except ValueError:
            _watcher_log_add("info", "Changed: {}".format(Path(filepath).name))

    # Map project dirs to web URLs
    proj_map = {}
    for proj in projects:
        pdir = proj.get("project_dir", "")
        web_url = proj.get("web_url", "")
        if pdir and web_url:
            proj_map[pdir] = web_url

    # Sync each changed module to its container
    for mod_dir_str, mod_dir in changed_modules.items():
        # Find which project this module belongs to
        for pdir, web_url in proj_map.items():
            if mod_dir_str.startswith(pdir):
                _sync_module_to_local(mod_dir, web_url)
                break


def start_module_watcher(interval=2):
    """Start a background thread that watches modulos/ in running projects."""
    def _watch_loop():
        mtimes = {}
        while True:
            time.sleep(interval)
            try:
                projects = get_projects()
            except Exception:
                continue
            new_mtimes = _scan_modules_mtimes(projects)
            if mtimes:
                changed = [
                    f for f in new_mtimes
                    if new_mtimes.get(f) != mtimes.get(f)
                ]
                # Also detect new files
                new_files = [f for f in new_mtimes if f not in mtimes]
                all_changed = list(set(changed + new_files))
                if all_changed:
                    _on_module_changed(all_changed, projects)
            mtimes = new_mtimes

    t = threading.Thread(target=_watch_loop, daemon=True)
    t.start()
    print("Module watcher active (interval={}s)".format(interval))
    sys.stdout.flush()
    return t


def _get_watch_files():
    """Return dict {filepath: mtime} for files to watch in reload mode."""
    mtimes = {}
    for pattern in ("*.py", "*.html"):
        for f in SCRIPT_DIR.glob(pattern):
            try:
                mtimes[str(f)] = f.stat().st_mtime
            except OSError:
                pass
    return mtimes


def _run_reload(port_arg):
    """Watcher loop: spawn server subprocess and restart on file changes."""
    args = [sys.executable, str(Path(__file__).resolve()), str(port_arg)]
    env = dict(os.environ, _RELOAD_CHILD="1")

    while True:
        mtimes = _get_watch_files()
        print("[reload] Starting server...")
        sys.stdout.flush()
        proc = subprocess.Popen(args, env=env)
        try:
            while True:
                time.sleep(1)
                # Check if process died
                if proc.poll() is not None:
                    print("[reload] Server exited (code {}), restarting...".format(proc.returncode))
                    sys.stdout.flush()
                    break
                # Check file changes
                new_mtimes = _get_watch_files()
                if new_mtimes != mtimes:
                    changed = [f for f in new_mtimes if new_mtimes.get(f) != mtimes.get(f)]
                    print("[reload] Change detected: {}".format(", ".join(Path(f).name for f in changed)))
                    sys.stdout.flush()
                    proc.terminate()
                    proc.wait(timeout=5)
                    break
        except KeyboardInterrupt:
            proc.terminate()
            proc.wait(timeout=5)
            print("\nShutting down...")
            sys.exit(0)


def start_server(port=DEFAULT_PORT):
    """Start the HTTP server and module watcher. Returns (server, port)."""
    port = find_free_port(port)
    server = http.server.HTTPServer(("127.0.0.1", port), Handler)
    print("Docker Web GUI running at http://localhost:{}".format(port))
    sys.stdout.flush()
    start_module_watcher()
    return server, port


def main():
    # Parse args
    args = sys.argv[1:]
    reload_mode = "--reload" in args
    if reload_mode:
        args.remove("--reload")

    port = int(args[0]) if args else DEFAULT_PORT

    # If reload mode and not the child, run the watcher
    if reload_mode and os.environ.get("_RELOAD_CHILD") != "1":
        _run_reload(port)
        return

    server, port = start_server(port)
    if os.environ.get("_RELOAD_CHILD") == "1":
        print("Auto-reload active")
    print("Press Ctrl+C to stop")
    sys.stdout.flush()

    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\nShutting down...")
        server.shutdown()


if __name__ == "__main__":
    main()
