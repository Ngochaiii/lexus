# Lexus Thăng Long — website tư vấn bán hàng

Laravel 13 · PHP 8.3 · Filament 5 · MariaDB

Một app duy nhất: admin Filament + API JSON + frontend Blade chung repo.
Nhân backend lấy từ `cars-backend` (bản VinFast), giao diện dựng từ bộ
template HTML/CSS Lexus trong `template/` — xem `template/README.md`.

## Phạm vi

Bốn module, theo yêu cầu của chủ website:

| Module | Đường dẫn |
|---|---|
| Dòng xe | `/san-pham` · `/san-pham/{slug}` · `/danh-muc/{slug}` |
| Tin tức | `/tin-tuc` · `/tin-tuc/{slug}` · `/chuyen-muc/{slug}` |
| SEO | `sitemap.xml` · canonical · Open Graph · JSON-LD · redirect 301 |
| Form lái thử · báo giá | `/dang-ky-lai-thu` · `/bao-gia` · popup trang chủ · `POST /gui-form/{form}` |

Trang chủ dựng lại đủ 12 khối theo bản thiết kế. Banner hero sửa ở admin →
**Banner trang chủ**; các khối biên tập giữa trang sửa ở **Trang → Trang chủ
— khối nội dung**.

**Đã gỡ khỏi bản VinFast:** phụ kiện · đại lý · trạm sạc & dịch vụ · so sánh
xe · tìm kiếm · bộ tính trả góp · bộ tính lăn bánh · so sánh chi phí
điện/xăng · băng đăng ký nhận tin.

Route, controller và Filament resource của các khối trên đã xoá. Model và
migration tương ứng (`Dealer`, `Province`, `Banner`) **giữ nguyên** vì
`config/catalog.php` còn trỏ tới trong bảng `models`; chúng bị tắt bằng
feature flag nên không lộ ra ở đâu.

## Triển khai VPS

Xem **[DEPLOY.md](DEPLOY.md)** — một lần `migrate --seed` + `catalog:images`
ra bản đầy đủ (6 dòng xe, 11 phiên bản, ảnh 360°, 11 trang, 6 bài viết). Mẫu
`.env` cho máy chủ: `.env.production.example` (ADMIN_*, LEAD_NOTIFY_EMAILS).

## Chạy

```bash
composer install
php artisan migrate --seed
php artisan catalog:images     # sinh srcset + kích thước ảnh (chạy lại sau khi seed/đổi ảnh)
php artisan serve
```

| | |
|---|---|
| Trang chủ | http://127.0.0.1:8000 |
| Admin | http://127.0.0.1:8000/admin — `admin@lexusthanglong.test` / `password` |
| Sitemap | http://127.0.0.1:8000/sitemap.xml (kèm ảnh) |
| robots / AI | http://127.0.0.1:8000/robots.txt · http://127.0.0.1:8000/llms.txt — sinh động theo `APP_URL` |

**Trước khi lên thật: đặt `APP_URL` đúng domain.** Canonical, sitemap,
robots.txt, llms.txt và JSON-LD đều lấy gốc từ đây.

Database: `catalog_lexus` (test dùng `catalog_lexus_test`).

## Lead theo phiên bản

Trang chủ hiện từng **phiên bản** (RX 350h Premium, LX 600 VIP…), không gộp
theo dòng xe. Nút "Nhận báo giá" trên thẻ phiên bản (trang chủ và trang xe)
gửi kèm `variant_id` → `leads.product_variant_id`. Admin → Liên hệ có cột và
bộ lọc "Phiên bản" để xem mẫu nào được hỏi nhiều. Ảnh thẻ phiên bản: ô "Ảnh
phiên bản" trong admin (seed từ `media/lexus/{xe}/phien-ban/`).

## Giao diện

Blade ở `resources/views/frontend/` — xem `resources/views/README.md` để biết
file nào cắt từ trang nào và chỗ nào còn dữ liệu tĩnh.

CSS là `public/assets/style.css`, viết tay, không build. Font NobelVnu nhúng
cục bộ. Lọc xe, menu mobile, thư viện và accordion giữ tương tác CSS/HTML.
Trang chi tiết sử dụng `public/assets/vehicle-studio.js` để chọn màu, kéo xoay
ngoại thất 360° và phóng ảnh. Hướng dẫn nhập ảnh: `docs/vehicle-studio.md`.

