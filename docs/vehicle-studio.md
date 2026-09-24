# Màu sắc và ngoại thất 360°

Trang chi tiết dùng một bộ xem ảnh cho tất cả dòng xe, font NobelVnu Book và bố cục responsive. Admin → Dòng xe → sửa xe → Màu sắc / Options: nhập tên, mã màu, ảnh đại diện và bộ ảnh 360°. Có thể kéo sắp xếp thứ tự khung hình.

- Dùng 18–36 ảnh chụp/render quanh xe theo cùng một chiều, cùng màu, camera, kích thước và nền. Không dùng ảnh thư viện khác góc ngẫu nhiên làm bộ quay.
- Từ 12 ảnh trở lên: xoay 360° (kéo ngang, phím trái/phải, Home/End, thanh góc hiện độ).
- 2–11 ảnh: chế độ "nhiều góc nhìn" — cùng thao tác, nhãn hiện "Góc 2/5" thay vì độ, không gọi là 360°. Dùng cho các bộ 4–5 ảnh (3/4 trước, ngang, 3/4 sau, sau, trước) nhập từ Car-project bằng `database/seeders/media/import_angles.py`.
- 1 ảnh hoặc không có: chỉ hiển thị ảnh đại diện.
- Không giới hạn ba màu như phiên bản cũ. Thiếu ảnh riêng thì hiện ảnh tổng quan kèm chú thích; không nhuộm màu giả.
- Mỗi màu có bộ ảnh riêng, hiện áp dụng ở cấp dòng xe, chưa chia theo phiên bản/mâm và chưa có panorama nội thất.
- JavaScript chỉ tải khi trang có màu. Ảnh nạp khi khu vực sắp xuất hiện, cache trong bộ nhớ và tải trước góc kế tiếp; không tự xoay.
- Nếu mất mạng/ảnh lỗi, giữ ảnh hợp lệ trước đó và thông báo. Không có JavaScript vẫn xem ảnh ban đầu và liên hệ.

## Backend / triển khai

Chạy `php artisan migrate` trước khi dùng bản mới. Migration thêm JSON nullable `product_options.spin_frames`. API option trả mảng đường dẫn theo thứ tự; nhân bản xe sao chép bộ ảnh. Uploader cho phép thư mục `catalog/options/360` trong `config/media.php`. File lưu qua media store hiện có, không cần dịch vụ 3D hay npm build.

## Dữ liệu demo RX local

Ngày 23/09/2026 bổ sung 3 bộ × 18 góc vào ba màu RX đang trống (Trắng ngọc trai / Đen / Đồng trầm). Nguồn tham khảo: https://www.lexus.com/models/RX — RX 350 Premium 2026 thị trường Mỹ, mâm 19 inch, Eminent White Pearl / Caviar / Copper Crest. Đây là ảnh minh họa, không khẳng định cấu hình Việt Nam.

Ảnh WebP 1440×628, chất lượng 82. Từ 24/09/2026 bộ ảnh nằm trong `database/seeders/media/lexus/rx/360/{trang,den,dong}/` và `Brands\LexusSeeder` tự gắn vào ba màu RX (bộ 360° luôn được ưu tiên hơn bộ nhiều góc) — chạy lại seeder không còn làm mất bộ xoay.

## Bộ 360° các xe còn lại (24/09/2026)

`python3 database/seeders/media/import_360.py` tải thêm 23 bộ × 18 khung cùng nguồn (trình xem màu lexus.com, xe bản Mỹ đời 2026), cắt đúng vùng như trình xem gốc (`extend=-17,-585,-17,-585`):

| Xe | Màu có 360° | Bản/mâm nguồn |
|---|---|---|
| RX | đủ 7 màu (thêm Xanh rêu ← Nori Green Pearl, Xám ← Iridium, Đỏ ← Matador Red Mica, Xanh dương ← Nightfall Mica) | 350 Premium, mâm 19" |
| ES | đủ 6 màu (Ultra White, Iridium, Cloudburst Gray, Wavelength, Copper Crest, Caviar) | 350e, mâm 19" |
| NX | đủ 6 màu — 5 màu từ lexus.com; Xanh dương đậm (Heat Blue 8X1, màu riêng F SPORT) từ Lexus Nhật, 12 khung × 30°, ảnh 640×480 | 350, mâm 18" / F SPORT |
| GX | đủ 4 màu | Premium, mâm 20" |
| LX | đủ 4 màu (Xám ← Manganese Luster) | LX 600, mâm 22" |
| LM | đủ 4 màu (Sonic White 085, Graphite Black 223, Sonic Titanium 1J7, Sonic Agate 3U3) — **36 khung × 10°** từ trình cấu hình Lexus Anh (images.lexus-europe.com), xe tay lái nghịch | LM 350h AWD, nền EAE8E3 |
| LS | 5 màu (trắng 083, đen 223, bạc 1L2, đỏ 3U3, xanh đậm 8X5) — **12 khung × 30°** từ trình xem 360° Lexus Nhật (lexus.jp), ảnh 640×480 nhỏ hơn, xe bản Nhật | LS 500h |

Khung bắt đầu: khung 04 (3/4 trước); riêng LX khung 01, LM khung 05, LS khung 02. Ảnh bản Mỹ — mâm, logo phiên bản có thể khác xe Việt Nam; bản quyền thuộc Lexus. Có bộ ảnh bản Việt Nam thì thay thư mục `360/{màu}/` rồi chạy lại seeder. Các xe khác cần nhập đúng bộ ảnh để có trải nghiệm tương tự. Khi triển khai cần chuyển cả media và dữ liệu options; các bộ ảnh thương mại cần phù hợp quyền sử dụng của website.

Cơ chế tương tác tham khảo trang https://www.lexus.com/models/TZ; giao diện triển khai theo thiết kế và dữ liệu của dự án.
