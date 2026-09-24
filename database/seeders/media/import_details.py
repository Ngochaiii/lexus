#!/usr/bin/env python3
"""
Nhập ảnh MÂM XE và CHI TIẾT XE (bản Việt Nam) từ Car-project.

    python3 database/seeders/media/import_details.py

Ảnh 360° lấy từ lexus.com là xe bản Mỹ — mâm, logo có thể khác. Phần "Chi tiết
& tùy chọn" ở trang xe vì thế dùng ảnh của Car-project: mâm theo từng phiên
bản, màu/chất liệu ghế, ốp trang trí, vô-lăng, chi tiết ngoại thất.

Đã bỏ: RX ghexe.jpg (trang brochure có chữ), NX ghe-do2 / LX ghe-den2 (trùng
màu đen), NX hud.jpg (quá nhỏ), LS 30nam-* (bản kỷ niệm giới hạn, không bán).
ES: file mâm/ghế/ốp/vô-lăng mà Car-project khai báo không tồn tại — chỉ có
màu nội thất và chi tiết.

Ra: database/seeders/media/lexus/{xe}/chi-tiet/{tên}.webp (tối đa 1200px).
Brands\\LexusSeeder đọc qua khoá 'details' của từng xe.
"""
import subprocess
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
SRC = HERE.parents[3] / 'Car-project' / 'Car-project' / 'public' / 'web' / 'assets' / 'images'
OUT = HERE / 'lexus'
MAX_W = 1200

DETAILS = {
    'rx': {
        'mat-truoc': 'rx/mattruoc.PNG', 'duoi-xe': 'rx/duoixe.png', 'than-xe': 'rx/thanxe.PNG',
        'mam-luxury': 'rx/mam-xe.png', 'mam-premium': 'rx/mam-xe2.png', 'mam-fsport': 'rx/mam-xe3.png',
        'ghe-hazel': 'rx/ghexe1.jpeg', 'ghe-trang': 'rx/ghexe2.jpg', 'ghe-do': 'rx/ghexe3.jpg',
        'da-semi-aniline': 'rx/dasemi.png', 'da-smooth': 'rx/dasmooth.png', 'da-smooth-den': 'rx/gheden.png',
        'op-sumi': 'rx/op1.png', 'op-hop-kim': 'rx/op2.png', 'op-xuong-ca': 'rx/op3.png',
        'volang-da': 'rx/volang1.png', 'volang-fsport': 'rx/volang2.png', 'volang-go': 'rx/volang3.png',
        'hud': 'rx/hub1.PNG', 'man-hinh-14': 'rx/hub2.PNG',
    },
    'es': {
        'dau-xe': 'es/dau-xe.jpg', 'duoi-xe': 'es/duoi-xe.jpg', 'hong-xe': 'es/hong-xe.jpg',
        'noi-that-den': 'es/noithat-den.jpg', 'noi-that-trang': 'es/noithat-trang.jpg', 'noi-that-nau': 'es/noithat-nau.jpg',
        'hang-ghe-sau': 'es/hang-ghe-sau.jpg',
        'man-hinh-14': 'es/man-hinh-14.jpg', 'dong-ho-123': 'es/man-hinh-123.jpg', 'hud': 'es/hud.jpg',
        'guong-ky-thuat-so': 'es/guong-ky-thuat-so.jpg',
    },
    'nx': {
        'mat-truoc': 'nx/mat-truoc.jpg', 'den-pha': 'nx/Đèn pha.jpg', 'den-hau': 'nx/Đèn hậu.jpg', 'duoi-xe': 'nx/duoi-xe.jpg',
        'mam-18': 'nx/mam-xe-1.jpg', 'mam-20-fsport': 'nx/mam-xe-2.jpg',
        'ghe-den-kem': 'nx/ghe-den.jpg', 'ghe-nau': 'nx/ghe-nau.jpg', 'ghe-do-dark-rose': 'nx/ghe-do1.jpg',
        'ghe-trang-fsport': 'nx/ghe-trang.jpg', 'ghe-flare-red': 'nx/ghe-do3.jpg', 'ghe-den-fsport': 'nx/ghe-do4.jpg',
        'op-go': 'nx/op-1.jpg', 'op-nhom': 'nx/op-2.jpg',
        'volang-fsport': 'nx/volang-1.jpg', 'volang-go': 'nx/volang-2.jpg', 'man-hinh': 'nx/man-hinh.jpg', 'dong-ho-fsport': 'nx/dong-ho.jpg',
    },
    'lx': {
        'mam-urban': 'lx/mam-urban.webp', 'mam-fsport': 'lx/mam-fsport.webp', 'mam-vip': 'lx/mam-vip.jpg',
        'ghe-den': 'lx/ghe-den.png', 'ghe-sunflare': 'lx/ghe-nau.png', 'ghe-trang': 'lx/ghe-trang.png',
        'ghe-hazel': 'lx/ghe-hazel.png', 'ghe-crimson': 'lx/ghe-crimson.png', 'ghe-flare-red': 'lx/ghe-do.png',
        'op-go': 'lx/op-go.jpg', 'op-nhom-hadori': 'lx/op-nhom.webp',
    },
    'gx': {
        'mat-truoc': 'gx/mat-truoc.webp', 'mat-sau': 'gx/mat-sau.jpg',
        'mam-luxury': 'gx/mam1.webp',
        'ghe-flaxen': 'gx/seat-1.png', 'ghe-den': 'gx/seat-2.png', 'ghe-overtrail': 'gx/seat-3.png',
        'cabin-7-cho': 'gx/noi-that-7cho.webp', 'op-go': 'gx/noithat1.jpg',
    },
    'lm': {
        'mat-truoc': 'lm/mat-truoc.jpg', 'mat-ben': 'lm/mat-ben.jpg', 'mat-sau': 'lm/mat-sau.jpg',
        'mam-19': 'lm/banh-xe-1.jpg',
        'khoang-lai': 'lm/noi-that-1.jpg', 'khoang-sau': 'lm/noi-that-hang-sau.jpg', 'cabin': 'lm/noi-that-cabin.jpg',
    },
    'ls': {
        'dau-xe': 'ls/dau-xe.jpg', 'than-xe': 'ls/than-xe.jpg', 'duoi-xe': 'ls/duoi-xe.jpg',
        'mam-20': 'ls/Vành nhôm 20 inch.jpg',
        'noi-that-den': 'ls/noi-that-den.jpg', 'noi-that-do-den': 'ls/noi-that-doden.jpg',
        'noi-that-vang-nau': 'ls/noi-that-vangnau.jpg', 'noi-that-hat-de': 'ls/noi-that-hatde.jpg', 'ghe-sau': 'ls/ghe-sau.jpg',
        'op-art-wood-huu-co': 'ls/op-1.jpg', 'op-art-wood-herringbone': 'ls/op-2.jpg', 'op-cat-laser': 'ls/op-3.jpg', 'kinh-kiriko': 'ls/op-4.jpg',
        'volang': 'ls/volang.jpg', 'can-so': 'ls/can-so.jpg', 'tapli-cua': 'ls/tapli-cua.jpg', 'man-hinh-sau': 'ls/man-hinh-giai-tri.jpg',
    },
}

