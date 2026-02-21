"""
py2app setup for Docker Web GUI.
Usage: python3 setup.py py2app
"""

import os

from setuptools import setup

APP = ["tray_app.py"]
import glob as _glob

# Include dist/ recursively (Vue build output)
_dist_files = []
for _root, _dirs, _files in os.walk("dist"):
    if _files:
        _dist_files.append((_root, [os.path.join(_root, f) for f in _files]))

DATA_FILES = [("", ["icon_menu.png", "../docker-web.sh"])] + _dist_files
OPTIONS = {
    "argv_emulation": False,
    "includes": ["server", "config", "acai_api", "git_ops", "watcher"],
    "plist": {
        "CFBundleName": "Docker Web GUI",
        "CFBundleDisplayName": "Docker Web GUI",
        "CFBundleIdentifier": "com.docker-web-gui.app",
        "CFBundleVersion": "1.0.0",
        "CFBundleShortVersionString": "1.0.0",
        "LSUIElement": True,
    },
}

if os.path.exists("icon.png"):
    OPTIONS["iconfile"] = "icon.png"

setup(
    name="Docker Web GUI",
    app=APP,
    data_files=DATA_FILES,
    options={"py2app": OPTIONS},
    setup_requires=["py2app"],
)
