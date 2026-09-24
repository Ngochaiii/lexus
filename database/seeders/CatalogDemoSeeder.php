<?php

namespace Database\Seeders;

use App\Support\Catalog;
use App\Support\Url;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Khung của một site: cài đặt, menu, form đặt lịch, một trang tĩnh, một bài
 * viết. **Không có xe nào** — xe do seeder theo hãng lo
 * (`database/seeders/Brands/`), để đổi hãng không phải sửa file này.
 *
 * Chạy lại được nhiều lần (updateOrCreate).
 */
class CatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->form();
        $this->content();
        $this->settings();
        $this->accessories();
        $this->banners();
        $this->dealers();
        $this->menus();
    }

    /**
     * Hai form ở trang chi tiết xe:
     *   - "dat-lich-lai-thu": brand seeder tự nhúng giữa trang (xem
     *     BrandSeeder::formKey()/sections()).
     *   - "dat-coc": nằm cuối trang, khai ở config('catalog.frontend.product_forms').
     */
    protected function form(): void
    {
        $testDrive = Catalog::query('form')->updateOrCreate(
            ['key' => 'dat-lich-lai-thu'],
            [
                'name' => 'Đặt lịch lái thử',
                'description' => 'Để lại thông tin, tư vấn viên sẽ liên hệ lại.',
                'success_message' => 'Đã nhận thông tin, chúng tôi sẽ liên hệ sớm.',
            ],
        );

        $testDrive->fields()->delete();
        $testDrive->fields()->createMany([
            ['key' => 'name', 'label' => 'Họ tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1, 'width' => 'half'],
            ['key' => 'phone', 'label' => 'Điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2, 'width' => 'half'],
            ['key' => 'preferred_time', 'label' => 'Khung giờ mong muốn', 'type' => 'select', 'rules' => ['nullable'], 'sort' => 3, 'width' => 'half',
                'options' => ['sang-t7' => 'Sáng thứ 7', 'chieu-t7' => 'Chiều thứ 7', 'sang-cn' => 'Sáng chủ nhật', 'chieu-cn' => 'Chiều chủ nhật']],
            ['key' => 'location_type', 'label' => 'Hình thức', 'type' => 'select', 'rules' => ['nullable'], 'sort' => 4, 'width' => 'half',
                'options' => ['tai-nha' => 'Lái thử tại nhà', 'showroom' => 'Tại showroom']],
            ['key' => 'note', 'label' => 'Ghi chú', 'type' => 'textarea', 'rules' => ['nullable'], 'sort' => 5],
        ]);

        $deposit = Catalog::query('form')->updateOrCreate(
            ['key' => 'dat-coc'],
            [
                // Khoá vẫn là `dat-coc` để không gãy link cũ và lead đã lưu;
                // chỉ phần chữ hiện ra cho khách là đổi theo cách bán hiện tại.
                'name' => 'Báo giá',
                'description' => 'Nhận bảng giá lăn bánh và ưu đãi đang chạy cho đúng phiên bản bạn quan tâm.',
                'success_message' => 'Đã nhận yêu cầu báo giá, tư vấn viên sẽ gọi lại trong vòng 2 giờ làm việc.',
            ],
        );

        $deposit->fields()->delete();
        $deposit->fields()->createMany([
            ['key' => 'name', 'label' => 'Họ và tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1, 'width' => 'half'],
            ['key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2, 'width' => 'half'],
            ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'rules' => ['nullable'], 'sort' => 3, 'width' => 'half'],
            ['key' => 'payment_method', 'label' => 'Phương thức thanh toán cọc', 'type' => 'select', 'rules' => ['nullable'], 'sort' => 4, 'width' => 'half',
                'options' => ['qr' => 'Chuyển khoản QR', 'the' => 'Thẻ ngân hàng', 'showroom' => 'Tại showroom']],
            ['key' => 'note', 'label' => 'Ghi chú', 'type' => 'textarea', 'rules' => ['nullable'], 'sort' => 5],
        ]);

        // Form cuối trang chi tiết xe — khai ở
        // config('catalog.frontend.product_forms'). Bản thiết kế chỉ có MỘT
        // form ở trang chi tiết: chỉ hỏi thông tin đủ để gọi lại và mẫu xe
        // khách đang quan tâm.
        $consult = Catalog::query('form')->updateOrCreate(
            ['key' => 'dang-ky-tu-van'],
            [
                'name' => 'Đăng ký tư vấn',
                'description' => 'Chọn mẫu xe bạn quan tâm, tư vấn viên sẽ liên hệ và gửi thông tin phù hợp.',
                'success_message' => 'Đã nhận thông tin của bạn. Tư vấn viên sẽ liên hệ trong vòng 2 giờ làm việc.',
            ],
        );

        $consult->fields()->delete();
        $consult->fields()->createMany([
            ['key' => 'name', 'label' => 'Họ và tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1,
                'placeholder' => 'Nguyễn Văn A', 'width' => 'half'],

            ['key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2,
                'placeholder' => '09xx xxx xxx', 'width' => 'half'],

            ['key' => 'product_id', 'label' => 'Mẫu xe quan tâm', 'type' => 'product',
                'rules' => ['required'], 'sort' => 3, 'placeholder' => '— Chọn mẫu xe —'],

            // Nghị định 13/2023: câu đồng ý phải dẫn được tới chính sách bảo vệ
            // dữ liệu. Cú pháp [chữ](đường-dẫn) do catalog_field_label() dựng
            // thành link — nhãn gõ trong admin nên không gõ thẻ HTML được.
            ['key' => 'agree', 'label' => 'Đồng ý xử lý dữ liệu', 'type' => 'checkbox',
                'rules' => ['required'], 'sort' => 4,
                'options' => ['1' => 'Tôi đồng ý cho đại lý xử lý dữ liệu cá nhân của tôi theo '
                    .'[Chính sách bảo vệ dữ liệu cá nhân](/chinh-sach-bao-mat).']],
        ]);

        // Băng đăng ký nhận tin trên footer — khai ở
        // config('catalog.frontend.newsletter_form'), chỉ một ô email.
        $newsletter = Catalog::query('form')->updateOrCreate(
            ['key' => 'dang-ky-nhan-tin'],
            [
                'name' => 'Đăng ký nhận thông tin',
                'description' => 'Chương trình khuyến mãi và tin dịch vụ từ đại lý — 1–2 email mỗi tháng.',
                'success_message' => 'Đã đăng ký! Hẹn gặp bạn trong hộp thư.',
            ],
        );

        $newsletter->fields()->delete();
        $newsletter->fields()->createMany([
            ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'rules' => ['required', 'email'], 'sort' => 1],
        ]);
    }

    /** Trang tĩnh + bài viết — dùng đúng cơ chế `sections` của sản phẩm. */
    protected function content(): void
    {
        Catalog::query('page')->updateOrCreate(
            ['slug' => 'gioi-thieu'],
            [
                'title' => 'Giới thiệu',
                'status' => 'published',
                // Bám bố cục "Về chúng tôi" của bản thiết kế: ảnh mặt tiền,
                // hai đoạn giới thiệu, rồi dải chỉ số (layout `stats`).
                'sections' => [
                    [
                        'type' => 'media',
                        'layout' => 'cols-1',
                        'items' => [['label' => 'Ảnh mặt tiền showroom']],
                    ],
                    [
                        'type' => 'text',
                        'body' => "Đại lý ủy quyền chính hãng, showroom trưng bày đủ dải xe, khu lái thử riêng và xưởng dịch vụ đạt chuẩn hãng.\nSửa nội dung trong admin → Trang.",
                    ],
                    [
                        'type' => 'table',
                        'layout' => 'stats',
                        'rows' => [
                            ['value' => '2022', 'label' => 'Năm thành lập'],
                            ['value' => '8 khoang', 'label' => 'Xưởng dịch vụ'],
                            ['value' => '4,9/5', 'label' => 'Đánh giá khách hàng'],
                        ],
                    ],
                ],
            ],
        );

        $postCategory = Catalog::query('post_category')->firstOrCreate(
            ['slug' => 'trai-nghiem'],
            ['name' => 'Trải nghiệm'],
        );

        Catalog::query('post')->updateOrCreate(
            ['slug' => 'bai-viet-mau'],
            [
                'title' => 'Bài viết mẫu',
                'excerpt' => 'Tóm tắt ngắn hiện ở thẻ bài viết.',
                'post_category_id' => $postCategory->id,
                'status' => 'published',
                'published_at' => now()->subDays(3),
                'sections' => [[
                    'title' => 'Đoạn một',
                    'type' => 'text',
                    'body' => 'Bài viết dùng chung bộ mục với trang xe.',
                ]],
            ],
        );
    }

    /**
     * Banner hero trang chủ.
     *
     * Chỉ khai khi tính năng đang bật. Xoá sạch bảng này thì trang chủ lùi về
     * dùng ảnh của ba mặt hàng đầu, không vỡ.
     */
    protected function banners(): void
    {
        if (! Catalog::feature('banners')) {
            return;
        }

        $rows = [
            [
                'title' => 'Trả góp 0% lãi suất 24 tháng',
                'eyebrow' => 'Ưu đãi trong tháng',
                'subtitle' => 'Kèm gói lắp sạc tại nhà miễn phí công lắp đặt cho khách đặt cọc tại đại lý.',
                'cta_label' => 'Xem chương trình',
                'cta_url' => Url::prefix('post') ?: '/',
            ],
            [
                'title' => 'Lái thử cuối tuần tại showroom',
                'eyebrow' => 'Sự kiện',
                'subtitle' => 'Đăng ký trước để giữ khung giờ, có xe cho cả gia đình cùng trải nghiệm.',
                'cta_label' => 'Đăng ký lái thử',
                'cta_url' => Url::prefix('booking') ?: '/',
            ],
        ];

        foreach ($rows as $i => $row) {
            Catalog::query('banner')->updateOrCreate(
                ['title' => $row['title']],
                $row + ['sort' => $i + 1, 'is_active' => true],
            );
        }
    }

    /**
     * Đại lý mẫu, nhóm theo tỉnh.
     *
     * Cố ý để một đại lý KHÔNG có toạ độ: trang đại lý phải chứng minh được
     * rằng thiếu toạ độ thì không dựng nút Chỉ đường.
     */
    protected function dealers(): void
    {
        if (! Catalog::feature('dealers')) {
            return;
        }

        $province = Catalog::query('province')->updateOrCreate(
            ['name' => 'Bắc Giang'],
            ['code' => '24'],
        );

        Catalog::query('dealer')->updateOrCreate(
            ['name' => 'Showroom Xương Giang'],
            [
                'province_id' => $province->id,
                'address' => 'Đường Xương Giang, TP. Bắc Giang',
                'phone' => '0204 123 456',
                'lat' => 21.2731,
                'lng' => 106.1946,
                'opening_hours' => ['T2–T7: 8:00–19:00', 'CN: 8:00–17:00'],
            ],
        );

        Catalog::query('dealer')->updateOrCreate(
            ['name' => 'Xưởng dịch vụ Vân Trung'],
            [
                'province_id' => $province->id,
                'address' => 'KCN Vân Trung, Việt Yên',
                'phone' => '0204 987 654',
                'opening_hours' => ['T2–T7: 8:00–17:30'],
            ],
        );
    }

    /** Cài đặt: những giá trị layout frontend đọc ra ngay. */
    protected function settings(): void
    {
        $setting = Catalog::model('setting');

        $setting::put('site_name', 'Website ô tô demo');
        $setting::put('site_description', 'Dữ liệu mẫu để soi thử admin, API và luồng frontend.');
        $setting::put('hotline', '1900 0000');
        $setting::put('email', 'demo@example.test');
        $setting::put('address', 'Số 1, đường Demo, Hà Nội');
        $setting::put('opening_hours', 'Mở cửa 8:00–19:00 hằng ngày');

        // Các khoá dưới đây frontend đọc ra nếu có, bỏ trống thì khối tương
        // ứng tự ẩn — xem partials/header.blade.php và home.blade.php.
        $setting::put('brand_sub', 'Đại lý demo');
        $setting::put('promo_text', 'Trả góp 0% lãi suất 24 tháng — xem chương trình đang chạy');
        $setting::put('offer_note', 'Ưu đãi trong tháng');
        $setting::put('offer_title', 'Trả góp 0% lãi suất 24 tháng cho các dòng xe điện');
        $setting::put('offer_text', 'Kèm gói lắp sạc tại nhà miễn phí công lắp đặt cho khách đặt cọc tại đại lý.');

        $setting::put('charging_note', 'Pin & trạm sạc');
        $setting::put('charging_title', 'Sạc đầy trong lúc bạn đi chợ');
        $setting::put('charging_text', 'Mạng lưới điểm sạc phủ khắp tỉnh, sạc nhanh 10–70% dưới 30 phút, kèm dịch vụ khảo sát và lắp đặt sạc tại nhà miễn phí công lắp.');

        $setting::put('care_note', 'Chăm sóc chủ xe');
        $setting::put('care_title', 'Yên tâm suốt 10 năm sở hữu');
        // Dạng "giá trị|nhãn", mỗi mục một dòng (dấu chấm phẩy cũng được).
        $setting::put('care_stats', '10 năm|Bảo hành xe và pin;24/7|Cứu hộ lưu động toàn tỉnh;45 phút|Thời gian cứu hộ trung bình;4,9/5|Điểm hài lòng dịch vụ');

        // Trang Trạm sạc & dịch vụ + công cụ tìm trạm ở trang chủ. Cột 4 là
        // ok | warn (màu chữ trạng thái), cột 5 là "lat,lng" hoặc địa chỉ chữ:
        // có toạ độ thì tính được khoảng cách tới khách và chỉ đường đúng
        // điểm. Toạ độ dưới đây là số ước lượng của bản demo.
        $setting::put('service_note', 'Trạm sạc & dịch vụ');
        $setting::put('service_title', 'Sạc và bảo dưỡng, ngay trong tỉnh');
        $setting::put('stations', implode("\n", [
            'Trạm Vincom|Còn 6/8 cổng|DC 150 kW · Mở 24/7|ok|21.27310,106.19460',
            'Trạm Big C|Còn 3/6 cổng|DC 60 kW · 8:00–22:00|ok|21.28960,106.18520',
            'Trạm Bến xe khách|Còn 1/10 cổng|DC 250 kW · Mở 24/7|warn|21.27950,106.18850',
            'Trạm KCN Vân Trung|Còn 8/12 cổng|AC 11 kW + DC 60 kW|ok|21.23810,106.08050',
        ]));
        // Để trống `stations_api` là frontend lọc ngay trên danh sách trên.
        // Điền URL vào khoá này khi có dịch vụ tìm trạm thật — xem hợp đồng
        // JSON ở đầu partials/station-finder.blade.php.
        $setting::put('stations_api', '');
        // Nhãn không kèm link thì frontend ẩn nút — điền cả hai hoặc bỏ cả hai.
        $setting::put('stations_more', 'Xem toàn bộ trạm trong tỉnh');
        $setting::put('stations_more_url', 'https://www.google.com/maps/search/tr%E1%BA%A1m+s%E1%BA%A1c');

        $setting::put('services_title', 'Dịch vụ tại showroom');
        $setting::put('services', implode("\n", [
            'Bảo dưỡng định kỳ|Đặt lịch trước, nhận xe trong ngày. Xe thay thế miễn phí cho bảo dưỡng trên 4 giờ.|Đặt lịch',
            'Lắp sạc tại nhà|Khảo sát miễn phí, lắp đặt bộ sạc AC 7,4 kW đạt chuẩn an toàn trong 1 buổi.|Đăng ký khảo sát',
            'Cứu hộ 24/7|Đội cứu hộ lưu động toàn tỉnh, có mặt trung bình trong 45 phút.|Hotline cứu hộ',
        ]));

        $setting::put('visit_title', 'Ghé thăm showroom');
        $setting::put('map_url', 'https://www.google.com/maps');
    }

    /**
     * Danh mục phụ kiện + vài mặt hàng mẫu cho trang /phu-kien.
     *
     * Phụ kiện là mặt hàng bình thường, chỉ khác danh mục — danh mục này khai
     * ở config('catalog.frontend.accessory_category') và bị loại khỏi trang
     * chủ lẫn /san-pham.
     */
    protected function accessories(): void
    {
        $slug = config('catalog.frontend.accessory_category');

        if (blank($slug)) {
            return;
        }

        $category = Catalog::query('category')->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => 'Phụ kiện xe',
                'description' => 'Phụ kiện chính hãng, lắp đặt tại showroom.',
                'sort' => 90,
            ],
        );

        $items = [
            ['Bộ sạc treo tường AC 11 kW', 11790000],
            ['Sạc di động 2,2 kW', 6500000],
            ['Thảm sàn 3D theo xe', 2400000],
            ['Camera hành trình trước/sau', 3200000],
            ['Ô dù gấp 2 tầng', 414000],
            ['Áo phủ xe chống nắng', 1150000],
            ['Bơm lốp điện mini', 890000],
            ['Mô hình xe tỉ lệ 1:24', 2074000],
        ];

        foreach ($items as $i => [$name, $price]) {
            Catalog::query('product')->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'category_id' => $category->id,
                    'price_from' => $price,
                    'status' => 'published',
                    'published_at' => now()->subDay(),
                    'sort' => $i + 1,
                ],
            );
        }
    }

    /**
     * Menu header + footer. Mục "Dòng xe" để trống mục con — brand seeder gắn
     * từng xe vào dưới đó, nên đổi hãng không phải sửa menu ở đây.
     */
    protected function menus(): void
    {
        $header = Catalog::query('menu')->updateOrCreate(['key' => 'header'], ['name' => 'Menu chính']);
        $header->items()->delete();

        $header->items()->create([
            'label' => catalog_label('product.plural'),
            'url' => Url::prefix('product') ?: '/',
            'sort' => 1,
        ]);
        if (Route::has('accessories')) {
            $header->items()->create(['label' => 'Phụ kiện', 'url' => Url::prefix('accessory'), 'sort' => 2]);
        }

        if (Route::has('services')) {
            $header->items()->create(['label' => 'Trạm sạc & Dịch vụ', 'url' => Url::prefix('service'), 'sort' => 3]);
        }

        $header->items()->create(['label' => 'Tin tức', 'url' => Url::prefix('post'), 'sort' => 4]);
        $header->items()->create(['label' => 'Giới thiệu', 'url' => '/gioi-thieu', 'sort' => 5]);

        $footer = Catalog::query('menu')->updateOrCreate(['key' => 'footer'], ['name' => 'Menu chân trang']);
        $footer->items()->delete();
        $footer->items()->create(['label' => 'Giới thiệu', 'url' => '/gioi-thieu', 'sort' => 1]);
        $footer->items()->create(['label' => 'Tin tức', 'url' => Url::prefix('post'), 'sort' => 2]);
    }
}
