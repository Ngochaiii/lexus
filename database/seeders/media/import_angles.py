#!/usr/bin/env python3
"""
Nhập bộ ẢNH GÓC theo màu cho trình xem xe (vehicle-studio) từ Car-project.

    python3 database/seeders/media/import_angles.py

Car-project có 4–5 ảnh mỗi màu (trước, ngang, sau…), KHÔNG phải chuỗi 24–36
khung chụp trên bàn xoay. Trình xem hiển thị chúng ở chế độ "nhiều góc nhìn":
kéo ngang / bấm ← → để đổi góc. Khi có bộ ảnh bàn xoay ≥ 12 khung thì trình
xem tự chuyển sang xoay 360° thật — chỉ cần thay danh sách trong admin.

Mỗi màu xếp theo vòng quanh xe: 3/4 trước → ngang → 3/4 sau → sau → trước,
để kéo ngang có cảm giác đi vòng quanh xe.

Đã loại khỏi danh sách (xem bảng ảnh khi rà):
  · ảnh cận chi tiết (RX đen den2/den3, LX trắng 5, GX đen 2, GX xám 2)
  · ảnh có watermark bên thứ ba (LM xám mau-xam3 "Response", NX đỏ mau-do1)
  · ảnh nền caro giả trong suốt (NX xám mau-xam1, LS xám/đỏ/xanh ...1)
  · LM "xanh" / "xanh dương": file không tồn tại trong Car-project

Ra: database/seeders/media/lexus/{xe}/goc/{mau}-{n}.webp (tối đa 1600px) +
goc-manifest.json. Brands\\LexusSeeder đọc thẳng các file này.
"""
import json
import subprocess
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
SRC = HERE.parents[3] / 'Car-project' / 'Car-project' / 'public' / 'web' / 'assets' / 'images'
OUT = HERE / 'lexus'
MAX_W = 1600

# xe → màu (khoá trùng với Brands\LexusSeeder) → danh sách file theo thứ tự góc
ANGLES = {
    'rx': {
        'xanh':       ['rx/xanhduong.jpg', 'rx/xanhr4.png', 'rx/xanhr3.png', 'rx/xanhr2.png', 'rx/xanhr1.png'],
        'trang':      ['rx/mautrang.jpg', 'rx/trang3.jpg', 'rx/trang2.jpg', 'rx/trang1.jpg'],
        'xam':        ['rx/mauxam.webp', 'rx/xam3.png', 'rx/xam2.png', 'rx/xam1.png'],
        'den':        ['rx/ngoai-that.png', 'rx/den1.jpg'],
        'do':         ['rx/maudo.jpg', 'rx/do3.jpg', 'rx/do2.png', 'rx/do1.webp'],
        'dong':       ['rx/cam.png', 'rx/cam3.jpg', 'rx/cam2.jpg', 'rx/cam1.jpg'],
        'xanh-duong': ['rx/xanhblue.webp', 'rx/xanhd2.png', 'rx/xanhd3.png', 'rx/xanhd1.png', 'rx/xanhd4.png'],
    },
    'es': {
        'trang':      ['es/trang-2.jpg', 'es/trang-1.jpg', 'es/trang-5.jpg', 'es/trang-3.jpg'],
        'bac':        ['es/bac-3.jpg', 'es/bac-1.jpg', 'es/bac-2.jpg', 'es/bac-4.jpg'],
        'xam':        ['es/xam-3.jpg', 'es/xam-1.jpg', 'es/xam-2.jpg', 'es/xam-4.jpg'],
        'xanh-duong': ['es/xanhduong-3.jpg', 'es/xanhduong-1.jpg', 'es/xanhduong-2.jpg', 'es/xanhduong-4.jpg'],
        'dong':       ['es/dong-3.jpg', 'es/dong-1.jpg', 'es/dong-2.jpg', 'es/dong-4.jpg'],
        'den':        ['es/den-3.jpg', 'es/den-1.jpg', 'es/den-2.jpg', 'es/den-4.jpg'],
    },
    'nx': {
        'xam':        ['nx/mau-xam4.jpg', 'nx/mau-xam.jpg', 'nx/mau-xam2.jpg'],
        'trang':      ['nx/mau-trang1.jpg', 'nx/mau-trang.jpg', 'nx/mau-trang2.jpg'],
        'do':         ['nx/mau-do2.jpg', 'nx/mau-do.jpg'],
        'xanh-duong': ['nx/mau-xanh-duong1.jpg', 'nx/mau-xanh-duong.jpg', 'nx/mau-xanh-duong2.jpg'],
        'xanh-reu':   ['nx/mau-xanh-reu3.jpg', 'nx/mau-xanh-reu.jpg', 'nx/mau-xanh-reu4.jpg'],
        'den':        ['nx/anh-4.jpg', 'nx/anh-1.jpg', 'nx/anh-2.jpg'],
    },
    'lx': {
        'den':        ['lx/mau-den1.webp', 'lx/mau-den2.webp', 'lx/mau-den3.jpg', 'lx/mau-den4.jpg', 'lx/mau-den5.jpg'],
        'trang':      ['lx/mau-trang1.webp', 'lx/mau-trang3.jpg', 'lx/mau-trang4.jpg', 'lx/mau-trang2.webp'],
        'xam':        ['lx/mau-xam1.jpg', 'lx/mau-xam5.jpg', 'lx/mau-xam4.jpg', 'lx/mau-xam3.webp'],
        'xanh':       ['lx/mau-xanh1.webp', 'lx/mau-xanh2.jpg', 'lx/mau-xanh3.webp', 'lx/mau-xanh4.jpg', 'lx/mau-xanh5.jpg'],
    },
    'gx': {
        'xam':        ['gx/mau-xam4.webp', 'gx/mau-xam3.jpg', 'gx/mau-xam1.jpg'],
        'den':        ['gx/mau-den1.webp', 'gx/mau-den3.webp', 'gx/mau-den4.jpg'],
        'trang':      ['gx/mau-trang2.webp', 'gx/mau-trang3.jpg', 'gx/mau-trang4.webp', 'gx/mau-trang1.jpg'],
        'xanh':       ['gx/mau-xanhla1.jpg', 'gx/mau-xanhla3.jpg', 'gx/mau-xanhla2.webp', 'gx/mau-xanhla4.webp'],
    },
    'lm': {
        'xam':        ['lm/mau-xam1.jpg', 'lm/mau-xam.jpg', 'lm/mau-xam4.jpg', 'lm/mau-xam2.jpg'],
        'den':        ['lm/anh-1.jpg', 'lm/mau-den.jpg', 'lm/anh-4.jpg'],
        'trang':      ['lm/mau-trang1.jpg', 'lm/mau-trang.jpg', 'lm/mau-trang2.jpg', 'lm/mau-trang4.jpg'],
        'do':         ['lm/mau-do2.jpg', 'lm/mau-do.jpg'],
    },
    'ls': {
        'xanh':       ['ls/mau-xanh2.jpg', 'ls/mau-xanh.jpg'],
        'bac':        ['ls/mau-xam3.jpg', 'ls/mau-xam.jpg', 'ls/mau-xam4.jpg'],
        'trang':      ['ls/mau-trang1.jpg', 'ls/mau-trang.jpg', 'ls/mau-trang3.jpg', 'ls/mau-trang4.jpg'],
        'den':        ['ls/mau-den1.jpg', 'ls/mau-den.jpg', 'ls/mau-den3.jpg', 'ls/mau-den4.jpg'],
        'do':         ['ls/mau-do2.jpg', 'ls/mau-do.jpg'],
    },
}