## Dữ liệu mẫu

| Seeder | Seed gì |
|---|---|
| `LexusSiteSeeder` | cài đặt, 3 form, 10 trang tĩnh đủ nội dung (thế giới Lexus, bảng giá, đặc quyền, tài chính, dịch vụ, showroom, liên hệ, hỏi đáp, quyền riêng tư, điều khoản), 8 bài viết SEO/GEO, menu, banner |
| `Brands\LexusSeeder` | 6 dòng xe đang bán RX · ES · LX · GX · LM · LS (NX ẩn): giá từng phiên bản, màu có ảnh, thông số, FAQ. Seed lại giữ nguyên id phiên bản |
| `Brands\MauSeeder` | mẫu để copy khi thêm hãng khác |

Ảnh seed nằm trong `database/seeders/media/lexus/`:
- `{rx,es,nx,lx,gx,lm,ls}/` — ảnh xe chọn lọc từ dự án Car-project
  (`import_from_car_project.py`), đã bỏ ảnh trùng, trang brochure có chữ,
  ảnh tư vấn viên khác.
- `{xe}/goc/` — bộ ảnh góc theo màu cho trình xem xoay (`import_angles.py`);
  `{xe}/360/{màu}/` — bộ xoay 360° (`import_360.py`): lexus.com cho RX, ES,
  NX, GX, LX (18 khung); Lexus Anh cho LM (36 khung); Lexus Nhật cho LS
  (12 khung) và NX Xanh dương đậm (Heat Blue). Mọi màu của 7 xe đều xoay 360°.
- `{xe}/chi-tiet/` — mâm xe, màu/chất liệu ghế, ốp, vô-lăng, chi tiết ngoại
  thất bản Việt Nam từ Car-project (`import_details.py`), hiện ở mục "Chi tiết
  & tùy chọn" dạng tab của trang xe (layout `groups`).
- `{xe}/phien-ban/` — ảnh từng phiên bản (thẻ trang chủ, thẻ phiên bản trang
  xe), cùng tông phòng chụp sáng: RX, ES, NX, LX, GX từ Car-project
  (`import_details.py`); LM, LS ghép xe nền trong suốt (Lexus Anh / Lexus
  Nhật) lên nền phòng chụp (`php database/seeders/media/import_light_variants.php`).
- `co-so/` — ảnh thật cơ sở Lexus Thăng Long (mặt tiền, sảnh, phòng chờ, khu
  bàn giao, xưởng sơn, xưởng dịch vụ), dùng cho mọi trang ngoài trang xe.

Giá xe là giá niêm yết tham khảo theo Car-project; sửa trong admin khi hãng
đổi giá — trang xe, `/bang-gia`, JSON-LD và llms.txt cùng đổi theo. Số liệu
phí lăn bánh trong bài viết đã đối chiếu văn bản tháng 9/2026 (ghi ở đầu
`LexusSiteSeeder::posts()`).

## Bộ template gốc

Toàn bộ bản thiết kế tĩnh nằm trong `template/`, giữ lại để đối chiếu:

```
template/
├── *.html              28 trang thiết kế
├── assets/             ảnh, font, style.css bản gốc
├── tools/build.py      sinh lại bộ HTML (chạy trong template/)
├── README.md           hướng dẫn bộ template
├── DESIGN-NOTES.md     nguồn ảnh, font, quyết định thiết kế
└── VALIDATION.md
```

Thư mục này KHÔNG liên quan đến app Laravel và không được phục vụ ra web
(web root là `public/`). Ảnh và CSS mà app dùng nằm ở `public/assets/` —
một bản sao; sửa `template/assets/` không ảnh hưởng tới site đang chạy.

## Trạng thái

Giao diện đã nối xong dữ liệu: mỗi dòng xe ra đúng nội dung của nó, tin tức và
trang tĩnh lấy từ admin, form lái thử lưu được lead.

Test: **247 test · 225 đạt · 22 skip · 0 hỏng.** Các test skip đều khẳng định
markup của giao diện VinFast cũ; hành vi tương ứng đã phủ lại bằng
`tests/Feature/LexusFrontendTest.php`.

## Việc còn lại

Xem mục cuối `resources/views/README.md`.
