"""
Log buffer compartido para Docker Web GUI.

Anteriormente contenía el module watcher y la sincronización de módulos.
La compilación de módulos se gestiona ahora desde la extensión de VSCode.
"""

import sys
import threading
import time


# ---- Log buffer ----

_watcher_log = []  # [{ts, level, msg}, ...]
_watcher_log_lock = threading.Lock()
MAX_WATCHER_LOG = 200


def _watcher_log_add(level, msg):
    """Add entry to log buffer. level: info|error|warn"""
    with _watcher_log_lock:
        _watcher_log.append({
            "ts": time.time(),
            "level": level,
            "msg": msg,
        })
        if len(_watcher_log) > MAX_WATCHER_LOG:
            _watcher_log[:] = _watcher_log[-MAX_WATCHER_LOG:]
    print("[log] {}".format(msg))
    sys.stdout.flush()
