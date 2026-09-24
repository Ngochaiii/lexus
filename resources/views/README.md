# Bản cắt Blade — Lexus Thăng Long

Cắt từ bộ template HTML tĩnh ở thư mục gốc repo. Tên view và tên route đặt
**khớp với `cars-backend`** để bước nối backend chỉ là thay chữ tĩnh bằng
biến, không phải dựng lại khung.

## Phạm vi đã chốt

Bốn module: post xe · tin tức · SEO · form đăng ký lái thử.
Trang chủ làm bản gọn (`home.blade.php`), không bê nguyên trang chủ của bộ
template — bản gốc vẫn ở `index.html` thư mục gốc.

## File

```
resources/views/frontend/
├── layout.blade.php        khung chung: head/SEO, header, main, footer, sales bar
├── products.blade.php      danh sách dòng xe      ← models.html
├── product.blade.php       chi tiết dòng xe       ← rx.html
├── posts.blade.php         danh sách tin tức      ← tin-tuc.html
├── post.blade.php          bài viết               ← bai-viet.html
├── booking.blade.php       đăng ký lái thử        ← lai-thu.html
├── home.blade.php          trang chủ 12 khối      ← index.html
├── page.blade.php          trang tĩnh /{slug}     (viết mới)
└── partials/
    ├── header.blade.php        logo + mega menu + menu ngăn kéo mobile
    ├── footer.blade.php        5 cột nền tối + dòng pháp lý
    ├── sales-bar.blade.php     Báo giá / Gọi / Zalo, dính đáy mobile
    ├── page-intro.blade.php    breadcrumb + eyebrow + H1 + mô tả
    ├── conversion.blade.php    băng chuyển đổi nền tối cuối trang
    ├── product-card.blade.php  thẻ xe trong lưới
    ├── post-card.blade.php     thẻ bài viết trong lưới
    ├── advisor-mini.blade.php  thẻ nhỏ chuyên viên tư vấn
    ├── lead-form.blade.php     form thu lead (lái thử + báo giá)
    ├── sections.blade.php      hệ mục của core — copy từ cars-backend
    ├── section/*.blade.php     media · text · bảng · form · video · thông báo
    ├── pagination.blade.php    copy từ cars-backend
    └── tracking.blade.php      GTM / Pixel, copy từ cars-backend
```

Ảnh, font và CSS nằm ở `public/assets/` — bản sao của `assets/` ở gốc repo.
CSS không build, sửa thẳng `public/assets/style.css`.

## Tương tác giao diện

Phần lớn tương tác dùng HTML/CSS; bộ xem màu và xoay xe dùng JavaScript nhỏ, không cần build:

| Chức năng | Cơ chế |
|---|---|
| Lọc dòng xe | radio ẩn + `.catalog:has(#suv:checked)` |
| Menu mobile | `<details>`, nhãn đổi bằng `.mobile-menu[open]` |
| Màu sắc / xoay xe | `partials/vehicle-studio.blade.php` + `assets/vehicle-studio.js`; ảnh và thứ tự từ `options.spin_frames` |
| Phóng ảnh thư viện | `:target` trên `.lightbox` |
| Accordion | `<details>/<summary>` |

Vì vậy `id` của radio lọc phải trùng chuỗi trong `data-category` của thẻ xe —
xem `partials/product-card.blade.php`.

## Hợp đồng với backend

Các view đã gọi sẵn route theo đúng tên của `cars-backend`:

| Route | Dùng ở |
|---|---|
| `home` | logo, breadcrumb, link khối chuyên viên |
| `products.index` · `products.show` | mega menu, footer, thẻ xe, xe liên quan |
| `posts.index` · `posts.show` | menu, footer, thẻ bài viết |
| `booking` | CTA header, sales bar, băng chuyển đổi |
| `leads.store` | action của `lead-form` |
| `pages.show` | bang-gia · bao-gia · uu-dai · tai-chinh · dich-vu · showroom · lien-he · faq · the-gioi-lexus · quyen-rieng-tu · dieu-khoan |

`lead-form` đã dựng theo hợp đồng của `StoreLead`: POST kèm `@csrf`, ô bẫy bot
tên `website`, lỗi validate đọc từ error bag đặt theo `$formKey`, gửi xong đọc
`session('lead_success')`.

## Chỗ còn tĩnh, cần thay ở bước backend

Mỗi file có khối `@php` đánh dấu `// Bước backend:`. Tóm tắt:

| View | Thay bằng |
|---|---|
| `products` | `Product::published()` + `Category::all()` cho bộ lọc |
| `product` | `$product` với `variants`, `options`, `sections`, `$related` |
| `posts` | `$featured` + `$posts` phân trang |
| `post` | `{!! $post->body !!}` thay khối `<article>` tĩnh |
| `booking` | `Form::where('key','dang-ky-lai-thu')`, `$products` |
| `header`/`footer` | `catalog_menu()`, `catalog_setting()` |
| `lead-form` | vòng lặp `$form->fields` thay các `<div class="field">` tĩnh |

