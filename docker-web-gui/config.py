"""
Configuración, constantes, keychain, SSL y utilidades de Docker Web GUI.
"""

import json
import os
import re
import socket
import ssl
import subprocess
import sys
import urllib.parse
import urllib.request
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
    "gitea_url": "",
    "gitea_org": "acai",
    "gitea_username": "",
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


def sanitize_path(path_str):
    """Valida que el path sea un directorio real y no escape."""
    p = Path(os.path.expanduser(path_str)).resolve()
    if not p.exists():
        return None
    return str(p)


def validate_container_name(name):
    """Solo permite caracteres seguros en nombres de contenedor."""
    return bool(re.match(r'^[a-zA-Z0-9][a-zA-Z0-9_.-]*$', name))
