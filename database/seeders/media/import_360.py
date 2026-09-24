#!/usr/bin/env python3
"""
Tải bộ ảnh xoay 360° (18 khung/màu) từ trình xem màu của lexus.com (Mỹ).

    python3 database/seeders/media/import_360.py

Nguồn: máy chủ ảnh Toyota Motor North America, cùng nguồn với bộ RX
Trắng/Đen/Đồng có từ 23/09/2026. Đường dẫn:
  …/lexus/images/models/{xe}/{năm}/{visualizer}/{bản}/exterior/{mâm}/{màu}/large-{1..18}.jpg
Lấy từ trình xem màu trên https://www.lexus.com/models/{XE} (24/09/2026).

LƯU Ý: đây là xe bản Mỹ — mâm, logo phiên bản, chi tiết có thể khác xe Việt
Nam; ảnh dùng để minh họa màu và dáng xe. Bản quyền thuộc Lexus.
LM, LS không có trên lexus.com (LM không bán ở Mỹ, LS 2026 không còn trình
xem) → lấy từ nguồn chính thức khác, xem OTHER_SETS:
  · LM: trình cấu hình Lexus Anh (images.lexus-europe.com), 36 góc × 10°,
    xe tay lái nghịch bản Anh.
  · LS: trình xem 360° Lexus Nhật (lexus.jp), 12 góc × 30°, ảnh 640×480,
    xe bản Nhật.
  · NX Xanh dương đậm (Heat Blue 8X1, màu riêng F SPORT — Mỹ không có):
    trình xem 360° Lexus Nhật, 12 góc × 30°, ảnh 640×480.

Ra: database/seeders/media/lexus/{xe}/360/{khoá màu}/01..18.webp
Khoá màu trùng khoá trong Brands\\LexusSeeder (xem seedColors()). Seeder tự
ưu tiên bộ 360° này hơn bộ nhiều góc của Car-project.
"""
import subprocess
import sys
import tempfile
import time
import urllib.request
from pathlib import Path

HERE = Path(__file__).resolve().parent
OUT = HERE / 'lexus'
BASE = 'https://tmna.assetscs.toyota.com/is/image/lexusaemcs/lexus/images/models'
# Cắt giống hệt trình xem của lexus.com: bỏ phần phòng chụp thừa quanh xe.
QUERY = '?extend=-17,-585,-17,-585&hei=628&wid=1440&qlt=95'

# xe → (đường dẫn bản + mâm, {khoá màu của site: slug màu bên Mỹ})
SETS = {
    'rx': ('rx/2026/visualizer/350-premium/exterior/19-in-five-spoke-alloy-wheels-with-dark-gray-metallic-and-machined-finish', {
        'xanh': 'nori-green-pearl', 'xam': 'iridium', 'do': 'matador-red-mica', 'xanh-duong': 'nightfall-mica',
    }),
    'es': ('es/2026/visualizer/350e/exterior/19-in-alloy-wheels-with-gray-metallic-finish-and-black-aero-covers', {
        'trang': 'ultra-white', 'bac': 'iridium', 'xam': 'cloudburst-gray',
        'xanh-duong': 'wavelength', 'dong': 'copper-crest', 'den': 'caviar',
    }),
    'nx': ('nx/2026/visualizer-1/350/exterior/18-in-15-spoke-alloy-wheels-with-gray-metallic-and-machined-finish', {
        'xam': 'cloudburst-gray', 'trang': 'ultra-white', 'do': 'infrared',
        'xanh-reu': 'nori-green-pearl', 'den': 'caviar',
    }),
    'gx': ('gx/2026/visualizer/premium/exterior/20-in-six-twin-spoke-alloy-wheels-with-dark-gray-metallic-finish', {
        'xam': 'nebula-gray-pearl', 'den': 'caviar', 'trang': 'eminent-white-pearl', 'xanh': 'nori-green-pearl',
    }),
    'lx': ('lx/2026/visualizer/gas/exterior/22-in-forged-alloy-wheels-with-machined-finish', {
        'den': 'caviar', 'trang': 'eminent-white-pearl', 'xam': 'manganese-luster', 'xanh': 'nori-green-pearl',
    }),
}
FRAMES = 18

# Nguồn khác lexus.com: xe → {khoá màu: [url khung 1, khung 2, …]}.
LM_EU = ('https://images.lexus-europe.com/gb/product-token/eec7f472-f2be-43bc-899d-051f6e2bc66a'
         '/vehicle/867e6a9c-ed55-496a-af55-7e2cd367106f/width/1440/height/628/scale-mode/1/padding/10'
         '/background-colour/EAE8E3/image-quality/90/day-exterior-{n}_{c}_LA21.webp')
JP = 'https://lexus.jp/models/{m}/images/features/exterior/tcv/{c}/{c}_{d}.png'
OTHER_SETS = {
    'lm': {key: [LM_EU.format(n=n, c=code) for n in range(36)]
           for key, code in {'trang': '085', 'den': '223', 'xam': '1J7', 'do': '3U3'}.items()},
    'ls': {key: [JP.format(m='ls', c=code, d=d) for d in range(0, 360, 30)]
           for key, code in {'trang': '083', 'den': '223', 'bac': '1L2', 'do': '3U3', 'xanh': '8X5'}.items()},
    'nx': {'xanh-duong': [JP.format(m='nx', c='8X1', d=d) for d in range(0, 360, 30)]},
}


def fetch(url, dest):
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (LexusThangLong media import)'})
    with urllib.request.urlopen(req, timeout=30) as res, open(dest, 'wb') as fh:
        fh.write(res.read())


def main():
    failed = []
    total = 0
    with tempfile.TemporaryDirectory() as tmp:
        for car, (path, colours) in SETS.items():
            for key, slug in colours.items():
                target = OUT / car / '360' / key
                target.mkdir(parents=True, exist_ok=True)
                for n in range(1, FRAMES + 1):
                    dest = target / f'{n:02d}.webp'
                    if dest.is_file():
                        continue  # chạy lại không tải lại
                    raw = Path(tmp) / f'{car}-{key}-{n}.jpg'
                    try:
                        fetch(f'{BASE}/{path}/{slug}/large-{n}.jpg{QUERY}', raw)
                        subprocess.run(['cwebp', '-quiet', '-q', '82', '-metadata', 'none', str(raw), '-o', str(dest)], check=True)
                        total += 1
                    except Exception as exc:  # noqa: BLE001
                        failed.append(f'{car}/{key}/{n}: {exc}')
                    time.sleep(0.15)  # lịch sự với máy chủ nguồn
                print(f'{car}/{key} ← {slug}: xong')
        for car, colours in OTHER_SETS.items():
            for key, urls in colours.items():
                target = OUT / car / '360' / key
                target.mkdir(parents=True, exist_ok=True)
                for n, url in enumerate(urls, 1):
                    dest = target / f'{n:02d}.webp'
                    if dest.is_file():
                        continue
                    raw = Path(tmp) / f'{car}-{key}-{n}{Path(url).suffix}'
                    try:
                        fetch(url, raw)
                        subprocess.run(['cwebp', '-quiet', '-q', '85', '-alpha_q', '100', '-metadata', 'none', str(raw), '-o', str(dest)], check=True)
                        total += 1
                    except Exception as exc:  # noqa: BLE001
                        failed.append(f'{car}/{key}/{n}: {exc}')
                    time.sleep(0.15)
                print(f'{car}/{key}: xong ({len(urls)} khung)')
    print('Đã tải', total, 'ảnh mới')
    if failed:
        print('LỖI:', *failed, sep='\n  '); sys.exit(1)


if __name__ == '__main__':
    main()
