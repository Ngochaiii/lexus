"""
Nhập ảnh xe + ảnh cơ sở từ dự án tham khảo Car-project vào kho ảnh seed.

    python3 database/seeders/media/import_from_car_project.py

Đọc   : ../Car-project/Car-project/public/web/assets/images/{model}/…
Ghi   : database/seeders/media/lexus/{slug}/{vai-tro}.webp
        database/seeders/media/lexus/manifest.json   (kích thước từng ảnh)

Ảnh được chọn tay theo vai trò trên trang (hero, ngoại thất, nội thất,
màu, an toàn, thư viện). Muốn đổi ảnh: sửa bảng PICK rồi chạy lại, hoặc
thả file .webp đè lên đúng tên trong thư mục lexus/{slug}/.

Chỉ thu nhỏ, không bao giờ phóng to: ảnh gốc nhỏ giữ nguyên kích thước,
và giao diện tự chuyển sang bố cục "studio" thay vì kéo giãn cho vỡ.

Cần `cwebp` (brew install webp).
"""
import json, os, subprocess, sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
LEXUS = HERE.parents[2]
SRC = LEXUS.parent / 'Car-project' / 'Car-project' / 'public' / 'web' / 'assets' / 'images'
TPL = LEXUS / 'public' / 'assets'           # ảnh chính hãng 1920px của template
OUT = HERE / 'lexus'

# Chiều rộng tối đa theo vai trò — ảnh lớn hơn thì thu lại, nhỏ hơn giữ nguyên.
MAX = {'hero': 2400, 'ngoai-that': 1920, 'noi-that': 1600, 'van-hanh': 2400,
       'mau': 1600, 'an-toan': 800, 'thu-vien': 1600, 'co-so': 2400}

