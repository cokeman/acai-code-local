#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

echo "==> Installing dependencies..."
pip3 install rumps py2app

# Generate icon if missing and Pillow is available
if [ ! -f icon.png ]; then
    echo "==> Generating icon..."
    python3 -c "
from PIL import Image, ImageDraw, ImageFont
img = Image.new('RGBA', (256, 256), (0, 0, 0, 0))
draw = ImageDraw.Draw(img)
# Blue circle background
draw.ellipse([16, 16, 240, 240], fill=(0, 122, 204))
# White whale emoji text
try:
    font = ImageFont.truetype('/System/Library/Fonts/Apple Color Emoji.ttc', 120)
except Exception:
    font = ImageFont.load_default()
draw.text((128, 128), '\U0001F433', font=font, anchor='mm')
img.save('icon.png')
print('Icon generated: icon.png')
" 2>/dev/null || echo "   (Pillow not available, using text-only menu bar icon)"
fi

echo "==> Cleaning previous build..."
rm -rf build dist

echo "==> Building app..."
python3 setup.py py2app

echo ""
echo "Done! App is at: dist/Docker Web GUI.app"
echo "To install: cp -r 'dist/Docker Web GUI.app' /Applications/"
