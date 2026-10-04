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

## Nguồn ảnh (từ 04/10/2026)

Mọi ảnh màu xe là **bộ ảnh góc từ Car-project** — ảnh do sale cung cấp, xe bản Việt Nam — nhập bằng `python3 database/seeders/media/import_angles.py` vào `database/seeders/media/lexus/{xe}/goc/` (danh sách trong `goc-manifest.json`). Trình xem chạy ở chế độ "nhiều góc nhìn" (2–5 ảnh/màu).

Trước đây các màu dùng bộ xoay 360° tải từ trình chọn màu lexus.com (xe bản Mỹ), Lexus Anh (LM) và Lexus Nhật (LS, NX Xanh dương). Ngày 04/10/2026 bỏ hẳn bộ đó — xe bản nước ngoài, website không có quyền dùng — cùng script `import_360.py`. Migration `2026_10_04_120000_sales_photos_instead_of_lexus_com` đổi dữ liệu site đang chạy sang ảnh góc và xoá ảnh 360° khỏi kho media. **Không tải lại ảnh từ lexus.com.** Có bộ ảnh bàn xoay bản Việt Nam (từ đại lý/Lexus Việt Nam, được phép dùng) thì nhập trong admin — từ 12 ảnh trở lên trình xem tự xoay 360°.