# (vai trò, tên file ra, nguồn). Nguồn 'tpl:x' = public/assets/x của template.
PICK = {
    'rx': [
        ('hero', 'hero', 'rx/xanhduong.jpg'),
        ('ngoai-that', 'ngoai-that', 'rx/maudo.jpg'),
        ('noi-that', 'noi-that', 'rx/chatlieughedasmooth.jpg'),
        ('van-hanh', 'van-hanh', 'rx/mauxam.webp'),
        ('mau', 'mau-trang', 'rx/mautrang.jpg'), ('mau', 'mau-xam', 'rx/xam1.png'),
        ('mau', 'mau-do', 'rx/do1.webp'), ('mau', 'mau-xanh', 'rx/xanhr1.png'),
        ('mau', 'mau-dong', 'rx/cam1.jpg'), ('mau', 'mau-den', 'rx/den1.jpg'),
        ('an-toan', 'an-toan-pksb', 'rx/PKSB.webp'), ('an-toan', 'an-toan-lta', 'rx/LTA.webp'),
        ('an-toan', 'an-toan-bsm', 'rx/BSM.webp'), ('an-toan', 'an-toan-ahs', 'rx/AHS.jpg'),
        ('an-toan', 'an-toan-sea', 'rx/SEA.webp'), ('an-toan', 'an-toan-rrcc', 'rx/RRCC.webp'),
        ('thu-vien', 'thu-vien-1', 'rx/anh-1.jpg'), ('thu-vien', 'thu-vien-2', 'rx/cam.png'),
        ('thu-vien', 'thu-vien-3', 'rx/cabin.PNG'), ('thu-vien', 'thu-vien-4', 'rx/thanxe.PNG'),
        ('thu-vien', 'thu-vien-5', 'rx/trang1.jpg'), ('thu-vien', 'thu-vien-6', 'rx/chatlieughedasemi.webp'),
        ('thu-vien', 'thu-vien-7', 'rx/ghexe.jpg'), ('thu-vien', 'thu-vien-8', 'rx/anh-5.webp'),
    ],
    'lx': [
        ('hero', 'hero', 'lx/mau-den.webp'),
        ('ngoai-that', 'ngoai-that', 'lx/mau-trang.webp'),
        ('noi-that', 'noi-that', 'lx/noi-that-1.jpg'),
        ('van-hanh', 'van-hanh', 'lx/mau-do.jpg'),
        ('mau', 'mau-den', 'lx/mau-den1.webp'), ('mau', 'mau-trang', 'lx/mau-trang1.webp'),
        ('mau', 'mau-xam', 'lx/mau-xam1.jpg'), ('mau', 'mau-xanh', 'lx/mau-do.jpg'),
        ('an-toan', 'an-toan-pcs', 'lx/PCS.webp'), ('an-toan', 'an-toan-lta', 'lx/LTA.webp'),
        ('an-toan', 'an-toan-bsm', 'lx/BSM.webp'), ('an-toan', 'an-toan-pksb', 'lx/PKSB.png'),
        ('an-toan', 'an-toan-sea', 'lx/SEA.webp'), ('an-toan', 'an-toan-rrcc', 'lx/RRCC.webp'),
        ('thu-vien', 'thu-vien-1', 'lx/anh-main.webp'), ('thu-vien', 'thu-vien-2', 'lx/mau-xam.jpg'),
        ('thu-vien', 'thu-vien-3', 'lx/vip-1.jpg'), ('thu-vien', 'thu-vien-4', 'lx/vip-ghe.jpg'),
        ('thu-vien', 'thu-vien-5', 'lx/vip-rse.jpg'), ('thu-vien', 'thu-vien-6', 'lx/ngoai-that-fsport.webp'),
        ('thu-vien', 'thu-vien-7', 'lx/noi-that-fsport.webp'), ('thu-vien', 'thu-vien-8', 'lx/khoang cabin.jpg'),
    ],
    'gx': [
        ('hero', 'hero', 'gx/mau-xam.webp'),
        ('ngoai-that', 'ngoai-that', 'gx/mau-trang.jpg'),
        ('noi-that', 'noi-that', 'gx/noi-that-1.jpg'),
        ('van-hanh', 'van-hanh', 'gx/mau-trang1.jpg'),
        ('mau', 'mau-trang', 'gx/mau-trang.jpg'), ('mau', 'mau-xam', 'gx/mau-xam1.jpg'),
        ('mau', 'mau-den', 'gx/mau-den.webp'), ('mau', 'mau-xanh', 'gx/mau-xanh.jpg'),
        ('an-toan', 'an-toan-pcs', 'gx/PCS.webp'), ('an-toan', 'an-toan-ahs', 'gx/AHS.webp'),
        ('an-toan', 'an-toan-teammate', 'gx/teammate.webp'),
        ('thu-vien', 'thu-vien-1', 'gx/mau-den1.webp'), ('thu-vien', 'thu-vien-2', 'gx/mau-xanhla1.jpg'),
        ('thu-vien', 'thu-vien-3', 'gx/intro.jpg'), ('thu-vien', 'thu-vien-4', 'gx/noi-that-7cho.webp'),
        ('thu-vien', 'thu-vien-5', 'gx/noi-that-overtrail.jpg'), ('thu-vien', 'thu-vien-6', 'gx/tong-quat.webp'),
        ('thu-vien', 'thu-vien-7', 'gx/mat-truoc.webp'), ('thu-vien', 'thu-vien-8', 'gx/mat-sau.jpg'),
    ],
    'nx': [
        ('hero', 'hero', 'tpl:nx.webp'),
        ('ngoai-that', 'ngoai-that', 'nx/mau-den.jpg'),
        ('noi-that', 'noi-that', 'nx/cabin.jpg'),
        ('mau', 'mau-den', 'nx/mau-den.jpg'), ('mau', 'mau-trang', 'nx/mau-trang.jpg'),
        ('mau', 'mau-xam', 'nx/mau-xam.jpg'), ('mau', 'mau-do', 'nx/mau-do.jpg'),
        ('mau', 'mau-xanh-duong', 'nx/mau-xanh-duong.jpg'), ('mau', 'mau-xanh-reu', 'nx/mau-xanh-reu.jpg'),
        ('an-toan', 'an-toan-pcs', 'nx/Hệ-thống-an-toàn-tiền-va-chạm-PCS.webp'),
        ('an-toan', 'an-toan-lta', 'nx/Hệ-thống-hỗ-trợ-theo-dõi-làn-đường-LTA.webp'),
        ('an-toan', 'an-toan-bsm', 'nx/Hệ-thống-cảnh-báo-điểm-mù-BSM.webp'),
        ('an-toan', 'an-toan-sea', 'nx/Hệ-thống-hỗ-trợ-rời-xe-an-toàn-SEA.webp'),
        ('an-toan', 'an-toan-pksb', 'nx/Hệ-thống-phanh-an-toàn-khi-đỗ-xe-_PKSB_.webp'),
        ('an-toan', 'an-toan-teammate', 'nx/TÍNH-NĂNG-HỖ-TRỢ-ĐỖ-XE-THÔNG-MINH-LEXUS-TEAMMATE.webp'),
        ('thu-vien', 'thu-vien-1', 'nx/anh-4.jpg'), ('thu-vien', 'thu-vien-2', 'nx/anh-1.jpg'),
        ('thu-vien', 'thu-vien-3', 'nx/than-xe.jpg'), ('thu-vien', 'thu-vien-4', 'nx/bang-dieu-khien.jpg'),
        ('thu-vien', 'thu-vien-5', 'nx/man-hinh.jpg'), ('thu-vien', 'thu-vien-6', 'nx/ghe-fsport.jpg'),
        ('thu-vien', 'thu-vien-7', 'nx/duoi-xe.jpg'), ('thu-vien', 'thu-vien-8', 'nx/Khoang hành lý.jpg'),
    ],
    'lm': [
        ('hero', 'hero', 'tpl:lm.webp'),
        ('ngoai-that', 'ngoai-that', 'lm/intro.jpg'),
        ('noi-that', 'noi-that', 'lm/noi-that-4cho.jpg'),
        ('mau', 'mau-trang', 'lm/mau-trang.jpg'), ('mau', 'mau-den', 'lm/mau-den.jpg'),
        ('mau', 'mau-xam', 'lm/mau-xam.jpg'), ('mau', 'mau-do', 'lm/mau-do.jpg'),
        ('an-toan', 'an-toan-pcs', 'lm/pcs.jpg'), ('an-toan', 'an-toan-lta', 'lm/lta.jpg'),
        ('an-toan', 'an-toan-lda', 'lm/lda.jpg'), ('an-toan', 'an-toan-drcc', 'lm/drcc.jpg'),
        ('an-toan', 'an-toan-sea', 'lm/sea.jpg'), ('an-toan', 'an-toan-teammate', 'lm/teammate.jpg'),
        ('thu-vien', 'thu-vien-1', 'lm/noi-that-6-cho.jpg'), ('thu-vien', 'thu-vien-2', 'lm/noi-that-cabin.jpg'),
        ('thu-vien', 'thu-vien-3', 'lm/noi-that-hang-sau.jpg'), ('thu-vien', 'thu-vien-4', 'lm/noi-that-1.jpg'),
        ('thu-vien', 'thu-vien-5', 'lm/mat-truoc.jpg'), ('thu-vien', 'thu-vien-6', 'lm/mat-sau.jpg'),
        ('thu-vien', 'thu-vien-7', 'lm/mat-ben.jpg'), ('thu-vien', 'thu-vien-8', 'lm/anh-3.jpg'),
    ],
    'es': [
        ('hero', 'hero', 'es/trang-1.jpg'),
        ('ngoai-that', 'ngoai-that', 'es/hong-xe.jpg'),
        ('noi-that', 'noi-that', 'es/cabin-tong-quan.jpg'),
        ('mau', 'mau-trang', 'es/mau-trang.jpg'), ('mau', 'mau-den', 'es/mau-den.jpg'),
        ('mau', 'mau-xam', 'es/mau-xam.jpg'), ('mau', 'mau-bac', 'es/mau-bac.jpg'),
        ('mau', 'mau-xanh-duong', 'es/mau-xanh-duong.jpg'), ('mau', 'mau-dong', 'es/mau-dong.jpg'),
        ('an-toan', 'an-toan-pcs', 'es/pcs.jpg'), ('an-toan', 'an-toan-lta', 'es/lta.jpg'),
        ('an-toan', 'an-toan-lda', 'es/lda.jpg'), ('an-toan', 'an-toan-drcc', 'es/drcc.jpg'),
        ('an-toan', 'an-toan-rsa', 'es/rsa.jpg'), ('an-toan', 'an-toan-pda', 'es/pda.jpg'),
        ('thu-vien', 'thu-vien-1', 'es/dau-xe.jpg'), ('thu-vien', 'thu-vien-2', 'es/duoi-xe.jpg'),
        ('thu-vien', 'thu-vien-3', 'es/hang-ghe-sau.jpg'), ('thu-vien', 'thu-vien-4', 'es/man-hinh-14.jpg'),
        ('thu-vien', 'thu-vien-5', 'es/hud.jpg'), ('thu-vien', 'thu-vien-6', 'es/guong-ky-thuat-so.jpg'),
        ('thu-vien', 'thu-vien-7', 'es/thao-tac.jpg'), ('thu-vien', 'thu-vien-8', 'es/noithat-nau.jpg'),
    ],
    'ls': [
        ('hero', 'hero', 'ls/than-xe.jpg'),
        ('ngoai-that', 'ngoai-that', 'ls/30nam-1.jpg'),
        ('noi-that', 'noi-that', 'ls/noi-that-hatde.jpg'),
        ('mau', 'mau-den', 'ls/mau-den.jpg'), ('mau', 'mau-trang', 'ls/mau-trang.jpg'),
        ('mau', 'mau-xam', 'ls/mau-xam.jpg'), ('mau', 'mau-do', 'ls/mau-do.jpg'),
        ('mau', 'mau-xanh', 'ls/mau-xanh.jpg'),
        ('an-toan', 'an-toan-pcs', 'ls/PCS.webp'), ('an-toan', 'an-toan-lta', 'ls/LTA.webp'),
        ('an-toan', 'an-toan-bsm', 'ls/BSM.webp'), ('an-toan', 'an-toan-ahb', 'ls/AHB.webp'),
        ('an-toan', 'an-toan-pksb', 'ls/PKSB.webp'), ('an-toan', 'an-toan-sea', 'ls/SEA.webp'),
        ('thu-vien', 'thu-vien-1', 'ls/dau-xe.jpg'), ('thu-vien', 'thu-vien-2', 'ls/duoi-xe.jpg'),
        ('thu-vien', 'thu-vien-3', 'ls/ghe-sau.jpg'), ('thu-vien', 'thu-vien-4', 'ls/man-hinh-giai-tri.jpg'),
        ('thu-vien', 'thu-vien-5', 'ls/tapli-cua.jpg'), ('thu-vien', 'thu-vien-6', 'ls/volang.jpg'),
        ('thu-vien', 'thu-vien-7', 'ls/can-so.jpg'), ('thu-vien', 'thu-vien-8', 'ls/noi-that-vangnau.jpg'),
    ],
    # Ảnh thật của cơ sở Lexus Thăng Long (anhcanhan/). Cố ý KHÔNG lấy
    # huu-lap-*, IMG_5289, IMG_7011 (chuyên viên khác) và IMG_7017 (có
    # logo của trang khác).
    'co-so': [
        ('co-so', 'mat-tien', 'anhcanhan/IMG_7013.JPG'),
        ('co-so', 'showroom', 'anhcanhan/IMG_7015.JPG'),
        ('co-so', 'khu-trung-bay', 'anhcanhan/IMG_7016.JPG'),
        ('co-so', 'xuong-dich-vu-es', 'anhcanhan/IMG_7014.JPG'),
        ('co-so', 'xuong-dich-vu-rx', 'anhcanhan/IMG_7018.JPG'),
    ],
}


