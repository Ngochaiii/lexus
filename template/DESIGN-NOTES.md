# Định hướng & nguồn tham khảo

Đọc toàn bộ tài liệu `Lexus_Luxury_Website_Template_Design_Spec.docx` người dùng cung cấp. Chắt lọc DNA quiet luxury, precision, hospitality: ảnh cinematic, chữ trung tính, nền trắng/đen, accent đồng nhẹ, ưu tiên trang chủ và trang mẫu RX. Chỉ dẫn triển khai trong tài liệu được coi là đặc tả tham khảo; yêu cầu trực tiếp HTML/CSS thuần quyết định phạm vi chức năng.

## Tham khảo UX

- Lexus Việt Nam RX — https://www.lexus.com.vn/en/models/rx.html : ngôn ngữ xe, ảnh và phân cấp nội dung sản phẩm.
- Lexus USA — https://www.lexus.com : design anchor trong tài liệu; trang chính trả 403 khi đọc bằng công cụ, không tuyên bố đã kiểm tra trực quan toàn bộ.
- Genesis USA — https://www.genesis.com/us/en/home : kể chuyện theo mẫu xe, bộ sưu tập theo nhóm, hành động khám phá và sở hữu.
- Porsche USA — https://www.porsche.com/usa/ : hành trình chọn xe, giá khởi điểm, đường vào trung tâm và trải nghiệm sở hữu rõ ràng.
- Polestar — https://www.polestar.com/us/ : nội dung tập trung theo dòng xe, giảm rối thông tin.

Nội dung và CSS được viết riêng, không sao chép mã nguồn hay bố cục nguyên bản của các website tham khảo.

## Ảnh demo

Ảnh thật từ Lexus Việt Nam, các trang models/rx.html, models/es.html, models/nx.html, models/lx.html, models/lm.html. Nguồn ảnh gốc dưới tiền tố:
https://www.lexus.com.vn/content/dam/lexus-v3-blueprint/

Các đường dẫn đã kiểm chứng:

- Hero desktop: models/suv/rx/mlp/my23/masthead/masthead-d.jpg
- Hero mobile: models/suv/rx/mlp/my23/masthead/masthead-m.jpg
- Story: models/suv/rx/mlp/my23/dividers/divider1-d.jpg
- RX và gallery: models/suv/rx/mlp/my23/gallery/design/gallery-design-01-d.jpg đến gallery-design-06-d.jpg
- Interior: gallery-design-04-d.jpg trong thư mục gallery/design ở trên.
- Showroom: contact-us/find-a-dealer/lexus-find-a-dealer.jpg

Ảnh được đổi định dạng WebP và thu nhỏ nếu cần để dùng cục bộ. Ảnh gallery và một số block editorial thể hiện ngôn ngữ Lexus nói chung; không đảm bảo khớp mọi phiên bản xe. Ảnh showroom minh họa không tương ứng địa chỉ demo.

Bản quyền ảnh và nhãn hiệu thuộc chủ sở hữu tương ứng. Nguồn công khai không đồng nghĩa giấy phép tái sử dụng thương mại. Bộ ảnh chỉ phục vụ đánh giá template; xác nhận quyền sử dụng hoặc thay bằng ảnh được cấp phép trước khi công bố chính thức.

## Những lựa chọn có chủ đích

- Dùng logo đại lý Lexus Thăng Long do người dùng cung cấp (`assets/logo-ltl-black.png`), không tự vẽ lại logo thương hiệu. Bản trắng và favicon được suy ra từ file này bằng `tools/brand_assets.py`, không chỉnh hình dạng.
- Không tạo ưu đãi giảm giá hay thông số hiệu năng chưa được xác nhận.
- Tài chính là một ví dụ cố định có công thức, không giả lập tính toán khi người dùng thay đầu vào.
- Chọn góc ảnh RX thật thay cho giả lập màu xe bằng CSS filter.
- Địa chỉ thật của showroom nên có liên kết Google Maps; chưa nhúng bản đồ để giữ trang tĩnh và nhẹ.
- Không phát hành công khai vì đầu ra người dùng yêu cầu là bộ template HTML/CSS.

## Ảnh cá nhân — cập nhật 22.09.2026

Đã xem 11 ảnh trong `assets/images/anhcanhan` và chọn 5 ảnh:

