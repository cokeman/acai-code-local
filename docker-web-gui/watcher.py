"""
Module watcher, cola de módulos y sincronización para Docker Web GUI.
"""

import json
import re
import sys
import threading
import time
import urllib.request
from pathlib import Path

from config import get_webs_dir


# ---- Pull suppression ----
# Two-phase mechanism to avoid processing files extracted during a pull:
#
# 1. mark_pulling(dir)  — called at pull START. While active, the watcher
#    filters out changes from this directory (handles slow pulls that span
#    multiple watcher cycles).
#
# 2. finish_pulling(dir) — called at pull END. Scans the project's current
#    module file mtimes and injects them into _force_absorb. The watcher
#    merges these into its own mtimes dict BEFORE computing changes, so the
#    extracted files are "already known" and only genuine user edits (with a
#    newer mtime) are detected.

_pulling_dirs = set()
_pulling_lock = threading.Lock()

_force_absorb = {}   # {filepath: mtime} — merged into watcher mtimes
_force_absorb_lock = threading.Lock()


def mark_pulling(project_dir):
    """Mark a project as 'being pulled' so the watcher ignores its changes."""
    # Use the same base path as the watcher (get_webs_dir / name) so that
    # the startswith() filter in the watcher loop actually matches.
    project_name = Path(project_dir).name
    watcher_path = str(get_webs_dir() / project_name)
    with _pulling_lock:
        _pulling_dirs.add(watcher_path)


def finish_pulling(project_dir):
    """End pull suppression and absorb current file state into the watcher.

    Scans the project's module files and injects their mtimes so the watcher
    already "knows" them. Only files modified AFTER this point will be
    detected as changes.
    """
    # Use the same base path as the watcher (get_webs_dir / name) so that
    # the file path keys match exactly.
    project_name = Path(project_dir).name
    watcher_path = str(get_webs_dir() / project_name)
    # Scan current mtimes using the watcher-compatible path
    current = _scan_modules_mtimes([watcher_path])
    _watcher_log_add("info", "Pull done: absorbed {} module files for {}".format(
        len(current), project_name))
    # Inject into force_absorb BEFORE removing from pulling — this way if a
    # watcher cycle runs between these two operations, the pulling filter
    # still protects us, and the absorb is ready for the next cycle.
    with _force_absorb_lock:
        _force_absorb.update(current)
    with _pulling_lock:
        _pulling_dirs.discard(watcher_path)


# ---- Watcher log buffer ----

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


# ---- Module scanning ----

def _scan_modules_mtimes(web_dirs):
    """Scan modulos/ dirs of all web directories and return {filepath: mtime}."""
    mtimes = {}
    for proj_dir in web_dirs:
        modulos_dir = Path(proj_dir) / "template" / "estandar" / "modulos"
        if not modulos_dir.is_dir():
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


# ---- Module queue ----

def _queue_file(project_dir):
    """Return the path to the module queue file for a project."""
    return Path(project_dir) / ".module-queue.json"


def _queue_add(project_dir, module_name):
    """Add a module name to the project's pending queue (no duplicates)."""
    qf = _queue_file(project_dir)
    queue = []
    try:
        if qf.exists():
            queue = json.loads(qf.read_text(encoding="utf-8"))
    except (json.JSONDecodeError, OSError):
        queue = []
    if module_name not in queue:
        queue.append(module_name)
    try:
        qf.write_text(json.dumps(queue, ensure_ascii=False), encoding="utf-8")
    except OSError as e:
        _watcher_log_add("error", "Queue write error: {}".format(e))


def _queue_read(project_dir):
    """Read the list of pending module names for a project."""
    qf = _queue_file(project_dir)
    try:
        if qf.exists():
            return json.loads(qf.read_text(encoding="utf-8"))
    except (json.JSONDecodeError, OSError):
        pass
    return []


def _queue_clear(project_dir):
    """Clear the module queue for a project."""
    qf = _queue_file(project_dir)
    try:
        if qf.exists():
            qf.unlink()
    except OSError:
        pass


# ---- Module sync ----

