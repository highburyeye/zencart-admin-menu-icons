#!/usr/bin/env python3
"""Build the install ZIP without tests, fixtures, screenshots or development files."""
import argparse
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED

root = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser()
parser.add_argument('--version', default='v1.1.4')
args = parser.parse_args()
plugin = root / 'zc_plugins' / 'AdminMenuIcons' / args.version
if not plugin.is_dir() or not (plugin / 'manifest.php').is_file():
    parser.error('Version folder with manifest.php not found')
files = [root / name for name in ['README.md', 'LICENSE', 'CHANGELOG.md']]
files += sorted(path for path in plugin.rglob('*') if path.is_file())
destination = root / 'dist' / ('admin-menu-icons-' + args.version + '.zip')
destination.parent.mkdir(exist_ok=True)
with ZipFile(destination, 'w', ZIP_DEFLATED, compresslevel=9) as archive:
    for path in files:
        info = ZipInfo(path.relative_to(root).as_posix(), date_time=(2026, 11, 15, 0, 0, 0))
        info.compress_type = ZIP_DEFLATED
        info.external_attr = 0o100644 << 16
        archive.writestr(info, path.read_bytes())
with ZipFile(destination) as archive:
    assert archive.testzip() is None
    assert all(not name.startswith(('tests/', 'tools/', '.git')) for name in archive.namelist())
print(destination)
