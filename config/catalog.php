<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\Dealer;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Lead;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Models\Province;
use App\Models\Redirect;
use App\Models\Setting;
use App\Models\Template;

/*
|--------------------------------------------------------------------------
| Cấu hình mặc định của core
|--------------------------------------------------------------------------
|
| Mỗi dự án publish file này ra config/catalog.php của mình rồi sửa
| `labels`, `features`, `section_presets`. Đây là thứ thay cho migration
| khi đổi hãng hoặc đổi mặt hàng — xem mục 5 của tài liệu kiến trúc.
|
*/

return [

    // Chữ hiển thị trong admin. Không bao giờ lấy từ tên cột.
    'labels' => [
        'product' => ['single' => 'Dòng xe',    'plural' => 'Dòng xe'],
        'variant' => ['single' => 'Phiên bản', 'plural' => 'Phiên bản'],
        'option' => ['single' => 'Góc nhìn',   'plural' => 'Góc nhìn ảnh'],
        'sections' => 'Chi tiết xe',
        'specs' => 'Thông số kỹ thuật',

        /*
         * Chữ trên các nút kêu gọi hành động. Gom về một chỗ vì nó nằm rải ở
         * cả chục chỗ: header, thẻ xe, hero, thanh dính đáy, coverflow, băng
         * ưu đãi. Đại lý đổi cách bán (đặt cọc → báo giá → tư vấn) thì sửa
         * đúng một dòng ở đây.
         */
        'cta' => [
            'deposit' => 'Nhận báo giá',
            'test_drive' => 'Đăng ký lái thử',
        ],
    ],

    // Bật/tắt từng khối. Admin ẩn khối tắt, API không trả field tương ứng.
    'features' => [
        'variants' => true,
        'options' => true,
        'specs' => true,
        'highlights' => true,
        'posts' => true,
        'pages' => true,
        'forms' => true,
        'dealers' => false,      // ngoài phạm vi — một showroom, không có mạng lưới đại lý
        'fee_calc' => false,     // ngoài phạm vi

        // So sánh chi phí nhiên liệu xe điện vs xe xăng/dầu ở trang chi tiết
        // xe — khác `fee_calc` (lệ phí lăn bánh). Cần biến thể có battery_kwh
        // + range_km, không thì mục tự ẩn dù bật.
        'fuel_calc' => false,    // so sánh điện/xăng — không hợp dòng Lexus hiện bán

        // Banner hero trang chủ. Tắt thì hero lùi về dùng ảnh của mặt hàng.
        // Banner hero trang chủ — khối duy nhất của trang chủ mà chủ website
        // muốn tự đổi. Chưa khai banner nào thì hero lùi về ảnh và chữ của
        // bản thiết kế, trang vẫn chạy.
        'banners' => true,

        // Bộ tính trả góp ở trang chi tiết xe. Chỉ tính khoản vay và lãi —
        // lệ phí lăn bánh do bộ phận khác lo, không thuộc phạm vi web này.
        'loan_calc' => false,    // ngoài phạm vi
    ],

    // Gợi ý tên khi bấm "Thêm mục" trong repeater sections.
    'section_presets' => ['Thư viện', 'Ngoại thất', 'Nội thất', 'Cảm giác lái', 'Công nghệ & an tâm'],

    // Layout cho phép chọn trong một mục.
    'section_layouts' => [
        'slider' => 'Slider',
        'cols-1' => '1 cột',
        'cols-2' => '2 cột',
        'cols-3' => '3 cột',

        // Bố cục dựng theo bản thiết kế — xem partials/section/media.blade.php.
        'gallery' => 'Thư viện lớn (1 ảnh to + 2 ảnh nhỏ)',
        'split' => 'Chữ một bên, ảnh một bên',
        'split-alt' => 'Chữ một bên, ảnh một bên (ảnh trước)',
        'carousel' => 'Băng chuyền (mũi tên chuyển ảnh)',
        'tabs' => 'Tab đánh số (01, 02, 03…)',
        // Thẻ ảnh chia nhóm bằng tab (Mâm xe / Nội thất / Ốp trang trí…):
        // nhóm = ô "Nhóm" của từng ảnh. Dùng cho chi tiết & tùy chọn ở trang xe.
        'groups' => 'Thẻ chia nhóm theo tab (mâm, nội thất, ốp…)',

        // Ba bố cục "điện ảnh", dựng theo cách các hãng xe cao cấp trình bày.
        'bleed' => 'Băng ảnh tràn hết màn hình',
        'sticky' => 'Ảnh đứng yên, chữ cuộn qua',
        'hotspot' => 'Ảnh có chấm tương tác (ảnh đầu là nền, các ảnh sau là điểm)',
        'feature-rows' => 'Nhiều điểm nhấn xếp hàng (mỗi ảnh một hàng, tự đảo bên)',

        // Cho mục kiểu `table`: mỗi dòng thành một ô chỉ số lớn thay vì
        // hàng bảng — dùng ở trang "Về chúng tôi".
        'stats' => 'Dải chỉ số (số to, nhãn nhỏ)',

        // Lưới ô bấm đánh số 01–04, mỗi ô một link — khối "Hành trình sở
        // hữu" của bản thiết kế. Mỗi ảnh trong mục thành một ô; ảnh không
        // dùng, chỉ lấy Nhãn + Mô tả + Link.
        'shortcuts' => 'Lưới ô bấm đánh số (01–04)',
    ],

    // Kiểu mục. 9/10 lần chỉ dùng `media`.
    'section_types' => [
        'media' => 'Ảnh',
        'text' => 'Văn bản',
        'notice' => 'Thông báo',
        'video' => 'Video',
        'table' => 'Bảng',
        'form' => 'Form',
        'custom' => 'Khối riêng',

        // Hỏi đáp: mỗi dòng là Câu hỏi | Câu trả lời. Render thành accordion
        // VÀ sinh JSON-LD FAQPage — dạng nội dung mà Google lẫn các công cụ
        // trả lời bằng AI (ChatGPT, Perplexity, AI Overviews) trích dẫn nhiều
        // nhất, vì mỗi cặp là một câu trả lời trọn vẹn, đứng độc lập được.
        'faq' => 'Hỏi đáp (FAQ)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | Màn hình Cài đặt dựng từ khai báo này — cần thêm mục thì khai báo ở đây,
    | KHÔNG thêm cột. Giá trị lưu vào bảng `settings` dạng key/value.
    |
    | Kiểu ô: text · textarea · url · email · number · image · toggle · color
    |
    */
    'settings' => [
        'general' => [
            'label' => 'Chung',
            'fields' => [
                'site_name' => ['label' => 'Tên website', 'type' => 'text'],

                // Thẻ <title> của trang chủ — nên có tên đại lý + khu vực.
                'seo_home_title' => ['label' => 'Tiêu đề trang chủ (SEO)', 'type' => 'text'],

                // Meta description của trang chủ.
                'site_description' => ['label' => 'Mô tả ngắn', 'type' => 'textarea'],

                'hotline' => ['label' => 'Hotline', 'type' => 'text'],
                'email' => ['label' => 'Email liên hệ', 'type' => 'email'],
                'address' => ['label' => 'Địa chỉ', 'type' => 'textarea'],
                'opening_hours' => ['label' => 'Giờ mở cửa', 'type' => 'text'],
                'company_name' => ['label' => 'Tên pháp nhân đầy đủ (hiện ở dòng bản quyền cuối trang)', 'type' => 'text'],
                'tax_code' => ['label' => 'Mã số thuế', 'type' => 'text'],
                'logo' => ['label' => 'Logo', 'type' => 'image'],
                'favicon' => ['label' => 'Favicon', 'type' => 'image'],
                'social_image' => ['label' => 'Ảnh chia sẻ mặc định (Open Graph)', 'type' => 'image'],
                'map_image' => ['label' => 'Ảnh bản đồ chỉ đường', 'type' => 'image'],
                'map_url' => ['label' => 'Link Google Maps', 'type' => 'url'],
                'visit_title' => ['label' => 'Tiêu đề thẻ liên hệ ở trang tĩnh', 'type' => 'text'],
                'brochure_url' => ['label' => 'Link brochure (nút ở trang chi tiết)', 'type' => 'url'],
            ],
        ],

        // Các khối nội dung của trang chủ. Khoá nào để trống thì cả khối tự
        // ẩn ở frontend — không có chữ mẫu chết trong view.
        'home' => [
            'label' => 'Trang chủ',
            'fields' => [
                'brand_sub' => ['label' => 'Dòng phụ cạnh tên (VD "Bắc Giang")', 'type' => 'text'],
                'header_cta' => ['label' => 'Nhãn nút bên phải header', 'type' => 'text'],
                'promo_text' => ['label' => 'Băng khuyến mãi trên cùng', 'type' => 'text'],
                'promo_url' => ['label' => 'Link băng khuyến mãi', 'type' => 'url'],

                'tools_note' => ['label' => 'Hành trình sở hữu — dòng nhỏ', 'type' => 'text'],
                'tools_title' => ['label' => 'Hành trình sở hữu — tiêu đề', 'type' => 'text'],
                'tools_text' => ['label' => 'Hành trình sở hữu — mô tả', 'type' => 'textarea'],
                'tools_image' => ['label' => 'Hành trình sở hữu — ảnh ô lớn (trống thì lấy ảnh xe đầu danh sách)', 'type' => 'image'],
                'tools_items' => ['label' => 'Hành trình sở hữu — các ô (mỗi dòng "Tên|Mô tả|Link", ô đầu là ô lớn có ảnh)', 'type' => 'textarea'],

                'offer_note' => ['label' => 'Ưu đãi — dòng nhỏ', 'type' => 'text'],
                'offer_title' => ['label' => 'Ưu đãi — tiêu đề', 'type' => 'text'],
                'offer_text' => ['label' => 'Ưu đãi — mô tả', 'type' => 'textarea'],
                'offer_image' => ['label' => 'Ưu đãi — ảnh (tràn mép phải, nên chọn ảnh xe nền sáng)', 'type' => 'image'],

                'charging_note' => ['label' => 'Pin & trạm sạc — dòng nhỏ', 'type' => 'text'],
                'charging_title' => ['label' => 'Pin & trạm sạc — tiêu đề', 'type' => 'text'],
                'charging_text' => ['label' => 'Pin & trạm sạc — mô tả', 'type' => 'textarea'],
                'charging_image' => ['label' => 'Pin & trạm sạc — ảnh', 'type' => 'image'],

                'care_note' => ['label' => 'Chăm sóc chủ xe — dòng nhỏ', 'type' => 'text'],
                'care_title' => ['label' => 'Chăm sóc chủ xe — tiêu đề', 'type' => 'text'],
                'care_image' => ['label' => 'Chăm sóc chủ xe — ảnh', 'type' => 'image'],
                'care_stats' => ['label' => 'Chăm sóc chủ xe — chỉ số (mỗi dòng "10 năm|Bảo hành xe và pin")', 'type' => 'textarea'],

                // Tư vấn viên đồng hành: chân dung + số gọi trực tiếp. Khác
                // các khối trên, khối này có sẵn nội dung mặc định trong view
                // (ảnh ở public/assets/images) — chỉ tắt khi bật `advisor_off`.
                'advisor_off' => ['label' => 'Tư vấn viên — ẩn khối này', 'type' => 'toggle'],
                'advisor_name' => ['label' => 'Tư vấn viên — họ tên (VD "Hữu Lập")', 'type' => 'text'],
                'advisor_role' => ['label' => 'Tư vấn viên — chức danh (mặc định "Tư vấn bán hàng")', 'type' => 'text'],
                'advisor_phone' => ['label' => 'Tư vấn viên — số gọi trực tiếp (trống thì dùng Hotline)', 'type' => 'text'],
                'advisor_zalo' => ['label' => 'Tư vấn viên — Zalo (số hoặc link, trống thì dùng Zalo chung)', 'type' => 'text'],
                'advisor_title' => ['label' => 'Tư vấn viên — tiêu đề (dòng 1)', 'type' => 'text'],
                'advisor_title_2' => ['label' => 'Tư vấn viên — tiêu đề (dòng 2, màu nhấn)', 'type' => 'text'],
                'advisor_text' => ['label' => 'Tư vấn viên — đoạn giới thiệu', 'type' => 'textarea'],
                'advisor_quote' => ['label' => 'Tư vấn viên — câu trích dẫn', 'type' => 'textarea'],
                'advisor_points' => ['label' => 'Tư vấn viên — 3 cam kết (mỗi dòng "Tiêu đề|Mô tả")', 'type' => 'textarea'],
                'advisor_image' => ['label' => 'Tư vấn viên — ảnh chân dung (dọc)', 'type' => 'image'],

                // Ghé thăm showroom: 1 ảnh lớn + 2 ảnh nhỏ. Địa chỉ và link
                // chỉ đường lấy từ tab Chung (address, map_url).
                'showroom_off' => ['label' => 'Showroom — ẩn khối này', 'type' => 'toggle'],
                'showroom_note' => ['label' => 'Showroom — dòng nhỏ', 'type' => 'text'],
                'showroom_title' => ['label' => 'Showroom — tiêu đề', 'type' => 'text'],
                'showroom_image_1' => ['label' => 'Showroom — ảnh lớn', 'type' => 'image'],
                'showroom_caption_1' => ['label' => 'Showroom — chú thích ảnh lớn', 'type' => 'text'],
                'showroom_image_2' => ['label' => 'Showroom — ảnh nhỏ 1', 'type' => 'image'],
                'showroom_caption_2' => ['label' => 'Showroom — chú thích ảnh nhỏ 1', 'type' => 'text'],
                'showroom_image_3' => ['label' => 'Showroom — ảnh nhỏ 2', 'type' => 'image'],
                'showroom_caption_3' => ['label' => 'Showroom — chú thích ảnh nhỏ 2', 'type' => 'text'],
            ],
        ],

        // Nhóm "Popup thu lead" của core đã gỡ: popup của bản Lexus cấu hình
        // ở catalog.frontend.popup, không qua admin (theo yêu cầu chủ website).

        // Trang Trạm sạc & dịch vụ — xem frontend/services.blade.php.
        'service' => [
            'label' => 'Trạm sạc & dịch vụ',
            'fields' => [
                'service_note' => ['label' => 'Dòng nhỏ trên tiêu đề', 'type' => 'text'],
                'service_title' => ['label' => 'Tiêu đề trang', 'type' => 'text'],
                'service_description' => ['label' => 'Mô tả SEO', 'type' => 'textarea'],
                'service_map' => ['label' => 'Ảnh bản đồ trạm sạc', 'type' => 'image'],

                'stations' => ['label' => 'Danh sách trạm (mỗi dòng "Tên|Trạng thái|Thông tin|ok hoặc warn")', 'type' => 'textarea'],
                'stations_api' => ['label' => 'Endpoint tìm trạm riêng (để trống dùng nguồn miễn phí)', 'type' => 'url'],
                'stations_more' => ['label' => 'Nhãn nút xem thêm trạm', 'type' => 'text'],
                'stations_more_url' => ['label' => 'Link nút xem thêm trạm', 'type' => 'url'],

                'services_title' => ['label' => 'Tiêu đề khối dịch vụ', 'type' => 'text'],
                'services' => ['label' => 'Dịch vụ (mỗi dòng "Tên|Mô tả|Nhãn nút|Link")', 'type' => 'textarea'],
            ],
        ],
        'social' => [
            'label' => 'Mạng xã hội',
            'fields' => [
                'facebook' => ['label' => 'Facebook', 'type' => 'url'],
                'youtube' => ['label' => 'YouTube', 'type' => 'url'],
                'tiktok' => ['label' => 'TikTok', 'type' => 'url'],
                'zalo' => ['label' => 'Zalo', 'type' => 'text'],
            ],
        ],
        'tracking' => [
            'label' => 'Đo lường',
            'fields' => [
                'gtm_id' => ['label' => 'Google Tag Manager ID', 'type' => 'text'],
                'facebook_pixel' => ['label' => 'Facebook Pixel ID', 'type' => 'text'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    | Core không hardcode Product::class. Dự án nào cần thêm hành vi thì
    | extend model của core rồi trỏ lại ở đây.
    */
    'models' => [
        'product' => Product::class,
        'banner' => Banner::class,
        'variant' => ProductVariant::class,
        'option' => ProductOption::class,
        'category' => Category::class,
        'post' => Post::class,
        'post_category' => PostCategory::class,
        'page' => Page::class,
        'menu' => Menu::class,
        'menu_item' => MenuItem::class,
        'setting' => Setting::class,
        'redirect' => Redirect::class,
        'form' => Form::class,
        'form_field' => FormField::class,
        'lead' => Lead::class,
        'template' => Template::class,
        'province' => Province::class,
        'dealer' => Dealer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */
    'api' => [
        'enabled' => true,
        'prefix' => 'api/v1',
        'middleware' => ['api'],
        'per_page' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Giá lăn bánh tạm tính (thẻ phiên bản ở trang xe)
    |--------------------------------------------------------------------------
    |
    | Chỉ gồm hai khoản lớn, cố định theo luật: lệ phí trước bạ lần đầu và phí
    | cấp biển số. Phí đăng kiểm, bảo trì đường bộ, bảo hiểm TNDS (vài triệu
    | đồng) không cộng — trang ghi rõ "chưa gồm". Luật đổi thì sửa ở đây.
    | Nhận diện xe điện chạy pin: tên phiên bản dạng "500e" hoặc ghi chú có
    | chữ "điện" (không tính "hybrid").
    */
    'on_road' => [
        'region'       => 'Hà Nội',
        'tax_rate'     => 0.12,  // Hà Nội: ô tô con chạy xăng/hybrid 12%
        'ev_tax_rate'  => 0.0,   // ô tô điện chạy pin 0% đến hết 2030 (NĐ 202/2026)
        'plate_fee'    => 14_000_000, // Hà Nội từ 1/1/2026 (TT 155/2025/TT-BTC)
    ],

    /*
    |--------------------------------------------------------------------------
    | Lead (form khách gửi)
    |--------------------------------------------------------------------------
    */
    'leads' => [
        // Ô bẫy bot: form frontend thêm một input ẩn tên này. Người thật để
        // trống; bot điền vào thì bỏ qua lặng lẽ, vẫn trả 201 để bot không dò được.
        'honeypot' => 'website',

        // Cùng form + cùng số điện thoại trong bao nhiêu phút thì coi là trùng,
        // không tạo lead mới. 0 = tắt.
        'dedupe_minutes' => 5,

        // Email nhận thông báo lead mới, ghi vào form lúc `db:seed`
        // (LexusSiteSeeder). Đọc qua config để vẫn chạy sau `php artisan optimize`.
        'notify_emails' => array_values(array_filter(array_map('trim',
            explode(',', (string) env('LEAD_NOTIFY_EMAILS', ''))))),
    ],

    // Tài khoản quản trị tạo lúc `db:seed` — xem DatabaseSeeder, DEPLOY.md.
    'admin' => [
        'email'    => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend (Blade)
    |--------------------------------------------------------------------------
    |
    | Số lượng và khoá menu/form của trang khách xem. Mỗi hãng đổi ở đây,
    | không sửa controller.
    |
    */
    'frontend' => [
        'per_page' => 12,       // danh sách sản phẩm / tin tức

        'home' => [
            'products' => 24,   // trần số xe đưa vào coverflow trang chủ
            'posts' => 3,    // số tin mới nhất
        ],

        // Khoá menu dựng ở màn hình Menu. Chưa tạo thì phần đó không render.
        'menus' => [
            'header' => 'header',
            'footer' => 'footer',
        ],

        // Form hiện ở cuối trang chi tiết sản phẩm — key nào đã nhúng giữa
        // trang qua mục kiểu `form` (VD "dat-lich-lai-thu" gắn tự động cho
        // mọi xe trong BrandSeeder) thì không lặp lại ở đây. [] = không hiện.
        'product_forms' => ['nhan-bao-gia'],

        // Form ở băng đăng ký nhận tin ngay trên footer — chỉ cần ô email.
        // null = ẩn hẳn băng đó.
        'newsletter_form' => null,      // footer Lexus không có băng đăng ký nhận tin

        /*
        | Trang "Đặt cọc & lái thử" (/dat-coc) — wizard 3 bước.
        |
        | `forms` là các form khách chọn ở đầu trang, theo thứ tự tab; mỗi
        | form vẫn POST vào /gui-form/{form} như mọi form khác nên honeypot,
        | chống trùng và mail y hệt. null = tắt hẳn trang.
        */
        'booking' => [
            'forms' => ['dang-ky-lai-thu'],

            // Ô nào lên bước 1 (đứng cạnh bộ chọn xe); còn lại xuống bước 2.
            'step1_fields' => ['location_type'],

            // Ô select nào hiện thành lưới thẻ bấm thay vì dropdown. Quá số
            // lựa chọn này thì tự về dropdown cho khỏi vỡ lưới.
            'card_fields' => ['payment_method', 'preferred_time', 'location_type'],
            'card_max_options' => 4,

            // Số tiền cọc hiện ở bảng tóm tắt bước 2. null = ẩn bảng đó.
            'deposit' => null,      // không thu cọc qua web
        ],

        // Danh mục dùng cho trang Phụ kiện (/phu-kien). Mặt hàng thuộc danh
        // mục này KHÔNG hiện ở trang chủ và danh sách xe — phụ kiện lẫn vào
        // dải xe là lỗi thấy ngay. null = tắt hẳn trang.
        'accessory_category' => null,   // không bán phụ kiện trên web này

        // Trang Trạm sạc & dịch vụ (/tram-sac-dich-vu). Nội dung lấy từ
        // Cài đặt → Trạm sạc & dịch vụ. false = tắt hẳn trang.
        'services_page' => false,       // trang trạm sạc là của bản VinFast

        /*
        | Popup nhận báo giá tự bật ở trang chủ — partials/quote-dialog +
        | public/assets/lead.js. Chủ website chọn KHÔNG quản trị trong admin,
        | nên các con số nằm ở đây.
        |
        | Chọn 20 giây, dựa trên (22.09.2026):
        |   · đo trực tiếp: đại lý VinFast Thăng Long bật ở 15s, đại lý Toyota
        |     Thanh Xuân ở 25s; site hãng VinFast và Lexus VN không bật popup
        |   · Omnisend (1,24 tỉ lượt hiển thị): trễ 6–10s chuyển đổi 2,4% so
        |     với 1,9% khi bật ngay; nhưng chính họ khuyên trang chủ khách mới
        |     nên chờ 30–45s — tức là trễ ngắn tối đa chuyển đổi, trễ dài bớt
        |     khó chịu
        |   · NN/g: không bật popup trước khi khách kịp thấy giá trị của trang
        |   20s đủ để khách xem hero và lướt tới bộ sưu tập xe, hợp với chất
        |   "quiet luxury" của đặc tả hơn mức 15s của đại lý.
        |
        | mobile = false: Google coi hộp che gần hết màn hình điện thoại là
        | "intrusive interstitial", có thể kéo hạng tìm kiếm. Trên điện thoại
        | popup vẫn mở khi khách TỰ bấm nút báo giá — chỉ không tự bật.
        |
        | Hai site đại lý đo được đều bật lại MỖI LẦN tải trang — đó là lỗi,
        | không nên chép. Ở đây: đã tự bật một lần thì im dismiss_days ngày
        | (tính từ lúc bật, nên khách đóng tab ngang cũng được tính), đã gửi
        | form thì im sent_days ngày, và mỗi phiên tối đa một lần.
        */
        'popup' => [
            'enabled'      => true,
            'delay'        => 20,   // giây kể từ khi trang hiện ra (không tính lúc tab bị ẩn)
            'mobile'       => false,
            'dismiss_days' => 7,
            'sent_days'    => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fuel calculator (giá điện/nhiên liệu tham khảo)
    |--------------------------------------------------------------------------
    |
    | Đổi theo giá điện/xăng dầu thực tế của thời điểm — không phải báo giá
    | chính thức. electricity_price tính cho sạc tại nhà.
    */
    'fuel_calc' => [
        'electricity_price' => 3900,   // đ/kWh
        'petrol_price' => 24500, // đ/lít xăng
        'diesel_price' => 21500, // đ/lít dầu
    ],

    /*
    |--------------------------------------------------------------------------
    | Trả góp (giá trị mặc định của bộ tính)
    |--------------------------------------------------------------------------
    |
    | Lãi suất tham khảo, không phải cam kết của ngân hàng. Đổi theo thời điểm.
    */
    'loan' => [
        'down_payment_percent' => 30,     // % trả trước gợi ý sẵn
        'annual_rate' => 9.0,             // %/năm
        'months' => 60,                   // kỳ trả mặc định
        'month_options' => [12, 24, 36, 48, 60, 72, 84],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin (Filament)
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'navigation_group' => 'Nội dung',
    ],

    /*
    |--------------------------------------------------------------------------
    | Đường dẫn frontend & SEO
    |--------------------------------------------------------------------------
    |
    | Tiền tố URL của từng loại trang. Dùng để:
    |   - suy ra link cho mục menu (target_type → url)
    |   - tự tạo redirect 301 khi đổi slug bản đã publish
    |   - dựng sitemap.xml
    |
    | Đây là hình dạng URL của frontend, mỗi hãng đổi được mà không sửa core.
    */
    'routes' => [
        'product' => '/san-pham',
        'category' => '/danh-muc',
        'post' => '/tin-tuc',
        'post_category' => '/chuyen-muc',
        'page' => '',   // trang tĩnh nằm ngay gốc: /gioi-thieu

        // Trang cố định duy nhất còn lại (không theo slug). Bật/tắt ở
        // `frontend` bên dưới, không phải ở đây.
        'booking' => '/dang-ky-lai-thu',
        'quote' => '/bao-gia',
    ],

    'seo' => [
        // Bật sitemap.xml tại {APP_URL}/sitemap.xml
        'sitemap' => true,

        // Loại trang đưa vào sitemap
        'sitemap_includes' => ['product', 'category', 'post', 'page'],

        // Trang tĩnh KHÔNG đưa vào sitemap/llms.txt: `trang-chu` chỉ là kho
        // khối nội dung của trang chủ, không phải trang cho khách đọc.
        'sitemap_exclude_pages' => ['trang-chu'],

        // Route tĩnh có nội dung cần Google lập chỉ mục. Search/compare
        // cố ý không có mặt vì sinh nhiều URL theo query string.
        'sitemap_routes' => [
            'home', 'products.index', 'booking', 'accessories',
            'dealers', 'services', 'posts.index',
        ],

        // Hãng xe đại lý phân phối — Brand trong JSON-LD của xe và đại lý.
        'brand' => 'Lexus',

        // Tổ chức đứng sau — dùng cho JSON-LD Organization (AutoDealer).
        // Địa chỉ tách trường để Google/AI hiểu đúng quận, thành phố — tín
        // hiệu chính cho tìm kiếm địa phương ("đại lý Lexus Cầu Giấy").
        'organization' => [
            'type' => 'AutoDealer',
            'name' => null,   // null thì lấy settings('site_name')
            'logo' => null,   // null thì lấy settings('logo')
            'sameAs' => [],     // link mạng xã hội
            'address' => [
                'streetAddress'   => 'Ngã tư Phạm Hùng – Dương Đình Nghệ',
                'addressLocality' => 'Cầu Giấy',
                'addressRegion'   => 'Hà Nội',
                'addressCountry'  => 'VN',
            ],
            // Cú pháp schema.org: "Mo-Su 08:00-18:00", mỗi dòng một khung.
            'opening_hours' => ['Mo-Su 08:00-18:00'],
            'area_served'   => 'Hà Nội',
        ],
    ],

];