# Ảnh riêng bản NX 350 F SPORT cho thư viện (tên đích → file nguồn).
EXTRA = {
    'nx': {
        'fsport-noi-that-do': 'nx/Nội thất Đỏ - Flare Red (Phiên bản F SPORT).jpg',
        'fsport-ghe':         'nx/Thiết kế ghế F SPORT.jpg',
        'fsport-cua-so-troi': 'nx/Cửa sổ trời (Phiên bản NX 350 F SPORT).jpg',
    },
}


def size(path):
    out = subprocess.run(['sips', '-g', 'pixelWidth', '-g', 'pixelHeight', str(path)],
                         capture_output=True, text=True).stdout
    return int(out.split('pixelWidth:')[1].split()[0]), int(out.split('pixelHeight:')[1].split()[0])


def convert(source, dest):
    w, _ = size(source)
    cmd = ['cwebp', '-quiet', '-q', '82', '-metadata', 'none']
    if w > MAX_W:
        cmd += ['-resize', str(MAX_W), '0']
    subprocess.run(cmd + [str(source), '-o', str(dest)], check=True)
    return size(dest)


def main():
    manifest, missing = {}, []
    for car, colours in ANGLES.items():
        (OUT / car / 'goc').mkdir(parents=True, exist_ok=True)
        for colour, files in colours.items():
            for n, rel in enumerate(files, 1):
                source = SRC / rel
                if not source.is_file():
                    missing.append(rel); continue
                name = f'{colour}-{n}'
                w, h = convert(source, OUT / car / 'goc' / f'{name}.webp')
                manifest.setdefault(car, {}).setdefault(colour, []).append({'file': f'goc/{name}', 'w': w, 'h': h, 'source': rel})
    for car, items in EXTRA.items():
        for name, rel in items.items():
            source = SRC / rel
            if not source.is_file():
                missing.append(rel); continue
            w, h = convert(source, OUT / car / f'{name}.webp')
            manifest.setdefault(car, {}).setdefault('_extra', []).append({'file': name, 'w': w, 'h': h, 'source': rel})
    (OUT / 'goc-manifest.json').write_text(json.dumps(manifest, ensure_ascii=False, indent=1))
    total = sum(len(v) for c in manifest.values() for v in c.values())
    print('Đã nhập', total, 'ảnh góc vào', OUT)
    if missing:
        print('THIẾU nguồn:', *missing, sep='\n  '); sys.exit(1)


if __name__ == '__main__':
    main()