Layout nhận SEO qua biến chứ không qua `@section`, đúng hợp đồng của core:
`title` · `description` · `canonical` · `ogImage` · `ogType` · `jsonld` ·
`robots` · `prev` · `next` · `bodyClass`, cộng hai biến riêng của bản Lexus là
`overlay` và `salesBar`.

## Trạng thái

**Đã nối dữ liệu xong.** Không còn view nào render mảng tĩnh.

| | |
|---|---|
| `/` · `/san-pham` · `/san-pham/{slug}` · `/danh-muc/{slug}` | 200, dữ liệu thật |
| `/tin-tuc` · `/tin-tuc/{slug}` · `/dang-ky-lai-thu` · trang tĩnh | 200, dữ liệu thật |
| `/san-pham/es` | ra đúng ES (trước đây ra RX) |
| Gửi form lái thử | lead lưu vào DB, kèm dòng xe đã chọn |
| Bot điền ô bẫy · gửi trùng 5 phút | không tạo lead |

Test: **191 test · 169 đạt · 22 skip · 0 hỏng.**

22 test bị skip đều khẳng định markup của giao diện VinFast (`data-home-story`,
`tools__item--feature`, bố cục hai cột…). Mỗi method có ghi chú lý do ngay tại
chỗ. Hành vi tương ứng được phủ lại bằng `tests/Feature/LexusFrontendTest.php`
(9 test, viết theo giao diện Lexus).

Một test bị gỡ hẳn: `LeadFormTest::test_form_render_du_thong_tin_de_javascript_gui_nen`
— nó đòi lớp JavaScript gửi form bằng fetch, mà bản Lexus không có JS. Lý do
ghi ngay trong file.

## Trang chủ

Dựng lại đủ 12 khối theo `index.html`, đúng thứ tự bản thiết kế:

| # | Khối | Nguồn nội dung |
|---|---|---|
| 01 | Hero toàn màn hình | **Banner** trong admin (ảnh, eyebrow, tiêu đề, mô tả, nút) |
| 02 | Dải chuyên viên | Cài đặt `advisor_*` |
| 03 | Bộ sưu tập (3 xe, lọc CSS) | `Product::published()` |
| 04 | Hồ sơ chuyên viên `#chuyen-vien` | Cài đặt `advisor_*` |
| 05–09 | Story · lối tắt · Omotenashi · Takumi · đặc quyền | `sections` của trang tĩnh `trang-chu` |
| 10 | Khoảnh khắc đồng hành `#khoanh-khac` | ảnh `public/assets/personal/` |
| 11 | Tin tức | `Post::published()` |
| 12 | Băng chuyển đổi | partial dùng chung |

Khối 02, 04 và 10 tự ẩn khi Cài đặt chưa có `advisor_name`, hoặc khi bật
`advisor_off`. Khối 03 và 11 tự ẩn khi chưa có xe / chưa có bài.

### Khối 05–09 sửa được trong admin

Năm khối biên tập giữa trang nằm trong `sections` của trang tĩnh slug
`trang-chu` (Trang → **Trang chủ — khối nội dung**). Biên tập viên sửa chữ,
ảnh, link mà không đụng vào view.

| Khối | Bố cục chọn trong admin |
|---|---|
| Story | Băng ảnh tràn hết màn hình (`bleed`) |
| Hành trình sở hữu | Lưới ô bấm đánh số (`shortcuts`) |
| Omotenashi | Chữ một bên, ảnh một bên (`split`) |
| Takumi | Chữ một bên, ảnh một bên — ảnh trước (`split-alt`) |
| Đặc quyền sở hữu | 2 cột (`cols-2`) |

Quy ước trường trong mọi bố cục có chữ: **Tên mục** → dòng eyebrow · **Mô tả
ngắn** → tiêu đề H2 · **Nội dung** → đoạn văn · **Nút 1/2** → link.

Trong từng ảnh của mục, `SectionsRepeater` có thêm ba ô chỉ hiện ở bố cục cần
tới chúng: **Dòng nhỏ trên tiêu đề** (cols-2/3), **Link của ô này**
(shortcuts, cols-2/3) và **Mô tả**.

Trang chủ không đánh số `01 /` trên eyebrow (trang chi tiết xe thì có) —
điều khiển bằng biến `numbered` truyền vào `partials/sections`.

Chưa tạo trang `trang-chu` thì phần giữa rỗng, các khối còn lại vẫn chạy.

### Banner hero

Khối 01 lấy từ bản ghi **Banner** (admin → Banner trang chủ): ảnh desktop +
ảnh mobile riêng, dòng nhỏ, tiêu đề, mô tả, nút và lịch chạy (Chạy từ / Chạy
đến). Tiêu đề và mô tả xuống dòng bằng Enter — bản thiết kế ngắt dòng giữa
câu nên view dùng `nl2br`, không để trình duyệt tự ngắt.

Mỗi ô bỏ trống thì lùi về đúng nội dung bản thiết kế; xoá sạch bảng banner
cũng không làm vỡ trang chủ. Banner tắt hoặc hết hạn cũng lùi về như vậy.