# Ảnh riêng từng PHIÊN BẢN (thẻ ở trang chủ + thẻ phiên bản ở trang xe) — lấy từ
# trang chủ Car-project. Ra: lexus/{xe}/phien-ban/{tên}.webp. Phiên bản không có
# ảnh riêng (ES Luxury, ES 500e, NX F SPORT) dùng khung 360° — xem LexusSeeder.
# LM, LS KHÔNG lấy ở đây: ảnh Car-project nền tối, lệch tông các thẻ khác →
# dùng import_light_variants.php (nền phòng chụp sáng). Đừng thêm lại, sẽ ghi đè.
VARIANT_IMAGES = {
    'rx': {'premium': 'RX350h-premium.webp', 'luxury': 'rx350hluxury.png', 'fsport': 'rx500hfsport.webp'},
    'es': {'premium': 'es.jpg'},
    'nx': {'350h': 'nx350h.jpeg'},
    'lx': {'urban': 'lx600urban.webp', 'fsport': 'lx600fsport.webp', 'vip': 'lx600vip.jpg'},
    'gx': {'overtrail': 'GX550-M.webp', 'luxury': 'GX550.jpg'},
}


def width(path):
    out = subprocess.run(['sips', '-g', 'pixelWidth', str(path)], capture_output=True, text=True).stdout
    return int(out.split('pixelWidth:')[1].split()[0])


def main():
    missing, total = [], 0
    for car, items in DETAILS.items():
        (OUT / car / 'chi-tiet').mkdir(parents=True, exist_ok=True)
        for name, rel in items.items():
            source = SRC / rel
            if not source.is_file():
                missing.append(rel); continue
            cmd = ['cwebp', '-quiet', '-q', '85', '-metadata', 'none']
            if width(source) > MAX_W:
                cmd += ['-resize', str(MAX_W), '0']
            subprocess.run(cmd + [str(source), '-o', str(OUT / car / 'chi-tiet' / f'{name}.webp')], check=True)
            total += 1
    for car, items in VARIANT_IMAGES.items():
        (OUT / car / 'phien-ban').mkdir(parents=True, exist_ok=True)
        for name, rel in items.items():
            source = SRC / rel
            if not source.is_file():
                missing.append(rel); continue
            cmd = ['cwebp', '-quiet', '-q', '85', '-metadata', 'none']
            if width(source) > MAX_W:
                cmd += ['-resize', str(MAX_W), '0']
            subprocess.run(cmd + [str(source), '-o', str(OUT / car / 'phien-ban' / f'{name}.webp')], check=True)
            total += 1
    print('Đã nhập', total, 'ảnh chi tiết')
    if missing:
        print('THIẾU nguồn:', *missing, sep='\n  '); sys.exit(1)


if __name__ == '__main__':
    main()
