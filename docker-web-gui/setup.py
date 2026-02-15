"""
py2app setup for Docker Web GUI.
Usage: python3 setup.py py2app
"""

import os

from setuptools import setup

APP = ["tray_app.py"]
DATA_FILES = [("", ["index.html", "icon_menu.png"])]
OPTIONS = {
    "argv_emulation": False,
    "includes": ["server"],
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