Link phụ cạnh nút chính luôn trỏ tới trang đăng ký lái thử, nhãn lấy từ
`config('catalog.labels.cta.test_drive')` — đây là hành động của site, không
thuộc nội dung banner.

Chỉ còn khối 10 (khoảnh khắc đồng hành) là chữ cố định trong view.

## Form thu lead & popup báo giá

Form **cố định trong code** (`LexusSiteSeeder`), không quản trị ở admin — chủ
website chỉ xem người gửi ở admin → **Liên hệ** (danh sách Lead).

| Form | Ở đâu | Lead hiện trong admin với tên |
|---|---|---|
| `dang-ky-lai-thu` | trang `/dang-ky-lai-thu` | Đăng ký lái thử |
| `nhan-bao-gia` | trang `/bao-gia`, mọi nút "Báo giá", cuối trang xe | Nhận báo giá |
| `popup-bao-gia` | popup tự bật ở trang chủ | Popup trang chủ |

Cả ba chỉ hỏi: họ tên · số điện thoại · dòng xe · đồng ý chính sách. Dòng xe
gửi `product_id` (kiểu trường `product` của core) nên cột **Dòng xe** trong
danh sách Lead có dữ liệu — gửi tên xe dạng chữ thì cột đó trống.

### Nút "Báo giá"

Mọi nút có `data-quote` (và `data-product` = id xe khi biết). Có JS thì mở popup
tại chỗ, chọn sẵn xe; không có JS thì đi tới `/bao-gia?xe={slug}`. Link bất kỳ
trỏ tới `/bao-gia` — kể cả link biên tập viên gõ trong admin — cũng được bắt.

### Popup tự bật

Chỉ trang chủ. Cấu hình ở `config('catalog.frontend.popup')`, không qua admin:

| Khoá | Giá trị | Vì sao |
|---|---|---|
| `delay` | 20 giây | đại lý VinFast Thăng Long 15s, Toyota Thanh Xuân 25s; site hãng không bật |
| `mobile` | false | Google coi hộp che màn hình điện thoại là interstitial, có thể kéo hạng |
| `dismiss_days` | 7 | tính từ lúc bật — đóng tab ngang cũng được tính |
| `sent_days` | 90 | đã gửi form thì không làm phiền nữa |

Đồng hồ chỉ chạy khi tab đang hiện; khách đang gõ vào form khác thì hoãn 5s;
mỗi phiên tối đa một lần. Chi tiết nghiên cứu ghi ngay trong `config/catalog.php`.

### JavaScript

`public/assets/lead.js` là **file JS duy nhất** của site — phá lệ "không JS"
của bản thiết kế vì popup tự bật bắt buộc phải có JS. Mọi thứ vẫn là lớp nâng
cấp: tắt JS thì form POST thường, nút "Báo giá" dẫn tới `/bao-gia`.

Có sẵn sự kiện `lead:sent` trên `document` để gắn đo chuyển đổi (GTM/Pixel).

## Bộ render mục theo hệ CSS Lexus

`partials/sections.blade.php` + `partials/lexus-section/*` thay bản của
cars-backend (bản đó dùng class `wrap`, `block`, `bleed`… không có trong
style.css của Lexus).

| Layout trong admin | Khối của bản thiết kế |
|---|---|
| `split` · `split-alt` | `.split` — ảnh một bên, chữ một bên |
| `gallery` | `.gallery-grid` + phóng ảnh bằng `:target` |
| `bleed` | `.story` — ảnh tràn, chữ đè lên |
| `cols-2` | `.editorial-grid` — thẻ ảnh + tiêu đề + mô tả + link riêng |
| `shortcuts` | `.shortcut-grid` — ô bấm đánh số 01–04, mỗi ô một link |
| `cols-1/3` · `slider` | `.model-grid` (slider về lưới vì không có JS) |

Mục `text` và `notice` đi qua `catalog_rich_text()` — dọn `<script>`,
`onclick`, link `javascript:` và thêm `rel` cho link ngoài, nhưng vẫn giữ
định dạng khi người nhập dán từ Word.

## Việc còn lại

**1. Viết lại 22 test bị skip** theo markup Lexus, hoặc xoá hẳn nếu thấy
`LexusFrontendTest` đã đủ.

**3. Header vẫn hardcode** bốn link trang tĩnh (Mua xe, Thế giới Lexus, Dịch
vụ, Người đồng hành). Menu đã được seed vào DB và quản lý được trong admin,
nhưng mega menu ba cột của bản thiết kế không map thẳng sang cây menu phẳng
nên chưa chuyển.

**4. Bộ lọc xe bằng CSS** chỉ chạy với slug danh mục `suv` · `sedan` · `mpv` ·
`hybrid` vì quy tắc `:has()` khai cứng trong style.css. Thêm danh mục slug
khác phải thêm quy tắc CSS, không thì bộ lọc tự lùi về link danh mục.

**5. Ảnh** hiện lấy từ bộ template (`public/assets/*.webp`) làm ảnh demo.
Upload ảnh thật trong admin là thay được, không phải sửa code.