def _process_queue(project_dir, web_url):
    """Process all queued modules for a project that just started."""
    queue = _queue_read(project_dir)
    if not queue:
        return
    _watcher_log_add("info", "Processing module queue ({} pending) for {}".format(
        len(queue), Path(project_dir).name))
    modulos_dir = Path(project_dir) / "template" / "estandar" / "modulos"
    processed = 0
    for mod_name in queue:
        mod_dir = modulos_dir / mod_name
        if mod_dir.is_dir():
            _sync_module_to_local(mod_dir, web_url)
            processed += 1
        else:
            _watcher_log_add("warning", "Queued module dir not found: {}".format(mod_name))
    _queue_clear(project_dir)
    _watcher_log_add("info", "Queue processed: {}/{} modules synced for {}".format(
        processed, len(queue), Path(project_dir).name))


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


def _on_module_changed(changed_files, running_projects):
    """Detect changed modules and sync them to their local Docker containers if running."""
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

    # Map running project dirs to web URLs (only running projects can receive syncs)
    proj_map = {}
    for proj in running_projects:
        pdir = proj.get("project_dir", "")
        web_url = proj.get("web_url", "")
        if pdir and web_url:
            proj_map[pdir] = web_url

    # Resolve webs_dir for extracting project dirs from file paths
    webs_dir = get_webs_dir()

    # Sync each changed module to its container, or queue if not running
    for mod_dir_str, mod_dir in changed_modules.items():
        synced = False
        for pdir, web_url in proj_map.items():
            if mod_dir_str.startswith(pdir):
                _sync_module_to_local(mod_dir, web_url)
                synced = True
                break
        if not synced:
            # Project not running — extract project_dir and queue the module
            try:
                rel = Path(mod_dir_str).relative_to(webs_dir)
                project_name = rel.parts[0]
                project_dir = str(webs_dir / project_name)
                mod_name = mod_dir.name
                _queue_add(project_dir, mod_name)
                _watcher_log_add("info", "Queued (offline): {} for {}".format(
                    mod_name, project_name))
            except (ValueError, IndexError):
                pass


# ---- Main watcher loop ----

def start_module_watcher(interval=2, get_projects_fn=None):
    """Start a background thread that watches modulos/ in all downloaded webs.

    get_projects_fn: callable that returns the list of running projects.
    Must be provided by the caller to avoid circular imports.
    """
    def _watch_loop():
        mtimes = {}
        while True:
            time.sleep(interval)
            try:
                # Scan ALL webs, not just running ones
                webs_dir = get_webs_dir()
                if not webs_dir.is_dir():
                    continue
                web_dirs = [str(item) for item in webs_dir.iterdir()
                            if item.is_dir() and not item.name.startswith(".")]
                running_projects = get_projects_fn() if get_projects_fn else []
            except Exception:
                continue

            try:
                new_mtimes = _scan_modules_mtimes(web_dirs)

                # Merge mtimes injected by finish_pulling(): set mtimes for
                # absorbed files to their current value so the diff sees no
                # change.  Both finish_pulling and the watcher use get_webs_dir()
                # paths, so keys match directly.
                with _force_absorb_lock:
                    if _force_absorb:
                        absorbed = dict(_force_absorb)
                        _force_absorb.clear()
                        mtimes.update(absorbed)
                        _watcher_log_add("info",
                            "Watcher absorbed {} files from pull".format(len(absorbed)))

                if mtimes:
                    changed = [
                        f for f in new_mtimes
                        if new_mtimes.get(f) != mtimes.get(f)
                    ]
                    # Also detect new files
                    new_files = [f for f in new_mtimes if f not in mtimes]
                    all_changed = list(set(changed + new_files))
                    if all_changed:
                        _watcher_log_add("info",
                            "Detected {} changes (before pull filter)".format(len(all_changed)))
                        # Filter out files from projects still being pulled
                        with _pulling_lock:
                            pulling = set(_pulling_dirs)
                        if pulling:
                            before = len(all_changed)
                            all_changed = [f for f in all_changed
                                           if not any(f.startswith(d) for d in pulling)]
                            if before != len(all_changed):
                                _watcher_log_add("info",
                                    "Pull filter: {} -> {} (pulling: {})".format(
                                        before, len(all_changed),
                                        [Path(d).name for d in pulling]))
                        if all_changed:
                            _on_module_changed(all_changed, running_projects)
                mtimes = new_mtimes
            except Exception as e:
                _watcher_log_add("error", "Watcher loop error: {}".format(e))

    t = threading.Thread(target=_watch_loop, daemon=True)
    t.start()
    print("Module watcher active (interval={}s)".format(interval))
    sys.stdout.flush()
    return t
