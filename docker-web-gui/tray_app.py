#!/usr/bin/env python3
"""
Docker Web GUI — macOS menu bar app.
Uses rumps to create a tray icon that manages the local HTTP server.
"""

import os
import subprocess
import sys
import threading
import webbrowser

# Ensure common macOS paths are in PATH (py2app bundles have a minimal PATH)
_extra_paths = "/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin"
os.environ["PATH"] = _extra_paths + ":" + os.environ.get("PATH", "")

import rumps

from server import DEFAULT_PORT, start_server


def _resource_path(filename):
    """Resolve resource path, handling py2app bundles."""
    if getattr(sys, 'frozen', False):
        return os.path.join(os.path.dirname(sys.executable), '..', 'Resources', filename)
    return os.path.join(os.path.dirname(__file__), filename)


class DockerWebGUIApp(rumps.App):
    def __init__(self):
        icon_path = _resource_path("icon_menu.png")
        super().__init__(
            name="Docker Web GUI",
            icon=icon_path if os.path.exists(icon_path) else None,
            title=None if os.path.exists(icon_path) else "\U0001F433",
            template=True,
            quit_button=None,
        )
        self.server = None
        self.port = DEFAULT_PORT
        self.status_item = rumps.MenuItem("Estado: Iniciando...")
        self.status_item.set_callback(None)
        self.menu = [
            rumps.MenuItem("Abrir GUI", callback=self.open_gui),
            self.status_item,
            None,  # separator
            rumps.MenuItem("Quit", callback=self.quit_app),
        ]
        # Start server in background thread
        threading.Thread(target=self._start_server, daemon=True).start()

    def _start_server(self):
        try:
            self.server, self.port = start_server(DEFAULT_PORT)
            self.status_item.title = "Estado: Activo \u2713 (:{})".format(self.port)
            self.server.serve_forever()
        except Exception as e:
            self.status_item.title = "Estado: Error"
            rumps.notification(
                title="Docker Web GUI",
                subtitle="Error",
                message=str(e),
            )

    def open_gui(self, _):
        webbrowser.open("http://localhost:{}".format(self.port))

    def quit_app(self, _):
        if self.server:
            # Shutdown in a thread to avoid blocking the main loop
            threading.Thread(target=self.server.shutdown, daemon=True).start()
        rumps.quit_application()
        # Force exit if rumps doesn't quit cleanly
        os._exit(0)


if __name__ == "__main__":
    DockerWebGUIApp().run()
