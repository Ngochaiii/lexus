# Lexus Digital Showroom — HTML/CSS template

Bộ template tĩnh tiếng Việt, 28 trang, mở trực tiếp `index.html` hoặc phục vụ bằng một HTTP server tĩnh. Không cần npm, framework, JavaScript hay backend. Hình ảnh WebP lưu trong `assets/`, dùng được offline.

## Trang chính

- `index.html`: trang chủ với hero RX, bộ sưu tập, câu chuyện, dịch vụ, showroom, tin tức.
- `models.html`: danh sách 5 dòng xe; lọc bằng CSS radio và `:has()`.
- `rx.html`: trang chi tiết chuẩn; chọn 3 góc ảnh, gallery phóng lớn bằng `:target`, phiên bản, accordion, form demo.
- `es.html`, `lx.html`, `nx.html`, `lm.html`: các trang cùng hệ thiết kế.
- `phien-ban.html`: trang phiên bản RX 350h mẫu.
- `bang-gia.html`, `so-sanh.html`, `uu-dai.html`, `chi-tiet-uu-dai.html`: giao diện mua xe.
- `tai-chinh.html`, `lan-banh.html`: giao diện tài chính với kịch bản cố định và cách tính minh họa.
- `lai-thu.html`, `bao-gia.html`, `lien-he.html`, `demo-success.html`: biểu mẫu và trạng thái mẫu.
- `experience.html`, `dich-vu.html`, `showroom.html`, `tin-tuc.html`, `bai-viet.html`: trải nghiệm và nội dung.
- `faq.html`, `privacy.html`, `terms.html`, `404.html`, `components.html`: hỗ trợ và design system.

## Thiết kế

Đen mực #151617, trắng #FFFFFF, trắng ấm #F3F1ED, đồng trầm #8B7355. Logo đại lý `assets/logo-ltl-black.png` (header nền trắng) và `assets/logo-ltl-white.png` (header trên hero, footer); favicon `assets/favicon-32/192.png`, `apple-touch-icon.png` được tạo từ biểu tượng trong logo bằng `python3 tools/brand_assets.py`. Font NobelVnu Book được nhúng cục bộ tại `assets/fonts/NobelVnu-Book.woff`, hỗ trợ tiếng Việt. Ảnh lớn, cạnh vuông, nhiều khoảng trắng, không thêm hiệu ứng rườm rà. CSS tokens nằm đầu `assets/style.css`.

## Phạm vi HTML/CSS

Hoạt động: điều hướng trang, menu native details, lọc xe, đổi góc ảnh RX, phóng ảnh, accordion, ẩn hàng giống nhau trong bảng so sánh, validation HTML bắt buộc, liên kết tới trang xác nhận demo, responsive và reduced-motion.

Chưa tích hợp: gửi form/CRM, tính tài chính thời gian thực, lựa chọn xe so sánh tùy ý, màu ngoại thất chính xác, bản đồ nhúng (hiện dùng liên kết Google Maps; Zalo dùng liên kết zalo.me), focus trap/Escape của modal, header thay đổi theo scroll, các trạng thái API/loading/error. Đây là phạm vi triển khai chức năng sau khi duyệt template. Gallery dùng vùng ảnh `:target`, không giả làm modal hoàn chỉnh.

Form GET chuyển sang trang xác nhận mẫu; dữ liệu có thể nằm trong URL/lịch sử trình duyệt. Chỉ dùng dữ liệu giả. Không có xử lý máy chủ, gửi email hoặc lưu CRM. Thông tin liên hệ thật: Thu Hà · 0989 345 989 · Lexus Thăng Long, ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy, Hà Nội (khai báo một lần ở đầu `tools/build.py`: `DEALER`, `ADVISOR`, `PHONE`, `ADDRESS`, `MAPS`). Giá, phiên bản và điều kiện tài chính không phải thông tin giao dịch hiện hành.

## Chỉnh sửa

HTML đầu ra có thể sửa trực tiếp. `tools/build.py` là công cụ tác giả tùy chọn để tạo lại toàn bộ trang, không được chạy trong trình duyệt. Header/footer và nội dung lặp được quản lý trong file này. `assets/models-demo.json` là bản xuất dữ liệu xe minh họa; nguồn sinh hiện nằm trong biến `models` của build.py. Nếu chạy lại công cụ, thay đổi HTML trực tiếp sẽ bị ghi đè.

Nguồn tham khảo, ảnh và lưu ý quyền sử dụng được ghi trong `DESIGN-NOTES.md`. Bộ template chưa được xuất bản lên Internet.

## Phần thương hiệu cá nhân

- Xem `index.html#chuyen-vien` và `index.html#khoanh-khac`.
- Ảnh được chọn lưu tối ưu trong `assets/personal/`; ảnh gốc nằm ở `assets/images/anhcanhan/`.
- HTML các khối được quản lý trong `tools/personal_brand.py`. `build.py` gọi module này khi tạo lại trang. CSS nằm cuối `assets/style.css`, bắt đầu từ comment Personal brand.
- Tên hiển thị `DISPLAY_NAME='Thu Hà'` trong `tools/personal_brand.py`; đổi tên/chức danh rồi tạo lại trang bằng `python3 tools/build.py`. Nếu đã sửa HTML trực tiếp, cần giữ lại các sửa đổi đó trước khi tạo lại.
- Bản ZIP chỉ cần ảnh tối ưu đã chọn; các ảnh gốc cá nhân không được đưa vào bộ đóng gói website.