| Tiền tố file gốc | Vai trò | Lý do chọn |
| --- | --- | --- |
| 1790052183956 | Chân dung chủ đạo, avatar | Cận mặt rõ, trang phục và xe tạo ngữ cảnh tư vấn; dùng nhất quán để tăng nhận diện. |
| 1790052183855 | Ảnh đón tiếp trang showroom | Nhìn trực diện, có không gian lễ tân Lexus; giữ khuôn mặt, dáng đứng và nhận diện trong khung. |
| 1790052183905 | Khoảnh khắc bàn giao chủ đạo | Ảnh ngang rõ nét, động tác trao hộp, khách và xe cùng xuất hiện. |
| 1790052183963 | Khoảnh khắc gặp gỡ | Ảnh ngang, không gian sáng, tạo nhịp khác với ảnh bàn giao chủ đạo. |
| 1790052183990 | Khoảnh khắc cùng khách hàng | Ảnh dọc rõ bối cảnh xe, hoa và người tư vấn; phù hợp mobile. |

Các ảnh còn lại được giữ nguyên ở thư mục nguồn, không xóa. Ưu tiên tránh ảnh gần trùng bố cục, ảnh chủ thể quá nhỏ và khung cảnh ngoài showroom chưa rõ ngữ cảnh.

Vị trí: dải nhận diện ngay sau hero; khối giới thiệu sau bộ sưu tập; gallery bàn giao thay khối showroom chung ở trang chủ; avatar liên kết về hồ sơ ở trang liên hệ, báo giá, lái thử và showroom. Menu/footer có đường dẫn đến khối giới thiệu.

Không thêm số năm kinh nghiệm, doanh số, giải thưởng hoặc lời nhận xét của khách hàng khi chưa có dữ liệu xác nhận. Tên đã xác nhận: Thu Hà, chức danh “Chuyên viên tư vấn · Lexus Thăng Long” (DISPLAY_NAME/ROLE trong tools/personal_brand.py).

Ảnh gốc không chỉnh sửa. Bản WebP được xuất theo 2–3 chiều rộng, không đổi khuôn mặt, màu ảnh hoặc nội dung; dùng srcset/sizes, lazy-load và object-position theo khung. Trên mobile, gallery thành một cột để giữ người trong ảnh đủ lớn; không dùng carousel.

## Đồng bộ bố cục & font

- Toàn website dùng NobelVnu Book, nhúng file WOFF cục bộ, font-display: swap; không giả lập nét đậm/nghiêng.
- Thang chữ được nâng một bậc so với bản đầu vì NobelVnu Book có chiều cao chữ thường (x-height) 41/100 em, thấp hơn Arial (52/100) khoảng 21%; cùng một giá trị px, font này đọc nhỏ hơn font hệ thống. Theo yêu cầu của người dùng (22.09.2026), mọi khai báo font-size dưới 30px được cộng thêm 3px — 168 khai báo. Kết quả: body mobile 19px, desktop 20px, màn ≥1600px 21px; chữ phụ tối thiểu 13px; nút 16px; điều hướng 17–18px. Các giá trị clamp() của h1/h2 không đổi vì đều hiển thị từ 30px trở lên. Thang chữ này cao hơn bảng typography ở mục 4 của đặc tả; giữ theo quyết định của người dùng. Hai hệ quả kèm theo: (1) điều hướng desktop chuyển sang menu ngăn kéo từ ≤1200px thay vì ≤850px, vì chữ lớn hơn không còn đủ chỗ cạnh logo đại lý tỉ lệ 11,8:1; (2) logo thu còn 28px ở dải ≤1400px vì cùng lý do. Cần lưu ý: trên mobile, h3 đạt 30px trong khi h2 chạm đáy clamp 30px, nên hai cấp tiêu đề bằng nhau; muốn giữ phân cấp thì nâng đáy clamp của h2 lên 33px. Đã kiểm tra không tràn ngang trên toàn bộ 28 trang ở 360, 375, 414, 768, 1024, 1201, 1250, 1366, 1440, 1600 và 1920px.
- Nguồn font: https://www.lexus.com.vn/etc.clientlibs/omotenashi/clientlibs/clientlib-base/fonts/resources/NobelVnu-Book.woff . Xác nhận giấy phép sử dụng web khi đưa vào khai thác chính thức.
- Editorial cards dùng hai cột bằng nhau, ảnh 16:10 và CSS subgrid căn đồng nhất các hàng nhãn, tiêu đề, mô tả, CTA. Mobile chuyển một cột, không cố định chiều cao nội dung.
