"""
API Gitea, operaciones git y gitignore para Docker Web GUI.
"""

import base64
import json
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

from config import keychain_get, load_config, run_cmd


def _gitea_api(method, path, token=None, data=None):
    """Call Gitea REST API using Basic auth. Returns dict with response or {"error": "..."}."""
    config = load_config()
    base = config.get("gitea_url", "").rstrip("/")
    if not base:
        return {"error": "gitea_url not configured"}
    url = "{}/api/v1{}".format(base, path)
    body = json.dumps(data).encode() if data else None
    # Use Basic auth with username + password from keychain
    username = config.get("gitea_username", "")
    password = keychain_get("gitea") or token or ""
    creds = base64.b64encode("{}:{}".format(username, password).encode()).decode()
    req = urllib.request.Request(
        url,
        data=body,
        headers={
            "Content-Type": "application/json",
            "Authorization": "Basic {}".format(creds),
        },
        method=method,
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            return json.loads(resp.read().decode())
    except urllib.error.HTTPError as e:
        err_body = e.read().decode()
        try:
            return json.loads(err_body)
        except json.JSONDecodeError:
            return {"error": "HTTP {}: {}".format(e.code, err_body[:200])}
    except Exception as e:
        return {"error": str(e)}


ACAI_GITIGNORE = """\
/*
!hooks/
!template/
template/*
!template/estandar/
template/estandar/*
!template/estandar/modulos/
!cms/
cms/*
!cms/lib/
cms/lib/*
!cms/lib/plugins/
cms/uploads/
**/minified/
.docker/
.acai
database.sql
*.sql.gz
node_modules/
.DS_Store
Thumbs.db
.module-queue.json
"""


def _write_gitignore(dest):
    """Write standard Acai .gitignore (always overwrites to keep in sync)."""
    gi = Path(dest) / ".gitignore"
    gi.write_text(ACAI_GITIGNORE, encoding="utf-8")
    return str(gi)


def _git_remote_url(config, domain):
    """Build the Gitea remote URL with embedded credentials."""
    gitea_url = config.get("gitea_url", "")
    username = config.get("gitea_username", "")
    token = keychain_get("gitea") or ""
    org = config.get("gitea_org", "acai")
    # URL-encode credentials to handle special chars (:, @, etc.)
    safe_user = urllib.parse.quote(username, safe="")
    safe_token = urllib.parse.quote(token, safe="")
    # Parse host from URL
    parsed = urllib.parse.urlparse(gitea_url)
    host = parsed.netloc or parsed.path
    scheme = parsed.scheme or "http"
    repo_name = "sync-{}".format(domain)
    return "{}://{}:{}@{}/{}/{}.git".format(scheme, safe_user, safe_token, host, org, repo_name)


def _git_connect_repo(dest, domain, config, create_if_missing=False):
    """Connect a local folder to its Gitea repo.

    If create_if_missing=False (default, used during auto-pull):
      - Only connects if the repo already exists in Gitea
      - If repo doesn't exist, does nothing (user must click "Iniciar Git")

    If create_if_missing=True (used when user clicks "Iniciar Git"):
      - Creates the repo in Gitea if it doesn't exist

    Returns list of step messages.
    """
    steps = []
    password = keychain_get("gitea")
    if not password:
        return ["Sin token Gitea configurado"]

    dest = str(dest)
    org = config.get("gitea_org", "acai")
    repo_name = "sync-{}".format(domain)

    # 1. Check if repo exists in Gitea
    check = _gitea_api("GET", "/repos/{}/{}".format(org, repo_name))
    repo_exists = bool(check.get("id"))

    if not repo_exists and not create_if_missing:
        # Repo doesn't exist and we shouldn't create it — skip git setup
        steps.append("Repo {}/{} no existe en Gitea (usa 'Iniciar Git' para crearlo)".format(org, repo_name))
        return steps

    if not repo_exists and create_if_missing:
        # Create repo
        repo_result = _gitea_api("POST", "/orgs/{}/repos".format(org), data={
            "name": repo_name,
            "private": True,
            "auto_init": False,
        })
        if repo_result.get("id"):
            steps.append("Repo creado: {}/{}".format(org, repo_name))
            repo_exists = True
        else:
            err_msg = repo_result.get("error") or repo_result.get("message", "unknown")
            steps.append("Error creando repo: {}".format(err_msg))
            return steps
    else:
        steps.append("Repo existente: {}/{}".format(org, repo_name))

    # 2. gitignore
    _write_gitignore(dest)
    steps.append(".gitignore escrito")

    # 3. git init
    rc, _, err = run_cmd(["git", "-C", dest, "init"], timeout=15)
    if rc != 0:
        steps.append("git init error: {}".format(err[:100]))
        return steps
    steps.append("git init")

    # 4. Add remote
    remote_url = _git_remote_url(config, domain)
    run_cmd(["git", "-C", dest, "remote", "remove", "origin"], timeout=10)
    run_cmd(["git", "-C", dest, "remote", "add", "origin", remote_url], timeout=10)

    # 5. Fetch
    rc, _, err = run_cmd(["git", "-C", dest, "fetch", "origin"], timeout=60)
    if rc != 0:
        steps.append("fetch error: {}".format(err[:100]))
        return steps
    steps.append("fetch OK")

    # 6. Check if remote has a main branch with commits
    rc_check, _, _ = run_cmd(["git", "-C", dest, "rev-parse", "origin/main"], timeout=10)
    remote_has_commits = (rc_check == 0)

    if remote_has_commits:
        # Remote has history — align local to match it while keeping working tree files
        run_cmd(["git", "-C", dest, "checkout", "-b", "main"], timeout=10)
        rc, _, err = run_cmd(["git", "-C", dest, "reset", "origin/main"], timeout=15)
        if rc == 0:
            steps.append("Historial alineado con origin/main")
        else:
            steps.append("reset error: {}".format(err[:100]))
        # Set upstream tracking
        run_cmd(["git", "-C", dest, "branch", "--set-upstream-to=origin/main", "main"], timeout=10)
        rc, status_out, _ = run_cmd(["git", "-C", dest, "status", "--porcelain"], timeout=10)
        if rc == 0:
            changed = len([l for l in status_out.strip().splitlines() if l.strip()]) if status_out.strip() else 0
            if changed > 0:
                steps.append("{} archivos locales difieren del repo".format(changed))
            else:
                steps.append("Archivos locales coinciden con el repo")
    else:
        # Remote is empty — initial commit + push
        run_cmd(["git", "-C", dest, "add", "."], timeout=30)
        run_cmd(["git", "-C", dest, "branch", "-M", "main"], timeout=10)
        run_cmd(["git", "-C", dest, "commit", "-m", "Initial commit"], timeout=30)
        rc, _, err = run_cmd(["git", "-C", dest, "push", "-u", "origin", "main"], timeout=60)
        if rc == 0:
            steps.append("Push inicial OK")
        else:
            steps.append("Push error: {}".format(err[:100]))

    return steps