def size(path):
    out = subprocess.run(['sips', '-g', 'pixelWidth', '-g', 'pixelHeight', str(path)],
                         capture_output=True, text=True).stdout
    w = int(out.split('pixelWidth:')[1].split()[0]); h = int(out.split('pixelHeight:')[1].split()[0])
    return w, h


def main():
    manifest, missing = {}, []
    for slug, items in PICK.items():
        (OUT / slug).mkdir(parents=True, exist_ok=True)
        for role, name, src in items:
            source = TPL / src[4:] if src.startswith('tpl:') else SRC / src
            if not source.is_file():
                missing.append(str(source)); continue
            w, h = size(source)
            dest = OUT / slug / f'{name}.webp'
            cmd = ['cwebp', '-quiet', '-q', '82', '-metadata', 'none']
            if w > MAX[role]:
                cmd += ['-resize', str(MAX[role]), '0']
            subprocess.run(cmd + [str(source), '-o', str(dest)], check=True)
            dw, dh = size(dest)
            manifest.setdefault(slug, {})[name] = {'role': role, 'w': dw, 'h': dh, 'source': src}
    (OUT / 'manifest.json').write_text(json.dumps(manifest, ensure_ascii=False, indent=1))
    print('Đã nhập', sum(len(v) for v in manifest.values()), 'ảnh vào', OUT)
    if missing:
        print('THIẾU nguồn:', *missing, sep='\n  '); sys.exit(1)


if __name__ == '__main__':
    main()
