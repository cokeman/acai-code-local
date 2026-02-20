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
import subprocess
import sys
import threading
import time
import urllib.parse
import urllib.request
import zipfile
from pathlib import Path

from config import (
    SCRIPT_DIR, DEFAULT_WEBS_DIR, DEFAULT_PORT, ACAI_AUTH_URL,
    CONFIG_DIR, CONFIG_FILE, KEYCHAIN_SERVICE, DEFAULT_CONFIG,
    load_config, save_config, get_webs_dir, get_web_base_dir,
    get_docker_web_sh, keychain_get, keychain_set, keychain_delete,
    _get_ssl_context, find_free_port, run_cmd, sanitize_path,
    validate_container_name,
)

from acai_api import (
    acai_request, _parse_php_json,
    acai_web_request, acai_web_request_zip,
    refresh_acai_token,
)

from git_ops import (
    _gitea_api, ACAI_GITIGNORE, _write_gitignore,
    _git_remote_url, _git_connect_repo,
)

from watcher import (
    _watcher_log, _watcher_log_lock,
    _watcher_log_add, _queue_read, _process_queue,
    start_module_watcher, mark_pulling, finish_pulling,
)


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

        # Read tunnel URL
        if pdir:
            tunnel_file = Path(pdir) / ".docker" / "tunnel-url.txt"
            try:
                tunnel_url = tunnel_file.read_text(encoding="utf-8").strip() if tunnel_file.is_file() else ""
            except Exception:
                tunnel_url = ""
            proj_data["tunnel_url"] = tunnel_url
        else:
            proj_data["tunnel_url"] = ""

        # Read bore DB tunnel URL
        if pdir:
            bore_file = Path(pdir) / ".docker" / "bore-db-url.txt"
            try:
                bore_db_url = bore_file.read_text(encoding="utf-8").strip() if bore_file.is_file() else ""
            except Exception:
                bore_db_url = ""
            proj_data["bore_db_url"] = bore_db_url
        else:
            proj_data["bore_db_url"] = ""

        # Read DB credentials from .docker/.env
        proj_data["db_user"] = ""
        proj_data["db_pass"] = ""
        proj_data["db_name"] = ""
        if pdir:
            env_file = Path(pdir) / ".docker" / ".env"
            if env_file.is_file():
                try:
                    for line in env_file.read_text(encoding="utf-8").splitlines():
                        if line.startswith("DB_USERNAME="):
                            proj_data["db_user"] = line.split("=", 1)[1]
                        elif line.startswith("DB_PASSWORD="):
                            proj_data["db_pass"] = line.split("=", 1)[1]
                        elif line.startswith("DB_DATABASE="):
                            proj_data["db_name"] = line.split("=", 1)[1]
                except Exception:
                    pass

        # Read acai_domain and token from .acai file
        if proj_data["acai"] and pdir:
            try:
                with open(str(Path(pdir) / ".acai"), "r", encoding="utf-8") as f:
                    acai_data = json.load(f)
                proj_data["acai_domain"] = acai_data.get("domain", "")
                proj_data["acai_token"] = acai_data.get("token", "")
            except Exception:
                proj_data["acai_domain"] = ""
                proj_data["acai_token"] = ""
        else:
            proj_data["acai_domain"] = ""
            proj_data["acai_token"] = ""

    return list(projects.values())


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
        elif path == "/api/git/status":
            self.handle_git_status(qs)
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
        elif path == "/api/git/init":
            self.handle_git_init(body)
        elif path == "/api/git/push":
            self.handle_git_push(body)
        elif path == "/api/git/pull":
            self.handle_git_pull(body)
        elif path == "/api/gitea/test":
            self.handle_gitea_test(body)
        elif path == "/api/server-git/setup":
            self.handle_server_git_setup(body)
        elif path == "/api/server-git/status":
            self.handle_server_git_status(body)
        elif path == "/api/server-git/push":
            self.handle_server_git_push(body)
        elif path == "/api/server-git/pull":
            self.handle_server_git_pull(body)
        elif path == "/api/local-webs/migrate":
            self.handle_local_webs_migrate(body)
        elif path == "/api/pull-database":
            self.handle_pull_database(body)
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
        acai_file = Path(path) / ".acai"
        is_acai = body.get("acai") or acai_file.exists()
        if is_acai:
            args.append("--acai")

        # Refresh Acai token if credentials available
        token_msg = ""
        if is_acai and acai_file.exists():
            token, token_err = refresh_acai_token(acai_file)
            if token_err:
                token_msg = "[acai] Token no renovado: {}\n".format(token_err)
            elif token:
                token_msg = "[acai] Token renovado correctamente\n"

        # Ejecutar con timeout largo para builds
        rc, out, err = run_cmd(args, timeout=300, env=_docker_web_env())
        # Limpiar códigos ANSI del output
        ansi_escape = re.compile(r'\x1B(?:[@-Z\\-_]|\[[0-?]*[ -/]*[@-~])')
        clean_out = ansi_escape.sub('', out + err)

        # Process pending module queue after successful launch
        if rc == 0 and _queue_read(path):
            def _deferred_queue_process(project_dir):
                """Poll get_projects() until the project appears, then process its queue."""
                for _ in range(30):
                    time.sleep(1)
                    try:
                        for proj in get_projects():
                            if proj.get("project_dir") == project_dir and proj.get("web_url"):
                                _process_queue(project_dir, proj["web_url"])
                                return
                    except Exception:
                        pass
                _watcher_log_add("warning",
                    "Queue: could not find running project for {}".format(
                        Path(project_dir).name))
            threading.Thread(
                target=_deferred_queue_process, args=(path,), daemon=True
            ).start()

        self.send_json({
            "success": rc == 0,
            "output": token_msg + clean_out,
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

        # Fetch existing Gitea repos in one API call
        gitea_repos = set()
        config = load_config()
        if config.get("gitea_url") and config.get("gitea_username") and keychain_get("gitea"):
            org = config.get("gitea_org", "acai")
            page = 1
            while True:
                result = _gitea_api("GET", "/orgs/{}/repos?page={}&limit=50".format(org, page))
                if isinstance(result, list):
                    for r in result:
                        gitea_repos.add(r.get("name", ""))
                    if len(result) < 50:
                        break
                    page += 1
                else:
                    break

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
                git_dir = item / ".git"
                has_git = git_dir.is_dir()
                git_clean = False
                git_changed = 0
                git_ahead = 0
                git_behind = 0
                if has_git:
                    rc, out, _ = run_cmd(["git", "-C", str(item), "status", "--porcelain"], timeout=10)
                    git_clean = (rc == 0 and out.strip() == "")
                    if not git_clean and rc == 0:
                        git_changed = len([l for l in out.strip().splitlines() if l.strip()])
                    # fetch remote to get accurate ahead/behind
                    run_cmd(["git", "-C", str(item), "fetch", "origin"], timeout=8)
                    # ahead/behind
                    rc_ab, ab_out, _ = run_cmd(["git", "-C", str(item), "rev-list", "--left-right", "--count", "HEAD...@{upstream}"], timeout=10)
                    if rc_ab == 0 and ab_out.strip():
                        parts = ab_out.strip().split()
                        if len(parts) == 2:
                            try:
                                git_ahead = int(parts[0])
                                git_behind = int(parts[1])
                            except ValueError:
                                pass
                entry = {
                    "name": item.name,
                    "path": str(item),
                    "modified": mtime,
                    "running": str(item) in running_dirs,
                    "acai": (item / ".acai").is_file(),
                    "has_db": (item / "database.sql").is_file(),
                    "has_git": has_git,
                    "git_clean": git_clean,
                    "git_changed": git_changed,
                    "git_ahead": git_ahead,
                    "git_behind": git_behind,
                    "git_remote_exists": "sync-{}".format(item.name) in gitea_repos,
                }
                # Count modules, hooks, assets (new structure with fallback)
                modulos_dir = item / "template" / "estandar" / "modulos"
                needs_migration = False
                if not modulos_dir.is_dir():
                    modulos_dir = item / "modulos"
                    if modulos_dir.is_dir():
                        needs_migration = True
                if modulos_dir.is_dir():
                    entry["modules"] = sum(1 for x in modulos_dir.iterdir() if x.is_dir())
                hooks_dir = item / "hooks"
                if hooks_dir.is_dir():
                    entry["hooks"] = sum(1 for x in hooks_dir.iterdir() if x.suffix == ".php")
                layout_file = item / "cms" / "lib" / "plugins" / "builder_saas" / "layout.json"
                if not layout_file.is_file():
                    layout_file = item / "layout.json"
                    if layout_file.is_file():
                        needs_migration = True
                if layout_file.is_file():
                    try:
                        with open(layout_file) as f:
                            layout = json.load(f)
                        entry["assets"] = len(layout.get("librariesJSONt") or []) + len(layout.get("librariesJSONb") or [])
                    except Exception:
                        pass
                entry["needs_migration"] = needs_migration
                entry["pending_modules"] = len(_queue_read(str(item)))
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
        config["has_gitea_password"] = keychain_get("gitea") is not None
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
        if "gitea_url" in body:
            config["gitea_url"] = body["gitea_url"].rstrip("/")
        if "gitea_org" in body:
            config["gitea_org"] = body["gitea_org"]
        if "gitea_username" in body:
            config["gitea_username"] = body["gitea_username"]

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
        if "gitea_password" in body:
            pw = body["gitea_password"]
            if pw:
                keychain_set("gitea", pw)
            else:
                keychain_delete("gitea")

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

        # Suppress watcher for this project during pull
        mark_pulling(str(dest))

        steps = []
        errors = []

        # Step 1: Create directory + .acai marker
        try:
            dest.mkdir(parents=True, exist_ok=True)
            acai_marker = dest / ".acai"
            marker_data = {
                "domain": domain,
                "ssl": ssl_enabled,
                "token": token,
                "tokenHash": token_hash,
                "timestamp": time.time(),
                "mysql_host": body.get("db_host", ""),
                "mysql_user": body.get("db_user", ""),
                "mysql_db": body.get("db_name", ""),
            }
            with open(str(acai_marker), "w", encoding="utf-8") as f:
                json.dump(marker_data, f, ensure_ascii=False, indent=2)
            steps.append("Directorio creado en {}".format(dest))
        except Exception as e:
            finish_pulling(str(dest))
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
                finish_pulling(str(dest))
                self.send_json({"success": False, "error": "getAcaiPackFiles: {}".format(result["error"])})
                return
            zip_path = result["path"]
            steps.append("ZIP descargado")
        except Exception as e:
            finish_pulling(str(dest))
            self.send_json({"success": False, "error": "Error descargando ZIP: {}".format(e)})
            return

        # Step 3: Extract ZIP to destination
        try:
            with zipfile.ZipFile(zip_path, "r") as zf:
                exclude_prefixes = ("cms/uploads/webp/",)
                members = [m for m in zf.namelist() if not any(m.startswith(p) for p in exclude_prefixes)]
                zf.extractall(str(dest), members=members)
            steps.append("ZIP extraido")
        except zipfile.BadZipFile:
            finish_pulling(str(dest))
            # Log first bytes to diagnose what the server returned
            try:
                with open(zip_path, "rb") as f:
                    preview = f.read(500)
                preview_str = preview.decode("utf-8", errors="replace")
            except Exception:
                preview_str = "(no se pudo leer)"
            self.send_json({"success": False, "error": "El servidor no devolvio un ZIP valido. Respuesta: {}".format(preview_str[:300])})
            return
        except Exception as e:
            finish_pulling(str(dest))
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

        modulos_dir = dest / "template" / "estandar" / "modulos"
        if modulos_dir.is_dir():
            modules_count = sum(1 for x in modulos_dir.iterdir() if x.is_dir())
        steps.append("{} modulos".format(modules_count))

        hooks_dir = dest / "hooks"
        if hooks_dir.is_dir():
            hooks_count = sum(1 for x in hooks_dir.iterdir() if x.is_file())
        steps.append("{} hooks".format(hooks_count))

        uploads_dir = dest / "cms" / "uploads"
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

        # Auto-connect git if Gitea is configured
        config = load_config()
        git_steps = []
        if config.get("gitea_url") and config.get("gitea_username"):
            try:
                git_steps = _git_connect_repo(dest, domain, config)
            except Exception as e:
                git_steps.append("git auto-init error: {}".format(e))

        # Resume watcher tracking for this project
        finish_pulling(str(dest))

        self.send_json({
            "success": True,
            "acai": True,
            "project_dir": str(dest),
            "modules_count": modules_count,
            "hooks_count": hooks_count,
            "uploads_count": uploads_count,
            "has_db": has_db,
            "steps": steps + git_steps,
            "errors": errors,
        })

    # ---- Pull Database ----

    def handle_pull_database(self, body):
        """POST /api/pull-database — dump remote DB and import into local Docker."""
        project_dir = body.get("path", "")
        if not project_dir:
            self.send_error_json("path is required")
            return

        path = sanitize_path(project_dir)
        if not path or not Path(path).is_dir():
            self.send_error_json("Invalid project directory")
            return

        dest = Path(path)
        steps = []
        errors = []

        # 1. Read remote DB credentials from .acai
        acai_file = dest / ".acai"
        if not acai_file.is_file():
            self.send_error_json("No se encontro .acai en el proyecto")
            return

        try:
            with open(str(acai_file), "r", encoding="utf-8") as f:
                acai_data = json.load(f)
        except Exception as e:
            self.send_error_json("Error leyendo .acai: {}".format(e))
            return

        db_host = acai_data.get("mysql_host", "")
        db_user = acai_data.get("mysql_user", "")
        db_name = acai_data.get("mysql_db", "")
        db_password = keychain_get("mysql") or ""

        if not all([db_host, db_user, db_name, db_password]):
            missing = []
            if not db_host:
                missing.append("mysql_host")
            if not db_user:
                missing.append("mysql_user")
            if not db_name:
                missing.append("mysql_db")
            if not db_password:
                missing.append("mysql password (keychain)")
            self.send_error_json("Faltan credenciales BD remota: {}".format(", ".join(missing)))
            return

        steps.append("Credenciales BD remota leidas")

        # 2. Verify local Docker DB container is running
        proj_name = dest.name.lower()
        proj_name = re.sub(r'[^a-z0-9_-]', '_', proj_name)
        db_container = "dw-{}-db".format(proj_name)

        rc_inspect, _, _ = run_cmd([
            "docker", "inspect", "--format", "{{.State.Running}}", db_container
        ])
        if rc_inspect != 0:
            self.send_error_json("Contenedor DB '{}' no encontrado. Levanta el proyecto primero.".format(db_container))
            return

        steps.append("Contenedor DB '{}' verificado".format(db_container))

        # 3. Read local DB credentials from .docker/.env
        env_file = dest / ".docker" / ".env"
        local_db = ""
        local_root_pass = ""
        if env_file.is_file():
            try:
                for line in env_file.read_text(encoding="utf-8").splitlines():
                    if line.startswith("DB_DATABASE="):
                        local_db = line.split("=", 1)[1]
                    elif line.startswith("DB_ROOT_PASSWORD="):
                        local_root_pass = line.split("=", 1)[1]
            except Exception:
                pass

        if not local_db or not local_root_pass:
            self.send_error_json("No se pudieron leer credenciales locales de .docker/.env")
            return

        # 4. Mysqldump from remote
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

            steps.append("Descargando base de datos remota...")
            rc, out, err = run_cmd(mysqldump_cmd, timeout=120)
            if rc != 0 or not out.strip():
                self.send_json({
                    "success": False,
                    "error": "mysqldump fallo: {}".format(err.strip()[:300] if err else "sin output"),
                    "steps": steps,
                })
                return

            # 5. Clean DEFINERs
            clean_dump = re.sub(
                r'/\*![0-9]+ DEFINER=`[^`]*`@`[^`]*`\*/',
                '',
                out,
            )

            # 6. Save as database.sql
            with open(str(dump_file), "w", encoding="utf-8") as f:
                f.write(clean_dump)
            steps.append("database.sql guardado ({:.1f} MB)".format(len(clean_dump) / 1024 / 1024))

        except Exception as e:
            self.send_json({
                "success": False,
                "error": "Error en mysqldump: {}".format(e),
                "steps": steps,
            })
            return

        # 7. Import into local Docker DB
        try:
            steps.append("Importando en contenedor local...")
            with open(str(dump_file), "r", encoding="utf-8") as sql_file:
                result = subprocess.run(
                    ["docker", "exec", "-i", db_container,
                     "mysql", "-uroot", "-p{}".format(local_root_pass), local_db],
                    stdin=sql_file,
                    capture_output=True,
                    text=True,
                    timeout=300,
                )
            if result.returncode != 0:
                errors.append("Import warning: {}".format(result.stderr.strip()[:300] if result.stderr else ""))
            steps.append("Base de datos importada en Docker local")
        except subprocess.TimeoutExpired:
            self.send_json({
                "success": False,
                "error": "Timeout importando BD (>5min)",
                "steps": steps,
            })
            return
        except Exception as e:
            self.send_json({
                "success": False,
                "error": "Error importando BD: {}".format(e),
                "steps": steps,
            })
            return

        self.send_json({
            "success": True,
            "steps": steps,
            "errors": errors,
        })

    # ---- Git / Gitea ----

    def handle_git_status(self, qs):
        """GET /api/git/status?path=... — return git status for a folder."""
        raw = qs.get("path", [""])[0]
        if not raw:
            self.send_error_json("path is required")
            return
        path = sanitize_path(raw)
        if not path:
            self.send_error_json("Invalid path")
            return
        git_dir = Path(path) / ".git"
        if not git_dir.is_dir():
            self.send_json({"has_git": False})
            return
        # Branch
        rc, branch_out, _ = run_cmd(["git", "-C", path, "rev-parse", "--abbrev-ref", "HEAD"], timeout=10)
        branch = branch_out.strip() if rc == 0 else "unknown"
        # Status
        rc, status_out, _ = run_cmd(["git", "-C", path, "status", "--porcelain"], timeout=10)
        clean = (rc == 0 and status_out.strip() == "")
        changed_files = [l.strip() for l in status_out.strip().splitlines() if l.strip()] if status_out.strip() else []
        # Log
        rc, log_out, _ = run_cmd(["git", "-C", path, "log", "--oneline", "-5"], timeout=10)
        last_commits = [l.strip() for l in log_out.strip().splitlines() if l.strip()] if rc == 0 else []
        # Ahead/behind
        ahead = 0
        behind = 0
        rc, ab_out, _ = run_cmd(["git", "-C", path, "rev-list", "--left-right", "--count", "HEAD...@{upstream}"], timeout=10)
        if rc == 0 and ab_out.strip():
            parts = ab_out.strip().split()
            if len(parts) == 2:
                try:
                    ahead = int(parts[0])
                    behind = int(parts[1])
                except ValueError:
                    pass
        self.send_json({
            "has_git": True,
            "branch": branch,
            "clean": clean,
            "ahead": ahead,
            "behind": behind,
            "last_commits": last_commits,
            "changed_files": changed_files[:20],
        })

    def handle_git_init(self, body):
        """POST /api/git/init — setup git on server first, then connect local."""
        path = body.get("path", "")
        domain = body.get("domain", "")
        if not path or not domain:
            self.send_error_json("path and domain are required")
            return
        safe = sanitize_path(path)
        if not safe:
            self.send_error_json("Invalid path")
            return
        config = load_config()
        gitea_password = keychain_get("gitea")
        if not config.get("gitea_url") or not gitea_password:
            self.send_error_json("Gitea no configurado. Ve a Ajustes > Git Sync.")
            return

        all_steps = []

        # 1. Setup git on server (giteaSetup: deletes repo, creates fresh, pushes)
        try:
            acai_marker = Path(safe) / ".acai"
            if acai_marker.is_file():
                server_body = dict(body)
                server_body["gitea_url"] = config.get("gitea_url", "")
                server_body["gitea_username"] = config.get("gitea_username", "")
                server_body["gitea_password"] = gitea_password
                server_body["gitea_org"] = config.get("gitea_org", "acai")
                result = self._server_git_proxy(server_body, "giteaSetup")
                if result and result.get("steps"):
                    all_steps.append("--- Servidor ---")
                    all_steps.extend(result["steps"])
                elif result and result.get("error"):
                    all_steps.append("Error servidor: {}".format(result["error"]))
        except Exception as e:
            all_steps.append("Error servidor: {}".format(e))

        # 2. Connect local repo (fetch from server's push)
        try:
            local_steps = _git_connect_repo(safe, domain, config, create_if_missing=True)
            all_steps.append("--- Local ---")
            all_steps.extend(local_steps)
            self.send_json({"success": True, "steps": all_steps})
        except Exception as e:
            self.send_json({"success": False, "error": str(e), "steps": all_steps})

    def _ensure_git_remote(self, safe):
        """Update origin remote URL from current config (handles password changes)."""
        config = load_config()
        if not config.get("gitea_url") or not config.get("gitea_username"):
            return
        # Detect domain from folder name
        domain = Path(safe).name
        remote_url = _git_remote_url(config, domain)
        run_cmd(["git", "-C", safe, "remote", "set-url", "origin", remote_url], timeout=10)

    @staticmethod
    def _git_branch(safe):
        """Return current branch name, defaulting to 'main'."""
        rc, out, _ = run_cmd(["git", "-C", safe, "rev-parse", "--abbrev-ref", "HEAD"], timeout=10)
        branch = out.strip() if rc == 0 and out.strip() and out.strip() != "HEAD" else ""
        return branch or "main"

    def handle_git_push(self, body):
        """POST /api/git/push — add, commit, push."""
        path = body.get("path", "")
        if not path:
            self.send_error_json("path is required")
            return
        safe = sanitize_path(path)
        if not safe:
            self.send_error_json("Invalid path")
            return
        self._ensure_git_remote(safe)
        steps = []
        # Check if there are changes
        rc, status_out, _ = run_cmd(["git", "-C", safe, "status", "--porcelain"], timeout=10)
        if rc == 0 and status_out.strip():
            run_cmd(["git", "-C", safe, "add", "."], timeout=30)
            # Ensure branch is "main" (handles fresh repos with no commits)
            run_cmd(["git", "-C", safe, "branch", "-M", "main"], timeout=10)
            rc, _, err = run_cmd(["git", "-C", safe, "commit", "-m", "Sync from local"], timeout=30)
            if rc == 0:
                steps.append("Commit creado")
            else:
                steps.append("Commit: {}".format(err[:100]))
        else:
            steps.append("Sin cambios locales")
        # Push
        branch = self._git_branch(safe)
        rc, out, err = run_cmd(["git", "-C", safe, "push", "-u", "origin", branch], timeout=60)
        if rc != 0 and ("403" in err or "not found" in err.lower() or "create" in err.lower()):
            # Repo might not exist yet — try to create it and retry
            config = load_config()
            org = config.get("gitea_org", "acai")
            domain = Path(safe).name
            repo_name = "sync-{}".format(domain)
            _gitea_api("POST", "/orgs/{}/repos".format(org), data={
                "name": repo_name,
                "private": True,
                "auto_init": False,
            })
            steps.append("Repo creado en Gitea")
            rc, out, err = run_cmd(["git", "-C", safe, "push", "-u", "origin", branch], timeout=60)
        if rc == 0:
            steps.append("Push OK")
        else:
            self.send_json({"success": False, "error": "Push: {}".format(err[:200]), "steps": steps})
            return
        self.send_json({"success": True, "steps": steps, "output": out})

    def handle_git_pull(self, body):
        """POST /api/git/pull — pull from remote."""
        path = body.get("path", "")
        if not path:
            self.send_error_json("path is required")
            return
        safe = sanitize_path(path)
        if not safe:
            self.send_error_json("Invalid path")
            return
        self._ensure_git_remote(safe)
        branch = self._git_branch(safe)
        steps = []
        # Auto-commit local changes so rebase can proceed cleanly
        rc_st, st_out, _ = run_cmd(["git", "-C", safe, "status", "--porcelain"], timeout=10)
        if rc_st == 0 and st_out.strip():
            run_cmd(["git", "-C", safe, "add", "."], timeout=30)
            rc_c, _, _ = run_cmd(["git", "-C", safe, "commit", "-m", "Sync from local (auto-commit before pull)"], timeout=30)
            if rc_c == 0:
                steps.append("Auto-commit de cambios locales")
        rc, out, err = run_cmd(["git", "-C", safe, "pull", "origin", branch, "--rebase"], timeout=60)
        if rc == 0:
            msg = "\n".join(steps + [out or "Already up to date."])
            self.send_json({"success": True, "output": msg})
        else:
            self.send_json({"success": False, "error": err[:300], "output": out})

    def handle_gitea_test(self, body):
        """POST /api/gitea/test — test Gitea connection using token auth."""
        gitea_url = body.get("gitea_url", "").rstrip("/")
        gitea_username = body.get("gitea_username", "")
        gitea_password = body.get("gitea_password", "")
        if not gitea_url or not gitea_username or not gitea_password:
            self.send_error_json("gitea_url, gitea_username, and gitea_password are required")
            return
        # First create a token via basic auth, or just test with basic auth
        url = "{}/api/v1/user".format(gitea_url)
        creds = base64.b64encode("{}:{}".format(gitea_username, gitea_password).encode()).decode()
        req = urllib.request.Request(
            url,
            headers={
                "Authorization": "Basic {}".format(creds),
                "Content-Type": "application/json",
            },
            method="GET",
        )
        try:
            with urllib.request.urlopen(req, timeout=10) as resp:
                data = json.loads(resp.read().decode())
            self.send_json({
                "success": True,
                "user": data.get("login", data.get("username", "")),
                "full_name": data.get("full_name", ""),
            })
        except urllib.error.HTTPError as e:
            self.send_json({"success": False, "error": "HTTP {}: Auth failed".format(e.code)})
        except Exception as e:
            self.send_json({"success": False, "error": str(e)})

    # ---- Server Git proxy endpoints ----

    def _server_git_proxy(self, body, action_ws):
        """Proxy a git action to the remote server's ws_respond.php."""
        domain = body.get("domain", "")
        if not domain:
            self.send_error_json("domain is required")
            return None
        # Read token from .acai file
        webs_dir = get_webs_dir()
        acai_file = webs_dir / domain / ".acai"
        if not acai_file.is_file():
            self.send_error_json("No .acai file found for {}".format(domain))
            return None
        try:
            acai_data = json.loads(acai_file.read_text(encoding="utf-8"))
        except Exception:
            self.send_error_json("Invalid .acai file")
            return None

        payload = {
            "action_ws": action_ws,
            "token": acai_data.get("token", ""),
            "tokenHash": acai_data.get("tokenHash", ""),
        }
        payload.update({k: v for k, v in body.items() if k not in ("domain",)})
        ssl_enabled = acai_data.get("ssl", True)
        return acai_web_request(domain, ssl_enabled, payload, timeout=60)

    def handle_server_git_setup(self, body):
        """POST /api/server-git/setup — create Gitea repo + setup git on server."""
        domain = body.get("domain", "")
        if not domain:
            self.send_error_json("domain is required")
            return

        config = load_config()
        gitea_password = keychain_get("gitea")
        if not config.get("gitea_url") or not config.get("gitea_username") or not gitea_password:
            self.send_error_json("Gitea no configurado. Ve a Ajustes > Git Sync.")
            return

        # 1. Delete + recreate repo in Gitea (clean start)
        org = config.get("gitea_org", "acai")
        repo_name = "sync-{}".format(domain)
        steps = []
        check = _gitea_api("GET", "/repos/{}/{}".format(org, repo_name))
        if check.get("id"):
            _gitea_api("DELETE", "/repos/{}/{}".format(org, repo_name))
            steps.append("Repo anterior eliminado")
        repo_result = _gitea_api("POST", "/orgs/{}/repos".format(org), data={
            "name": repo_name,
            "private": True,
            "auto_init": False,
        })
        if repo_result.get("id"):
            steps.append("Repo creado: {}/{}".format(org, repo_name))
        else:
            err_msg = repo_result.get("error") or repo_result.get("message", "unknown")
            self.send_json({"success": False, "error": "Error creando repo: {}".format(err_msg)})
            return

        # 2. Inject Gitea creds and call server
        body["gitea_url"] = config.get("gitea_url", "")
        body["gitea_username"] = config.get("gitea_username", "")
        body["gitea_password"] = gitea_password
        body["gitea_org"] = org

        result = self._server_git_proxy(body, "giteaSetup")
        if result is None:
            return
        if result.get("error"):
            self.send_json({"success": False, "error": result["error"], "steps": steps})
            return
        server_steps = result.get("steps", [])
        steps += server_steps

        # 3. Connect local repo to the Gitea repo (don't recreate — server already pushed)
        webs_dir = Path(load_config().get("webs_dir", "~/webs")).expanduser()
        local_path = webs_dir / domain
        if local_path.is_dir():
            # Remove stale .git so we fetch fresh from server's push
            git_dir = local_path / ".git"
            if git_dir.is_dir():
                import shutil
                shutil.rmtree(str(git_dir))
                steps.append("Local .git eliminado")
            try:
                local_steps = _git_connect_repo(str(local_path), domain, config, create_if_missing=False)
                steps += ["--- Local ---"] + local_steps
            except Exception as e:
                steps.append("Error local: {}".format(e))

        self.send_json({"success": True, "steps": steps})

    def handle_server_git_status(self, body):
        """POST /api/server-git/status — get git status from server."""
        result = self._server_git_proxy(body, "gitSyncStatus")
        if result is not None:
            self.send_json(result)

    def handle_server_git_push(self, body):
        """POST /api/server-git/push — commit + push on server."""
        result = self._server_git_proxy(body, "gitSyncPush")
        if result is not None:
            self.send_json(result)

    def handle_server_git_pull(self, body):
        """POST /api/server-git/pull — pull on server."""
        result = self._server_git_proxy(body, "gitSyncPull")
        if result is not None:
            self.send_json(result)

    # ---- Migration ----

    def handle_local_webs_migrate(self, body):
        """POST /api/local-webs/migrate — migrate from flat to server structure."""
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

        steps = []
        try:
            # modulos/ → template/estandar/modulos/
            old_modulos = p / "modulos"
            new_modulos = p / "template" / "estandar" / "modulos"
            if old_modulos.is_dir() and not new_modulos.is_dir():
                new_modulos.parent.mkdir(parents=True, exist_ok=True)
                old_modulos.rename(new_modulos)
                steps.append("modulos/ -> template/estandar/modulos/")

            # plugins/ → cms/lib/plugins/
            old_plugins = p / "plugins"
            new_plugins = p / "cms" / "lib" / "plugins"
            if old_plugins.is_dir() and not new_plugins.is_dir():
                new_plugins.parent.mkdir(parents=True, exist_ok=True)
                old_plugins.rename(new_plugins)
                steps.append("plugins/ -> cms/lib/plugins/")

            # layout.json → cms/lib/plugins/builder_saas/layout.json
            old_layout = p / "layout.json"
            new_layout = p / "cms" / "lib" / "plugins" / "builder_saas" / "layout.json"
            if old_layout.is_file() and not new_layout.is_file():
                new_layout.parent.mkdir(parents=True, exist_ok=True)
                old_layout.rename(new_layout)
                steps.append("layout.json -> cms/lib/plugins/builder_saas/layout.json")

            # uploads/ → cms/uploads/
            old_uploads = p / "uploads"
            new_uploads = p / "cms" / "uploads"
            if old_uploads.is_dir() and not new_uploads.is_dir():
                new_uploads.parent.mkdir(parents=True, exist_ok=True)
                old_uploads.rename(new_uploads)
                steps.append("uploads/ -> cms/uploads/")

            # Rewrite .gitignore
            _write_gitignore(str(p))
            steps.append(".gitignore actualizado")

            self.send_json({"success": True, "steps": steps})
        except Exception as e:
            self.send_json({"success": False, "error": str(e), "steps": steps})


# ---- Auto-reload ----

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
    start_module_watcher(get_projects_fn=get_projects)
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
