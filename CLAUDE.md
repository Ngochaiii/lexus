# Lexus — website tư vấn của chị Thu Hà

Website **es350h-lexusthanglong.com**: trang tư vấn cá nhân của **Nguyễn Thị Thu Hà**, chuyên viên tư vấn bán hàng tại Lexus Thăng Long (Hà Nội) từ 01/03/2017. **Không phải website của đại lý** — đại lý cho phép dùng tên và logo bằng giấy xác nhận có dấu. Mục tiêu duy nhất của site: khách gọi **0934 846 666** hoặc nhắn Zalo **0989 345 989** (từ 09/10/2026 hai số khác nhau: chỗ ghi "Zalo: …" lấy số từ link Zalo, không lấy số gọi).

Laravel 13 + Filament 5, PHP 8.3, MariaDB. CSS/JS viết tay trong `public/assets/` (không build). Trả lời người dùng bằng tiếng Việt.

## Lệnh

- Test: `php artisan test` (cần MariaDB của XAMPP đang chạy, DB `catalog_lexus_test`). Chạy **toàn bộ** test trước khi commit.
- Preview: `preview_start` với cấu hình `lexus-app` (cổng 8010, `.claude/launch.json`).
- Không chạy `pint` trên cả file: nó định dạng lại seeder/config đang căn cột tay. Chỉ sửa đúng chỗ cần.

## Quy trình

- TDD: viết test trước (tests/Feature, tên test tiếng Việt không dấu), rồi sửa code.
- Commit message tiếng Việt, kết thúc bằng `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`; đẩy thẳng lên `main`.
- Site đang chạy có dữ liệu sửa tay trong admin và bài do Gemini viết trên máy chủ — **không chạy lại seeder trên VPS**. Đổi nội dung/dữ liệu bằng migration: chỉ thay khi còn đúng bản cũ, chạy lại không đổi thêm (mẫu: `2026_10_04_110000_advisor_voice_in_content.php`). Sửa seeder song song cho DB mới.
- Deploy: người dùng tự chạy trên VPS (aaPanel, `/www/wwwroot/es350`), rồi nhờ kiểm tra site thật:
  `cd /www/wwwroot/es350 && git pull && /www/server/php/83/bin/php artisan migrate --force && /www/server/php/83/bin/php artisan optimize && /www/server/php/83/bin/php artisan view:clear && chown -R www:www storage bootstrap/cache`
- Kiểm tra sau deploy: crawl đủ trang trong `/sitemap.xml` bằng curl, không chỉ trang chủ.

## Quy tắc nội dung (bắt buộc)

Google Ads đã khoá vĩnh viễn tài khoản của chị Hà (10/2026) vì "Các phương thức kinh doanh không được chấp nhận" — ngụ ý mình là đại lý. Giữ site ở dạng trang cá nhân:

- Dưới logo: "Website tư vấn cá nhân · Nguyễn Thị Thu Hà"; chân trang ghi "được đại lý cho phép sử dụng tên và logo; không phải website chính thức của Lexus Việt Nam hay của đại lý" (`AdvisorIdentityTest`).
- Không xưng "chúng tôi" thay đại lý; không gọi số di động của chị Hà là "hotline"/"tổng đài" (`AdvisorVoiceTest`).
- Kinh nghiệm đúng sự thật: "Tư vấn Lexus tại Lexus Thăng Long từ 2017" — không "10 năm".
- Chỉ nói về xe đại lý đang bán; không bịa câu chuyện khách hàng, bằng cấp, giải thưởng. Bài đứng tên chị Hà cần chị duyệt.
- Không tự đổi giá xe; giá do người dùng xác nhận.
- Ảnh xe chỉ lấy từ Car-project (ảnh do sale cung cấp, xe bản Việt Nam). **Không tải ảnh từ lexus.com** hay Lexus nước ngoài (`SalesPhotosTest`).

## Không được làm

- Không sửa gì trong `/Users/Shared/dự án /Car-project` (chỉ đọc, lấy ảnh qua các script trong `database/seeders/media/`).
- Không giúp lách lệnh khoá Google Ads: không tạo tài khoản mới, không đổi tên miền/số điện thoại để chạy lại quảng cáo cho chị Hà.
- Không nhập mật khẩu, số thẻ, CCCD vào form; không tự bấm gửi kháng nghị, gửi email, xuất bản hay huỷ — mở sẵn để người dùng tự bấm.

## Tài liệu

- `DEPLOY.md` — cài mới trên máy chủ trống.
- `docs/vehicle-studio.md` — khung chọn màu xe và nguồn ảnh.
- Kế hoạch quảng cáo và hồ sơ kháng nghị nằm ngoài repo: `~/Downloads/Ke-hoach-Google-Ads-Lexus-Thu-Ha-ban-10.docx`, `~/Downloads/tài liệu /Ho-so-khang-nghi/`.
