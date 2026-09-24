<?php

namespace Database\Seeders;

use App\Support\Catalog;
use App\Support\Url;
use Illuminate\Database\Seeder;

/**
 * Khung site Lexus Thăng Long: cài đặt, hai form thu lead, các trang tĩnh
 * mà giao diện có link tới.
 *
 * Thay cho CatalogDemoSeeder của bản VinFast — form ở đó (`dat-coc`,
 * `dang-ky-tu-van`) không khớp khoá mà view Lexus gọi.
 *
 * Thông tin liên hệ là THẬT, do chủ website cung cấp. Giá xe trong
 * Brands\LexusSeeder là dữ liệu minh hoạ.
 */
class LexusSiteSeeder extends Seeder
{
    public function run(): void
    {
        $this->settings();
        $this->forms();
        $this->pages();
        $this->homePage();
        $this->banner();
        $this->posts();
        $this->menus();
    }

    private function settings(): void
    {
        $values = [
            'site_name'        => 'Lexus Thăng Long',
            'site_description' => 'Lexus Thăng Long — đại lý Lexus chính hãng tại ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy, Hà Nội. '
                .'Bảng giá 7 dòng xe Lexus 2026, lái thử và báo giá lăn bánh cùng chuyên viên Thu Hà.',
            'seo_home_title'   => 'Lexus Thăng Long — Đại lý Lexus chính hãng tại Cầu Giấy, Hà Nội',
            'hotline'          => '0989345989',
            'address'          => 'Ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy, Hà Nội',
            'opening_hours'    => 'Thứ Hai – Chủ Nhật, 08:00 – 18:00',
            'company_name'     => 'Thu Hà · Lexus Thăng Long',
            'map_url'          => 'https://www.google.com/maps/search/?api=1&query=Lexus+Th%C4%83ng+Long+Ph%E1%BA%A1m+H%C3%B9ng+C%E1%BA%A7u+Gi%E1%BA%A5y+H%C3%A0+N%E1%BB%99i',

            'advisor_name'  => 'Thu Hà',
            'advisor_role'  => 'Chuyên viên tư vấn · Lexus Thăng Long',
            'advisor_phone' => '0989345989',
            'advisor_zalo'  => 'https://zalo.me/0989345989',

            'zalo' => 'https://zalo.me/0989345989',
        ];

        // Ảnh chia sẻ mặc định (Open Graph) khi gửi link trang không có ảnh
        // riêng qua Zalo/Facebook: mặt tiền đại lý.
        $values['social_image'] = $this->facility('mat-tien-toan-canh');

        foreach ($values as $key => $value) {
            Catalog::query('setting')->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /**
     * Hai form, đúng khoá mà view gọi:
     *   dang-ky-lai-thu  → trang /dang-ky-lai-thu (config catalog.frontend.booking.forms)
     *   nhan-bao-gia     → cuối trang chi tiết xe (config catalog.frontend.product_forms)
     *
     * Đúng phạm vi đã chốt: khách chỉ nhập tên, số điện thoại, chọn xe rồi
     * gửi. Không hỏi ngày/khung giờ — chuyên viên hẹn khi gọi lại.
     */
    private function forms(): void
    {
        $fields = [
            ['key' => 'name', 'label' => 'Họ và tên', 'type' => 'text', 'rules' => ['required', 'max:100'], 'sort' => 1, 'width' => 'half'],
            ['key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2, 'width' => 'half'],
            // Kiểu `product` của core: gửi id xe, validate `exists` trên xe đã
            // đăng, và StoreLead ghi thẳng vào cột leads.product_id — nhờ vậy
            // cột "Dòng xe" trong danh sách Lead ở admin có dữ liệu. Gửi tên
            // xe dạng chữ thì chỉ nằm trong `data`, cột đó trống trơn.
            ['key' => 'product_id', 'label' => 'Dòng xe quan tâm', 'type' => 'product', 'rules' => ['required'], 'sort' => 3],
            // Kiểu `checkbox` của core là checkbox NHIỀU lựa chọn: rule `array`
            // được thêm tự động và giá trị gửi lên phải là mảng. Ô đồng ý đơn
            // lẻ vẫn dùng kiểu này, chỉ khai đúng một option — xem
            // FormField::validationRules().
            ['key' => 'consent', 'label' => 'Đồng ý chính sách quyền riêng tư', 'type' => 'checkbox',
                'rules' => ['required'], 'sort' => 4,
                'options' => ['1' => 'Tôi đã đọc và đồng ý với [chính sách quyền riêng tư](/quyen-rieng-tu).']],
        ];

        $forms = [
            'dang-ky-lai-thu' => [
                'name'            => 'Đăng ký lái thử',
                'description'     => 'Để lại thông tin, chuyên viên tư vấn sẽ liên hệ xác nhận lịch hẹn.',
                'success_message' => 'Đã nhận đăng ký của bạn. Chuyên viên tư vấn sẽ liên hệ trong giờ làm việc để xác nhận lịch lái thử.',
            ],
            'nhan-bao-gia' => [
                'name'            => 'Nhận báo giá',
                'description'     => 'Để lại thông tin để nhận báo giá và phương án phù hợp.',
                'success_message' => 'Đã nhận yêu cầu của bạn. Chuyên viên tư vấn sẽ gửi báo giá trong thời gian sớm nhất.',
            ],
            // Cùng trường với "Nhận báo giá" nhưng tách khoá riêng để trong
            // danh sách Lead biết khách đến từ popup tự bật hay tự bấm nút —
            // đo được popup có đáng giữ hay không.
            'popup-bao-gia' => [
                'name'            => 'Popup trang chủ',
                'description'     => 'Form tự bật trên trang chủ.',
                'success_message' => 'Đã nhận yêu cầu của bạn. Chuyên viên tư vấn sẽ gửi báo giá trong thời gian sớm nhất.',
            ],
        ];

        // Email nhận thông báo khi có khách để lại số. Admin không còn màn hình
        // Form nên khai ở .env: LEAD_NOTIFY_EMAILS=a@x.vn,b@y.vn (xem DEPLOY.md).
        $notify = (array) config('catalog.leads.notify_emails', []);

        foreach ($forms as $key => $attributes) {
            if ($notify) {
                $attributes['notify_emails'] = $notify;
            }

            $form = Catalog::query('form')->updateOrCreate(['key' => $key], $attributes);
            $form->fields()->delete();
            $form->fields()->createMany($fields);
        }
    }

    /**
     * Các trang tĩnh mà header/footer/thẻ xe có link tới. Tạo ở trạng thái
     * `published` với nội dung rỗng — biên tập viên vào admin điền sau.
     * Thiếu trang nào thì link đó 404, nên tạo sẵn đủ bộ.
     */
    private function pages(): void
    {
        // /bao-gia đã có route + form riêng (QuoteController). Bản ghi trang
        // tĩnh cũ trùng slug thì dọn đi cho admin khỏi thấy một trang rỗng.
        Catalog::query('page')->where('slug', 'bao-gia')->forceDelete();

        $pages = [
            'bang-gia'       => 'Bảng giá xe Lexus',
            'uu-dai'         => 'Đặc quyền sở hữu',
            'tai-chinh'      => 'Giải pháp tài chính',
            'dich-vu'        => 'Dịch vụ & chăm sóc',
            'the-gioi-lexus' => 'Thế giới Lexus',
            'showroom'       => 'Showroom & liên hệ',
            'lien-he'        => 'Liên hệ tư vấn',
            'faq'            => 'Câu hỏi thường gặp',
            'quyen-rieng-tu' => 'Chính sách quyền riêng tư',
            'dieu-khoan'     => 'Điều khoản sử dụng',
        ];

        foreach ($pages as $slug => $title) {
            Catalog::query('page')->updateOrCreate(
                ['slug' => $slug],
                ['title' => $title, 'status' => 'published'],
            );
        }

        foreach ($this->contentPages() as $slug => $content) {
            Catalog::query('page')->where('slug', $slug)->first()?->update($content);
        }
    }

    /**
     * Ảnh thật của cơ sở Lexus Thăng Long, copy vào kho media để admin thay.
     *
     * Nguồn: database/seeders/media/lexus/co-so/ — ảnh mặt tiền, sảnh, phòng
     * chờ, khu bàn giao, xưởng sơn, xưởng dịch vụ (bộ ảnh chính thức của đại
     * lý + ảnh trong dự án Car-project). Ghi đè khi nội dung ảnh đổi.
     */
    private function facility(string $name): ?string
    {
        $source = database_path("seeders/media/lexus/co-so/{$name}.webp");

        if (! is_file($source)) {
            return null;
        }

        $path    = "catalog/co-so/{$name}.webp";
        $media   = app(\App\Media\MediaStore::class);
        $content = (string) file_get_contents($source);

        if (! $media->exists($path) || md5((string) $media->read($path)) !== md5($content)) {
            $media->write($path, $content);
        }

        return $path;
    }

    /**
     * Nội dung các trang tĩnh có ảnh thật của cơ sở.
     *
     * Viết theo lối "trả lời trước": câu đầu mỗi khối nói thẳng sự thật
     * (ở đâu, mở cửa khi nào, có gì) để khách đọc lướt và công cụ tìm kiếm /
     * AI trích dẫn được nguyên câu. Mỗi trang kết bằng khối hỏi đáp.
     *
     * @return array<string, array{sections: array, seo: array}>
     */
    private function contentPages(): array
    {
        $f     = fn (string $name) => $this->facility($name);
        $one   = fn (string $name, string $label) => [['image' => $f($name), 'label' => $label]];
        $hours = 'Thứ Hai – Chủ Nhật, 08:00 – 18:00';
        $where = 'ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy, Hà Nội';

        return [
            'the-gioi-lexus' => [
                'seo' => [
                    'title'       => 'Thế giới Lexus: Omotenashi, Takumi, Hybrid | Lexus Thăng Long',
                    'description' => 'Omotenashi, Takumi và 20 năm tiên phong hybrid hạng sang — những giá trị làm nên Lexus, '
                        .'và cách Lexus Thăng Long tại Cầu Giấy, Hà Nội mang chúng đến mỗi khách hàng.',
                    'eyebrow'     => 'THẾ GIỚI LEXUS',
                    'excerpt'     => 'Những giá trị làm nên Lexus — và cách chúng tôi mang chúng đến với bạn tại Hà Nội.',
                ],
                'sections' => [
                    ['type' => 'media', 'layout' => 'cols-1',
                        'items' => $one('showroom', 'Showroom Lexus Thăng Long tại '.$where)],
                    [
                        'type' => 'media', 'layout' => 'split', 'title' => 'Lexus Thăng Long',
                        'intro' => "Một địa chỉ.\nTrọn hành trình sở hữu.",
                        'body'  => 'Lexus Thăng Long là đại lý Lexus chính hãng tại '.$where.'. Showroom trưng bày đủ các '
                            .'dòng xe Lexus, có xe lái thử, khu bàn giao riêng và xưởng dịch vụ đạt chuẩn của hãng — '
                            .'đồng hành từ lần ghé thăm đầu tiên đến những lần bảo dưỡng về sau.',
                        'cta_label' => 'Đường đến showroom', 'cta_url' => '/showroom',
                        'items' => $one('mat-tien-toan-canh', 'Tòa nhà Lexus Thăng Long, Cầu Giấy, Hà Nội'),
                    ],
                    [
                        'type' => 'media', 'layout' => 'split-alt', 'title' => 'Omotenashi',
                        'intro' => "Đón khách như đón\nmột người thân.",
                        'body'  => 'Omotenashi là tinh thần hiếu khách của Nhật Bản: đoán trước điều khách cần và chuẩn bị '
                            .'sẵn trước khi khách phải lên tiếng. Ở showroom, đó là phòng chờ yên tĩnh, ly trà nóng, và '
                            .'một chuyên viên theo sát bạn từ lúc chọn xe đến ngày nhận xe.',
                        'cta_label' => 'Kết nối chuyên viên', 'cta_url' => '/lien-he',
                        'items' => $one('phong-cho', 'Phòng chờ và tư vấn tại Lexus Thăng Long'),
                    ],
                    [
                        'type' => 'media', 'layout' => 'split', 'title' => 'Takumi',
                        'intro' => "Bàn tay của\nnhững nghệ nhân.",
                        'body'  => 'Takumi là danh xưng dành cho những nghệ nhân bậc thầy của Lexus — người kiểm tra từng '
                            .'đường chỉ khâu, từng lớp sơn bằng tay và mắt. Tinh thần đó theo xe về tới xưởng dịch vụ: '
                            .'kỹ thuật viên được hãng đào tạo, phụ tùng chính hãng, buồng sơn sấy tiêu chuẩn.',
                        'cta_label' => 'Dịch vụ & chăm sóc', 'cta_url' => '/dich-vu',
                        'items' => $one('xuong-son', 'Buồng sơn sấy tại xưởng Lexus Thăng Long'),
                    ],
                    [
                        'type' => 'media', 'layout' => 'split-alt', 'title' => 'Lexus Hybrid',
                        'intro' => "Tiên phong hybrid\nhạng sang từ 2005.",
                        'body'  => 'Năm 2005, Lexus RX 400h trở thành chiếc SUV hạng sang chạy hybrid đầu tiên trên thế '
                            .'giới. Nay RX, NX, ES, LM và LS đều có bản hybrid, cùng ES 500e thuần điện. Hybrid của '
                            .'Lexus không cần cắm sạc: xe tự nạp điện khi phanh và chạy điện ở tốc độ thấp trong phố.',
                        'cta_label' => 'Xem các dòng xe', 'cta_url' => '/san-pham',
                        'items' => $one('khu-trung-bay', 'Khu trưng bày xe Lexus hybrid ngoài trời'),
                    ],
                    [
                        'type' => 'media', 'layout' => 'cols-2', 'title' => 'Không gian trải nghiệm',
                        'intro' => "Mỗi góc showroom\nđều có câu chuyện.",
                        'cta_label' => 'Đặt lịch ghé thăm', 'cta_url' => '/dang-ky-lai-thu',
                        'items' => [
                            ['image' => $f('sanh-trung-bay'), 'eyebrow' => 'Sảnh trưng bày', 'label' => 'Xem tận nơi, ngồi thử, so sánh.',
                                'desc' => 'Các dòng xe Lexus trưng bày cạnh nhau để bạn cảm nhận khác biệt về kích thước và nội thất.'],
                            ['image' => $f('khu-ban-giao'), 'eyebrow' => 'Khu bàn giao', 'label' => 'Ngày nhận xe là một dịp đáng nhớ.',
                                'desc' => 'Không gian riêng để bàn giao, hướng dẫn sử dụng và lưu lại khoảnh khắc cùng gia đình.'],
                            ['image' => $f('goc-cafe'), 'eyebrow' => 'Phòng chờ', 'label' => 'Làm việc trong lúc chờ xe.',
                                'desc' => 'Wi-Fi, cà phê và không gian yên tĩnh trong thời gian xe được bảo dưỡng.'],
                            ['image' => $f('su-kien'), 'eyebrow' => 'Sự kiện', 'label' => 'Những buổi gặp gỡ riêng.',
                                'desc' => 'Âm nhạc, trải nghiệm lái và các chương trình dành riêng cho khách hàng Lexus.'],
                        ],
                    ],
                    [
                        'type' => 'faq', 'title' => 'Hỏi đáp', 'intro' => 'Câu hỏi thường gặp về Lexus Thăng Long',
                        'rows' => [
                            ['label' => 'Lexus Thăng Long ở đâu?',
                                'value' => 'Lexus Thăng Long nằm tại '.$where.' — thuận tiện từ Mỹ Đình, Cầu Giấy, Nam Từ Liêm và Thanh Xuân.'],
                            ['label' => 'Showroom mở cửa lúc nào?',
                                'value' => 'Showroom mở cửa '.$hours.'. Nên đặt lịch trước để chuyên viên chuẩn bị sẵn xe lái thử bạn quan tâm.'],
                            ['label' => 'Lexus Thăng Long bán những dòng xe nào?',
                                'value' => 'Đủ 7 dòng xe Lexus đang phân phối chính hãng: ES, NX, RX (sedan và SUV hạng sang), GX, LX (SUV khung gầm rời), '
                                    .'LM (MPV) và LS (sedan đầu bảng). Giá từng phiên bản xem tại trang Bảng giá.'],
                            ['label' => 'Đăng ký lái thử Lexus cần những gì?',
                                'value' => 'Chỉ cần để lại họ tên, số điện thoại và dòng xe muốn lái thử. Khi đến, mang theo giấy phép lái xe hạng B '
                                    .'còn hiệu lực; chuyên viên sẽ xác nhận lịch và cung đường lái thử.'],
                            ['label' => 'Lexus Thăng Long có xưởng dịch vụ không?',
                                'value' => 'Có. Xưởng dịch vụ đặt ngay tại đại lý, gồm khu sửa chữa chung, buồng sơn sấy và cố vấn dịch vụ tiếp nhận xe.'],
                        ],
                    ],
                ],
            ],

            'bang-gia' => [
                'seo' => [
                    'title'       => 'Bảng giá xe Lexus 2026 mới nhất tại Hà Nội | Lexus Thăng Long',
                    'description' => 'Bảng giá xe Lexus 2026 đủ 7 dòng xe: NX từ 3,13 tỷ, RX từ 3,35 tỷ, GX từ 6,4 tỷ, LM từ 7,21 tỷ, '
                        .'LS 8,03 tỷ, LX từ 8,59 tỷ; ES thế hệ mới đang cập nhật giá. Nhận báo giá lăn bánh Hà Nội tại Lexus Thăng Long.',
                    'eyebrow'     => 'BẢNG GIÁ',
                    'excerpt'     => 'Giá niêm yết từng phiên bản, cập nhật theo bảng giá của đại lý. Bấm "Nhận báo giá" để có giá lăn bánh chi tiết.',
                ],
                'sections' => [
                    [
                        'type' => 'faq', 'title' => 'Hỏi đáp', 'intro' => 'Câu hỏi thường gặp khi mua xe Lexus',
                        'rows' => [
                            ['label' => 'Giá niêm yết đã gồm thuế VAT chưa?',
                                'value' => 'Đã gồm. Giá niêm yết là giá bán xe đã có thuế giá trị gia tăng, chưa gồm lệ phí trước bạ, phí biển số và các khoản đăng ký.'],
                            ['label' => 'Giá lăn bánh Lexus tại Hà Nội tính thế nào?',
                                'value' => 'Giá lăn bánh = giá niêm yết + lệ phí trước bạ (12% với ô tô con chạy xăng/hybrid tại Hà Nội; '
                                    .'ô tô điện chạy pin như ES 500e: 0% đến hết năm 2030) + phí cấp biển số (14 triệu đồng tại Hà Nội từ 1/1/2026) '
                                    .'+ phí đăng kiểm, phí bảo trì đường bộ và bảo hiểm trách nhiệm dân sự bắt buộc.'],
                            ['label' => 'Mua Lexus trả góp được không?',
                                'value' => 'Được. Lexus Thăng Long làm việc với nhiều ngân hàng; khoản vay và thời hạn phụ thuộc hồ sơ của bạn. '
                                    .'Chuyên viên sẽ gửi phương án minh họa kèm báo giá.'],
                            ['label' => 'Giá xe có thay đổi không?',
                                'value' => 'Giá do hãng công bố và có thể điều chỉnh theo thời điểm, cùng các chương trình ưu đãi riêng. '
                                    .'Bảng giá trên trang này được cập nhật theo đại lý; giá chốt là giá trên hợp đồng.'],
                        ],
                    ],
                ],
            ],

            'showroom' => [
                'seo' => [
                    'title'       => 'Showroom Lexus Thăng Long — Phạm Hùng, Cầu Giấy, Hà Nội',
                    'description' => 'Showroom Lexus Thăng Long tại ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy, Hà Nội. '
                        .'Mở cửa '.$hours.'. Xem xe, lái thử và nhận tư vấn trực tiếp.',
                    'eyebrow'     => 'LEXUS THĂNG LONG',
                    'excerpt'     => 'Ghé thăm, cảm nhận và lựa chọn chiếc Lexus dành cho bạn.',
                ],
                'sections' => [
                    [
                        'type' => 'media', 'layout' => 'split', 'title' => 'Lexus Thăng Long',
                        'intro' => 'Điểm hẹn của sự tận tâm.',
                        'body'  => 'Showroom nằm tại '.$where.', mở cửa '.$hours.'. Bạn có thể xem xe trực tiếp, lái thử '
                            .'và trao đổi cùng chuyên viên tư vấn.',
                        'cta_label' => 'Đặt lịch ghé thăm', 'cta_url' => '/dang-ky-lai-thu',
                        'items' => $one('mat-tien', 'Mặt tiền showroom Lexus Thăng Long tại Hà Nội'),
                    ],
                    [
                        'type' => 'media', 'layout' => 'cols-2', 'title' => 'Không gian trải nghiệm',
                        'intro' => 'Đón tiếp bằng sự chu đáo.',
                        'items' => [
                            ['image' => $f('sanh-trung-bay'), 'label' => 'Khu vực trưng bày',
                                'desc' => 'Khám phá thiết kế và cảm nhận trực tiếp các dòng xe Lexus.'],
                            ['image' => $f('khu-ban-giao'), 'label' => 'Không gian bàn giao',
                                'desc' => 'Một khởi đầu đáng nhớ cho hành trình cùng chiếc xe mới.'],
                        ],
                    ],
                    [
                        'type' => 'media', 'layout' => 'split-alt', 'title' => 'Omotenashi',
                        'intro' => 'Tận tâm trong từng cuộc gặp.',
                        'body'  => 'Từ tìm hiểu xe đến lựa chọn phiên bản, mỗi cuộc trao đổi bắt đầu bằng nhu cầu và mong muốn của bạn.',
                        'cta_label' => 'Kết nối chuyên viên', 'cta_url' => '/lien-he',
                        'items' => $one('phong-cho', 'Không gian tư vấn tại Lexus Thăng Long'),
                    ],
                ],
            ],

            'dich-vu' => [
                'seo' => [
                    'title'       => 'Dịch vụ & bảo dưỡng xe Lexus tại Hà Nội | Lexus Thăng Long',
                    'description' => 'Xưởng dịch vụ Lexus chính hãng tại Cầu Giấy, Hà Nội: bảo dưỡng định kỳ, sửa chữa chung, '
                        .'đồng sơn với buồng sơn sấy tiêu chuẩn. Đặt lịch qua chuyên viên Lexus Thăng Long.',
                    'eyebrow'     => 'DỊCH VỤ',
                    'excerpt'     => 'Kết nối dịch vụ và tìm hiểu quy trình chăm sóc chiếc Lexus của bạn.',
                ],
                'sections' => [
                    [
                        'type' => 'media', 'layout' => 'split', 'title' => 'Dịch vụ Lexus',
                        'intro' => 'Chăm sóc để an tâm đồng hành.',
                        'body'  => 'Xưởng dịch vụ đặt ngay tại Lexus Thăng Long: bảo dưỡng định kỳ, sửa chữa chung và đồng sơn. '
                            .'Chuyên viên sẽ kết nối bộ phận dịch vụ và hẹn lịch theo nhu cầu của bạn.',
                        'cta_label' => 'Liên hệ tư vấn', 'cta_url' => '/lien-he',
                        'items' => $one('xuong-dich-vu', 'Kỹ thuật viên kiểm tra gầm xe tại xưởng dịch vụ Lexus Thăng Long'),
                    ],
                    [
                        'type' => 'media', 'layout' => 'cols-2', 'title' => 'Không gian dịch vụ',
                        'intro' => 'Chỉn chu trong từng công đoạn.',
                        'items' => [
                            ['image' => $f('xuong-son'), 'label' => 'Khu vực sơn và sửa chữa',
                                'desc' => 'Hệ thống sơn sấy tại xưởng Lexus Thăng Long.'],
                            ['image' => $f('co-van-dich-vu'), 'label' => 'Tư vấn trước khi thực hiện',
                                'desc' => 'Trao đổi tình trạng xe và nhu cầu chăm sóc cùng cố vấn dịch vụ.'],
                            ['image' => $f('xuong-dich-vu-es'), 'label' => 'Bảo vệ xe trong quá trình sửa chữa',
                                'desc' => 'Xe được che phủ, cố định cẩn thận trước mỗi công đoạn.'],
                            ['image' => $f('xuong-dich-vu-rx'), 'label' => 'Buồng sơn tiêu chuẩn',
                                'desc' => 'Kỹ thuật viên được đào tạo theo quy trình của Lexus.'],
                        ],
                    ],
                ],
            ],

            'lien-he' => [
                'seo' => [
                    'title'       => 'Liên hệ tư vấn Lexus — Thu Hà, Lexus Thăng Long',
                    'description' => 'Liên hệ chuyên viên Thu Hà, Lexus Thăng Long: hotline 0989 345 989. Showroom tại '
                        .$where.', mở cửa '.$hours.'.',
                    'eyebrow'     => 'LIÊN HỆ',
                    'excerpt'     => 'Một đầu mối đồng hành từ lựa chọn xe đến trải nghiệm thực tế.',
                ],
                'sections' => [
                    [
                        'type' => 'media', 'layout' => 'split', 'title' => 'Hẹn gặp tại Lexus Thăng Long',
                        'intro' => 'Bắt đầu bằng một cuộc trò chuyện.',
                        'body'  => 'Trao đổi về dòng xe, lựa chọn phiên bản hoặc đặt lịch trải nghiệm cùng chuyên viên tư vấn.',
                        'cta_label' => 'Đăng ký lái thử', 'cta_url' => '/dang-ky-lai-thu',
                        'items' => $one('don-tiep', 'Chuyên viên đón tiếp khách tại sảnh Lexus Thăng Long'),
                    ],
                ],
            ],
            // ── Đặc quyền sở hữu ──────────────────────────────────────────
            // Bảo hành đối chiếu thông báo của Lexus Việt Nam: 10 năm không
            // giới hạn km cho xe hybrid bán mới từ 8/5/2026 (điều kiện bảo
            // dưỡng định kỳ đủ tại đại lý ủy quyền); 5 năm không giới hạn km
            // là chính sách chung từ 10/2023.
            'uu-dai' => [
                'seo' => [
                    'title'       => 'Đặc quyền sở hữu Lexus: bảo hành 10 năm xe hybrid | Lexus Thăng Long',
                    'description' => 'Xe Lexus hybrid bán mới từ 8/5/2026 được bảo hành 10 năm, không giới hạn km cho cả xe và pin hybrid. '
                        .'Chính sách bảo hành, bảo dưỡng và đồng hành sau bán hàng tại Lexus Thăng Long, Hà Nội.',
                    'eyebrow'     => 'ĐẶC QUYỀN SỞ HỮU',
                    'excerpt'     => 'An tâm từ ngày nhận xe đến nhiều năm về sau: bảo hành dài hạn, bảo dưỡng chính hãng và một chuyên viên luôn bên bạn.',
                ],
                'sections' => [
                    [
                        'type' => 'media', 'layout' => 'split', 'title' => 'Bảo hành',
                        'intro' => "10 năm, không giới hạn km\ncho xe hybrid.",
                        'body'  => 'Xe Lexus hybrid bán mới từ ngày 8/5/2026 được Lexus Việt Nam bảo hành 10 năm, không giới hạn số km, '
                            .'cho cả xe và pin hybrid — với điều kiện xe được bảo dưỡng định kỳ đầy đủ tại đại lý ủy quyền. '
                            .'Áp dụng cho RX, NX, ES 350h, LM và LS.',
                        'cta_label' => 'Xem các dòng xe hybrid', 'cta_url' => '/san-pham',
                        'items' => $one('co-van-dich-vu', 'Cố vấn dịch vụ tiếp nhận xe tại Lexus Thăng Long'),
                    ],
                    [
                        'type' => 'table', 'title' => 'Chính sách bảo hành', 'intro' => 'Thời hạn bảo hành theo loại xe',
                        'rows' => [
                            ['label' => 'Xe hybrid bán mới từ 8/5/2026 (RX, NX, ES 350h, LM, LS)',
                                'value' => '10 năm, không giới hạn km — cho xe và pin hybrid, khi bảo dưỡng định kỳ đầy đủ tại đại lý ủy quyền'],
                            ['label' => 'Xe động cơ xăng (LX 600, GX 550)', 'value' => '5 năm, không giới hạn km'],
                            ['label' => 'Xe điện ES 500e', 'value' => 'Theo chính sách riêng cho xe điện của Lexus Việt Nam — chuyên viên xác nhận khi báo giá'],
                            ['label' => 'Chu kỳ bảo dưỡng khuyến nghị', 'value' => 'Mỗi 6 tháng hoặc 10.000 km, tùy điều kiện nào đến trước'],
                        ],
                    ],
                    [
                        'type' => 'media', 'layout' => 'cols-2', 'title' => 'Đồng hành sau bán hàng',
                        'intro' => "Mua xe là bắt đầu,\nkhông phải kết thúc.",
                        'items' => [
                            ['image' => $f('khu-ban-giao'), 'eyebrow' => 'Ngày nhận xe', 'label' => 'Bàn giao chu đáo.',
                                'desc' => 'Hướng dẫn từng tính năng, cài đặt kết nối điện thoại và lưu lại khoảnh khắc cùng gia đình.'],
                            ['image' => $f('don-tiep'), 'eyebrow' => 'Một đầu mối', 'label' => 'Một người nhớ xe của bạn.',
                                'desc' => 'Chuyên viên theo sát hồ sơ xe, giấy tờ và mọi câu hỏi — bạn không phải kể lại từ đầu.'],
                            ['image' => $f('xuong-dich-vu'), 'eyebrow' => 'Bảo dưỡng', 'label' => 'Nhắc lịch, giữ bảo hành.',
                                'desc' => 'Nhắc lịch bảo dưỡng định kỳ để xe luôn đủ điều kiện hưởng bảo hành dài hạn.'],
                            ['image' => $f('goc-cafe'), 'eyebrow' => 'Phòng chờ', 'label' => 'Thời gian chờ thoải mái.',
                                'desc' => 'Wi-Fi, cà phê và góc làm việc yên tĩnh trong lúc xe được chăm sóc.'],
                        ],
                    ],
                    [
                        'type' => 'faq', 'title' => 'Hỏi đáp', 'intro' => 'Câu hỏi thường gặp về bảo hành Lexus',
                        'rows' => [
                            ['label' => 'Bảo hành 10 năm của Lexus áp dụng cho xe nào?',
                                'value' => 'Cho xe Lexus hybrid bán mới từ ngày 8/5/2026, bao gồm cả xe và pin hybrid, không giới hạn số km.'],
                            ['label' => 'Cần làm gì để giữ bảo hành 10 năm?',
                                'value' => 'Bảo dưỡng định kỳ đầy đủ theo khuyến nghị của Lexus (mỗi 6 tháng hoặc 10.000 km) tại đại lý Lexus ủy quyền.'],
                            ['label' => 'Xe mua trước ngày 8/5/2026 được bảo hành bao lâu?',
                                'value' => 'Theo chính sách ghi trong sổ bảo hành của xe tại thời điểm mua; từ 10/2023 Lexus Việt Nam áp dụng 5 năm không giới hạn km cho xe và 7 năm cho pin hybrid.'],
                            ['label' => 'Bảo dưỡng Lexus ở đâu tại Hà Nội?',
                                'value' => 'Tại xưởng dịch vụ của Lexus Thăng Long, ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy. Đặt lịch qua chuyên viên hoặc hotline 0989 345 989.'],
                        ],
                    ],
                ],
            ],

            // ── Giải pháp tài chính ───────────────────────────────────────
            // Ví dụ trả góp tính theo dư nợ giảm dần (cách phần lớn ngân hàng
            // Việt Nam áp dụng) với lãi suất GIẢ ĐỊNH 8%/năm — ghi rõ là minh họa.
            'tai-chinh' => [
                'seo' => [
                    'title'       => 'Mua Lexus trả góp tại Hà Nội: hồ sơ, ví dụ vay | Lexus Thăng Long',
                    'description' => 'Mua Lexus trả góp: vay thường 70–80% giá xe, thời hạn tới 7–8 năm. Ví dụ vay 70% Lexus RX 350h Premium '
                        .'trong 7 năm, hồ sơ cần chuẩn bị và câu hỏi thường gặp — Lexus Thăng Long, Hà Nội.',
                    'eyebrow'     => 'GIẢI PHÁP TÀI CHÍNH',
                    'excerpt'     => 'Sở hữu chiếc Lexus bạn chọn với kế hoạch tài chính rõ ràng từ đầu.',
                ],
                'sections' => [
                    [
                        'type' => 'text', 'title' => 'Trả lời nhanh', 'intro' => 'Mua Lexus trả góp như thế nào?',
                        'body' => '<p><strong>Các ngân hàng thường cho vay 70–80% giá trị xe Lexus, thời hạn tới 7–8 năm, lấy chính chiếc xe làm tài sản bảo đảm.</strong> '
                            .'Bạn trả trước phần còn lại cùng các khoản lăn bánh (lệ phí trước bạ, biển số…), sau đó trả góp hằng tháng.</p>'
                            .'<p>Tỷ lệ vay, lãi suất và thời gian ưu đãi khác nhau giữa các ngân hàng và phụ thuộc hồ sơ của bạn. '
                            .'Chuyên viên sẽ so sánh và gửi phương án cụ thể kèm báo giá.</p>',
                    ],
                    [
                        'type' => 'table', 'title' => 'Ví dụ minh họa', 'intro' => 'Vay mua Lexus RX 350h Premium',
                        'body' => 'Ví dụ khoản vay mua Lexus RX 350h Premium',
                        'rows' => [
                            ['label' => 'Giá niêm yết', 'value' => '3.350.000.000 đ'],
                            ['label' => 'Khoản vay (70% giá xe)', 'value' => '2.345.000.000 đ'],
                            ['label' => 'Trả trước (30% giá xe + phí lăn bánh tạm tính)', 'value' => 'khoảng 1.423.000.000 đ'],
                            ['label' => 'Thời hạn vay', 'value' => '7 năm (84 tháng)'],
                            ['label' => 'Lãi suất giả định', 'value' => '8%/năm, tính trên dư nợ giảm dần'],
                            ['label' => 'Tháng đầu tiên', 'value' => 'khoảng 43,5 triệu đồng (gốc 27,9 triệu + lãi 15,6 triệu)'],
                            ['label' => 'Tháng cuối cùng', 'value' => 'khoảng 28,1 triệu đồng'],
                            ['label' => 'Tổng tiền lãi 7 năm', 'value' => 'khoảng 664 triệu đồng'],
                        ],
                    ],
                    [
                        'type' => 'text', 'title' => 'Lưu ý', 'intro' => 'Đọc kỹ trước khi ký hợp đồng vay',
                        'body' => '<ul><li>Lãi suất 8%/năm trong ví dụ chỉ để minh họa. Lãi suất thực tế thường cố định trong 6–24 tháng đầu, sau đó thả nổi theo lãi suất tham chiếu của ngân hàng.</li>'
                            .'<li>Hỏi rõ phí trả nợ trước hạn — nhiều ngân hàng thu phí nếu tất toán trong vài năm đầu.</li>'
                            .'<li>Trong thời gian vay, ngân hàng giữ bản gốc giấy đăng ký xe; bạn lưu hành xe bằng bản sao có xác nhận của ngân hàng.</li>'
                            .'<li>Bảo hiểm vật chất xe thường là điều kiện bắt buộc của khoản vay.</li></ul>',
                    ],
                    [
                        'type' => 'text', 'title' => 'Hồ sơ', 'intro' => 'Cần chuẩn bị hồ sơ gì?',
                        'body' => '<h3>Khách hàng cá nhân</h3><ul><li>Căn cước công dân của người vay (và vợ/chồng nếu đã kết hôn).</li>'
                            .'<li>Giấy xác nhận tình trạng hôn nhân hoặc đăng ký kết hôn.</li>'
                            .'<li>Chứng minh thu nhập: hợp đồng lao động và sao kê lương, hoặc giấy tờ hộ kinh doanh, hợp đồng cho thuê tài sản…</li>'
                            .'<li>Hợp đồng mua bán xe (chuyên viên chuẩn bị).</li></ul>'
                            .'<h3>Khách hàng doanh nghiệp</h3><ul><li>Giấy chứng nhận đăng ký doanh nghiệp, điều lệ, giấy tờ người đại diện.</li>'
                            .'<li>Báo cáo tài chính và tờ khai thuế gần nhất.</li></ul>',
                    ],
                    [
                        'type' => 'media', 'layout' => 'split', 'title' => 'Tư vấn riêng',
                        'intro' => "Một kế hoạch\ndành riêng cho bạn.",
                        'body'  => 'Cho chuyên viên biết dòng xe, số tiền bạn muốn trả trước và khoản trả hằng tháng thoải mái. '
                            .'Bạn sẽ nhận bảng so sánh phương án của vài ngân hàng kèm báo giá lăn bánh.',
                        'cta_label' => 'Nhận phương án tài chính', 'cta_url' => '/bao-gia',
                        'items' => $one('phong-cho', 'Trao đổi phương án tài chính tại Lexus Thăng Long'),
                    ],
                    [
                        'type' => 'faq', 'title' => 'Hỏi đáp', 'intro' => 'Câu hỏi thường gặp khi mua Lexus trả góp',
                        'rows' => [
                            ['label' => 'Mua Lexus trả góp cần trả trước bao nhiêu?',
                                'value' => 'Thường 20–30% giá xe cộng các khoản lăn bánh. Ví dụ với RX 350h Premium, vay 70% thì cần khoảng 1,42 tỷ đồng ban đầu.'],
                            ['label' => 'Vay mua xe Lexus tối đa bao lâu?',
                                'value' => 'Phần lớn ngân hàng cho vay mua ô tô tới 7–8 năm, tùy ngân hàng và hồ sơ.'],
                            ['label' => 'Có trả nợ trước hạn được không?',
                                'value' => 'Được, nhưng nhiều ngân hàng thu phí tất toán trước hạn trong những năm đầu. Hỏi rõ mức phí trước khi ký.'],
                            ['label' => 'Doanh nghiệp có vay mua Lexus được không?',
                                'value' => 'Được. Hồ sơ gồm giấy tờ pháp lý doanh nghiệp và báo cáo tài chính; xe đứng tên doanh nghiệp.'],
                        ],
                    ],
                ],
            ],

            // ── Câu hỏi thường gặp (tổng hợp) ─────────────────────────────
            'faq' => [
                'seo' => [
                    'title'       => 'Câu hỏi thường gặp khi mua xe Lexus tại Hà Nội | Lexus Thăng Long',
                    'description' => 'Giải đáp nhanh: giá xe Lexus 2026, giá lăn bánh Hà Nội, lái thử, trả góp, bảo hành 10 năm xe hybrid, '
                        .'thời gian giao xe và địa chỉ showroom Lexus Thăng Long.',
                    'eyebrow'     => 'HỎI ĐÁP',
                    'excerpt'     => 'Những câu khách hỏi nhiều nhất — trả lời ngắn, kèm link đến trang chi tiết.',
                ],
                'sections' => [
                    [
                        'type' => 'faq', 'title' => 'Mua xe', 'intro' => 'Giá và mua xe',
                        'rows' => [
                            ['label' => 'Xe Lexus rẻ nhất và đắt nhất hiện nay giá bao nhiêu?',
                                'value' => 'Trong các phiên bản đã có giá, rẻ nhất là NX 350 F SPORT 3,13 tỷ đồng; đắt nhất là LX 600 VIP 4 chỗ 9,7 tỷ đồng. ES thế hệ mới đang cập nhật giá. Xem đủ 16 phiên bản tại trang Bảng giá.'],
                            ['label' => 'Giá niêm yết đã gồm những gì?',
                                'value' => 'Đã gồm thuế VAT; chưa gồm lệ phí trước bạ, phí biển số, phí bảo trì đường bộ, đăng kiểm và bảo hiểm.'],
                            ['label' => 'Giá lăn bánh Lexus tại Hà Nội tính thế nào?',
                                'value' => 'Giá niêm yết + lệ phí trước bạ 12% (xe xăng/hybrid; xe điện chạy pin 0% đến hết 2030) + phí biển số 14 triệu đồng + phí bảo trì đường bộ, đăng kiểm và bảo hiểm TNDS. '
                                    .'Ví dụ RX 350h Premium lăn bánh khoảng 3,77 tỷ đồng.'],
                            ['label' => 'Có mua Lexus trả góp được không?',
                                'value' => 'Được. Ngân hàng thường cho vay 70–80% giá xe, thời hạn tới 7–8 năm. Xem ví dụ khoản vay tại trang Giải pháp tài chính.'],
                            ['label' => 'Đặt xe xong bao lâu thì nhận xe?',
                                'value' => 'Tùy dòng xe, phiên bản và màu: xe có sẵn có thể bàn giao sau khi hoàn tất thủ tục đăng ký; xe đặt hàng theo lịch về xe của Lexus Việt Nam. Chuyên viên báo thời gian cụ thể khi bạn chọn xe.'],
                        ],
                    ],
                    [
                        'type' => 'faq', 'title' => 'Lái thử & showroom', 'intro' => 'Lái thử và ghé showroom',
                        'rows' => [
                            ['label' => 'Showroom Lexus Thăng Long ở đâu, mở cửa khi nào?',
                                'value' => 'Ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy, Hà Nội; mở cửa Thứ Hai – Chủ Nhật, 08:00 – 18:00.'],
                            ['label' => 'Lái thử Lexus có mất phí không, cần mang gì?',
                                'value' => 'Miễn phí. Đặt lịch trước trên website hoặc qua hotline 0989 345 989, mang theo giấy phép lái xe hạng B còn hiệu lực.'],
                            ['label' => 'Có thể lái thử nhiều xe trong một buổi không?',
                                'value' => 'Có, nếu báo trước để chuyên viên chuẩn bị. Cách tốt nhất để chọn giữa RX 350h và RX 500h, hay ES và LS, là lái liền nhau.'],
                        ],
                    ],
                    [
                        'type' => 'faq', 'title' => 'Sau khi mua', 'intro' => 'Bảo hành và dịch vụ',
                        'rows' => [
                            ['label' => 'Xe Lexus được bảo hành bao lâu?',
                                'value' => 'Xe hybrid bán mới từ 8/5/2026: 10 năm, không giới hạn km cho xe và pin hybrid, với điều kiện bảo dưỡng định kỳ tại đại lý ủy quyền. Xe động cơ xăng: 5 năm, không giới hạn km.'],
                            ['label' => 'Bao lâu phải bảo dưỡng một lần?',
                                'value' => 'Lexus khuyến nghị mỗi 6 tháng hoặc 10.000 km, tùy điều kiện nào đến trước.'],
                            ['label' => 'Xe hybrid Lexus có phải cắm sạc không?',
                                'value' => 'Không. Xe hybrid tự nạp pin khi phanh và khi động cơ xăng hoạt động. Chỉ ES 500e (thuần điện) cần sạc.'],
                        ],
                    ],
                ],
            ],

            // ── Chính sách quyền riêng tư ─────────────────────────────────
            // Mô tả ĐÚNG những gì hệ thống đang lưu (xem StoreLead: tên, số
            // điện thoại, dòng xe, đồng ý, trang gửi, UTM, IP). Đổi form hay
            // thêm mã đo lường thì sửa trang này theo.
            'quyen-rieng-tu' => [
                'seo' => [
                    'title'       => 'Chính sách quyền riêng tư | Lexus Thăng Long',
                    'description' => 'Website thu thập những thông tin nào, dùng để làm gì, lưu bao lâu và quyền của bạn với dữ liệu cá nhân — '
                        .'theo Luật Bảo vệ dữ liệu cá nhân 2025.',
                    'eyebrow'     => 'QUYỀN RIÊNG TƯ',
                    'excerpt'     => 'Chúng tôi chỉ hỏi những gì cần để tư vấn cho bạn — và bạn luôn có quyền yêu cầu xoá.',
                ],
                'sections' => [
                    ['type' => 'text', 'title' => 'Tóm tắt', 'intro' => 'Chính sách trong ba câu',
                        'body' => '<p>Khi bạn gửi form đăng ký lái thử hoặc nhận báo giá, chúng tôi lưu <strong>họ tên, số điện thoại và dòng xe bạn quan tâm</strong> '
                            .'để liên hệ tư vấn. Thông tin <strong>không được bán</strong> và chỉ chia sẻ với đại lý Lexus Thăng Long để phục vụ yêu cầu của bạn. '
                            .'Bạn có thể yêu cầu xem, sửa hoặc xoá dữ liệu bất cứ lúc nào qua hotline 0989 345 989.</p>'
                            .'<p>Cập nhật lần cuối: 24/09/2026. Chính sách áp dụng theo Luật Bảo vệ dữ liệu cá nhân số 91/2025/QH15 và Nghị định 356/2025/NĐ-CP.</p>'],
                    ['type' => 'text', 'title' => '1. Bên xử lý dữ liệu', 'intro' => 'Ai chịu trách nhiệm với dữ liệu của bạn',
                        'body' => '<p>Website này là trang tư vấn của chuyên viên bán hàng Thu Hà tại đại lý Lexus Thăng Long (ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy, Hà Nội), '
                            .'không phải website chính thức của Lexus Việt Nam. Mọi yêu cầu liên quan đến dữ liệu cá nhân, vui lòng liên hệ hotline 0989 345 989.</p>'],
                    ['type' => 'text', 'title' => '2. Dữ liệu thu thập', 'intro' => 'Chúng tôi thu thập những gì',
                        'body' => '<h3>Do bạn cung cấp qua form</h3><ul><li>Họ và tên.</li><li>Số điện thoại.</li><li>Dòng xe bạn quan tâm.</li>'
                            .'<li>Xác nhận đồng ý với chính sách này.</li></ul>'
                            .'<h3>Ghi nhận tự động khi gửi form</h3><ul><li>Trang bạn đang xem lúc gửi và nguồn chiến dịch quảng cáo (tham số UTM), nếu có.</li>'
                            .'<li>Địa chỉ IP — dùng để chống gửi tự động (spam).</li></ul>'
                            .'<h3>Lưu trên trình duyệt của bạn</h3><ul><li>Cookie phiên làm việc, cần để form gửi được an toàn.</li>'
                            .'<li>Ghi nhớ đã xem hộp báo giá tự bật, để không hiện lại liên tục. Dữ liệu này nằm trên máy bạn, không gửi về máy chủ.</li>'
                            .'<li>Nếu website bật công cụ đo lường (Google Tag Manager, Facebook Pixel), các công cụ này có thể đặt cookie riêng theo chính sách của Google và Meta.</li></ul>'],
                    ['type' => 'text', 'title' => '3. Mục đích', 'intro' => 'Dữ liệu được dùng để làm gì',
                        'body' => '<ul><li>Gọi lại để xác nhận lịch lái thử, gửi báo giá và phương án mua xe theo yêu cầu của bạn.</li>'
                            .'<li>Chăm sóc sau bán hàng khi bạn đã mua xe: nhắc lịch bảo dưỡng, thông báo chương trình liên quan đến xe của bạn.</li>'
                            .'<li>Thống kê nguồn khách (khách đến từ trang nào, chiến dịch nào) để cải thiện website.</li></ul>'
                            .'<p>Chúng tôi không dùng dữ liệu của bạn cho mục đích khác khi chưa hỏi ý kiến bạn.</p>'],
                    ['type' => 'text', 'title' => '4. Chia sẻ', 'intro' => 'Dữ liệu được chia sẻ với ai',
                        'body' => '<ul><li>Đại lý Lexus Thăng Long — để lập báo giá, hợp đồng, đăng ký lái thử và giao xe.</li>'
                            .'<li>Ngân hàng, công ty bảo hiểm — chỉ khi bạn yêu cầu tư vấn vay mua xe hoặc bảo hiểm, và chỉ những thông tin cần cho hồ sơ đó.</li>'
                            .'<li>Cơ quan nhà nước có thẩm quyền — khi pháp luật yêu cầu.</li></ul>'
                            .'<p>Chúng tôi <strong>không bán, không cho thuê</strong> dữ liệu cá nhân của bạn.</p>'],
                    ['type' => 'text', 'title' => '5. Lưu trữ', 'intro' => 'Dữ liệu được lưu bao lâu, ở đâu',
                        'body' => '<p>Dữ liệu được lưu trên máy chủ của website, chỉ người được phân quyền quản trị mới xem được. '
                            .'Chúng tôi giữ dữ liệu trong thời gian cần thiết cho việc tư vấn và chăm sóc khách hàng, hoặc đến khi bạn yêu cầu xoá — '
                            .'trừ trường hợp pháp luật yêu cầu lưu giữ lâu hơn (ví dụ hồ sơ hợp đồng mua bán).</p>'],
                    ['type' => 'text', 'title' => '6. Quyền của bạn', 'intro' => 'Bạn có quyền gì với dữ liệu của mình',
                        'body' => '<ul><li>Được biết dữ liệu nào đang được lưu và dùng vào việc gì.</li><li>Xem và yêu cầu sửa dữ liệu sai.</li>'
                            .'<li>Rút lại sự đồng ý và yêu cầu xoá dữ liệu.</li><li>Yêu cầu ngừng liên hệ tư vấn, chăm sóc.</li>'
                            .'<li>Khiếu nại nếu cho rằng dữ liệu bị xử lý sai quy định.</li></ul>'
                            .'<p>Gọi hoặc nhắn Zalo 0989 345 989 để thực hiện các quyền trên. Chúng tôi phản hồi trong thời hạn pháp luật quy định.</p>'],
                ],
            ],

            // ── Điều khoản sử dụng ────────────────────────────────────────
            'dieu-khoan' => [
                'seo' => [
                    'title'       => 'Điều khoản sử dụng website | Lexus Thăng Long',
                    'description' => 'Điều khoản sử dụng website tư vấn Lexus Thăng Long: tính chất thông tin, giá tham khảo, hình ảnh, nhãn hiệu và trách nhiệm.',
                    'eyebrow'     => 'ĐIỀU KHOẢN',
                    'excerpt'     => 'Những điều cần biết khi sử dụng thông tin trên website này.',
                ],
                'sections' => [
                    ['type' => 'text', 'title' => '1. Về website', 'intro' => 'Website này là gì',
                        'body' => '<p>Đây là website tư vấn của chuyên viên bán hàng Thu Hà tại đại lý Lexus Thăng Long, Hà Nội. '
                            .'Website <strong>không phải</strong> trang chính thức của Lexus Việt Nam hay Toyota Motor Corporation. '
                            .'Khi truy cập và sử dụng website, bạn đồng ý với các điều khoản dưới đây.</p>'
                            .'<p>Cập nhật lần cuối: 24/09/2026.</p>'],
                    ['type' => 'text', 'title' => '2. Giá và thông tin xe', 'intro' => 'Giá và thông số chỉ để tham khảo',
                        'body' => '<ul><li>Giá trên website là giá niêm yết tham khảo đã gồm VAT, có thể thay đổi theo chính sách của Lexus Việt Nam mà không báo trước. '
                            .'Giá giao dịch là giá ghi trên hợp đồng mua bán.</li>'
                            .'<li>Thông số kỹ thuật theo công bố của hãng và có thể khác nhau giữa các phiên bản, lô xe.</li>'
                            .'<li>Ví dụ về giá lăn bánh, khoản vay chỉ mang tính minh họa; số liệu chính xác theo quy định và hồ sơ tại thời điểm giao dịch.</li></ul>'],
                    ['type' => 'text', 'title' => '3. Hình ảnh', 'intro' => 'Hình ảnh xe và showroom',
                        'body' => '<p>Hình ảnh xe có thể là phiên bản hoặc thị trường khác, màu sắc hiển thị phụ thuộc màn hình. '
                            .'Hình ảnh showroom và xưởng dịch vụ là ảnh thực tế tại Lexus Thăng Long.</p>'],
                    ['type' => 'text', 'title' => '4. Nhãn hiệu', 'intro' => 'Nhãn hiệu và nội dung',
                        'body' => '<p>Tên, logo Lexus và tên các dòng xe là nhãn hiệu thuộc Toyota Motor Corporation, được dùng để giới thiệu sản phẩm do đại lý phân phối. '
                            .'Nội dung bài viết trên website không được sao chép cho mục đích thương mại khi chưa được đồng ý.</p>'],
                    ['type' => 'text', 'title' => '5. Trách nhiệm', 'intro' => 'Giới hạn trách nhiệm',
                        'body' => '<p>Chúng tôi cố gắng giữ thông tin chính xác và cập nhật, nhưng không bảo đảm website không có sai sót. '
                            .'Quyết định mua xe nên dựa trên báo giá, hợp đồng và thông tin xác nhận trực tiếp với đại lý. '
                            .'Liên kết đến website khác (bản đồ, Zalo, ngân hàng…) thuộc trách nhiệm của bên sở hữu website đó.</p>'],
                    ['type' => 'text', 'title' => '6. Liên hệ', 'intro' => 'Liên hệ và luật áp dụng',
                        'body' => '<p>Mọi câu hỏi về điều khoản, vui lòng gọi 0989 345 989. Điều khoản này được điều chỉnh theo pháp luật Việt Nam. '
                            .'Xem thêm <a href="/quyen-rieng-tu">Chính sách quyền riêng tư</a>.</p>'],
                ],
            ],
        ];
    }

    /**
     * Menu header + footer để màn hình Menu trong admin dùng được.
     *
     * Mục "Dòng xe" để trống mục con — Brands\LexusSeeder gắn từng xe vào
     * dưới đó, nên thêm xe mới không phải sửa menu tay.
     *
     * Lưu ý: header của giao diện Lexus hiện KHÔNG đọc menu này (mega menu
     * truy vấn xe trực tiếp để dựng bố cục ba cột của bản thiết kế). Menu
     * vẫn được dựng để admin có chỗ quản lý và để BrandSeeder gắn xe vào.
     */
    private function menus(): void
    {
        $header = Catalog::query('menu')->updateOrCreate(['key' => 'header'], ['name' => 'Menu chính']);
        $header->items()->delete();
        $header->items()->create(['label' => 'Dòng xe', 'url' => Url::prefix('product') ?: '/', 'sort' => 1]);
        $header->items()->create(['label' => 'Bảng giá & mua xe', 'url' => '/bang-gia', 'sort' => 2]);
        $header->items()->create(['label' => 'Thế giới Lexus', 'url' => '/the-gioi-lexus', 'sort' => 3]);
        $header->items()->create(['label' => 'Dịch vụ & chăm sóc', 'url' => '/dich-vu', 'sort' => 4]);
        $header->items()->create(['label' => 'Tin tức', 'url' => Url::prefix('post'), 'sort' => 5]);
        $header->items()->create(['label' => 'Showroom & liên hệ', 'url' => '/showroom', 'sort' => 6]);

        $footer = Catalog::query('menu')->updateOrCreate(['key' => 'footer'], ['name' => 'Menu chân trang']);
        $footer->items()->delete();
        $footer->items()->create(['label' => 'Câu hỏi thường gặp', 'url' => '/faq', 'sort' => 1]);
        $footer->items()->create(['label' => 'Quyền riêng tư', 'url' => '/quyen-rieng-tu', 'sort' => 2]);
        $footer->items()->create(['label' => 'Điều khoản', 'url' => '/dieu-khoan', 'sort' => 3]);
    }

    /**
     * Bài viết chuẩn SEO + GEO.
     *
     * Mỗi bài theo cùng một khuôn để cả người đọc lẫn công cụ AI trích dẫn
     * được:
     *   1. "Trả lời nhanh" — 2–3 câu đầu trả lời thẳng câu hỏi ở tiêu đề,
     *      có con số cụ thể (giá, phí, địa chỉ).
     *   2. Bảng số liệu — cặp nhãn → giá trị rõ ràng.
     *   3. Phân tích / hướng dẫn — tiêu đề phụ dạng câu hỏi.
     *   4. Hỏi đáp — sinh JSON-LD FAQPage.
     * Tác giả là chuyên viên thật (JsonLd::forPost), ngày cập nhật hiện rõ.
     *
     * Số liệu phí đã đối chiếu văn bản (tháng 9/2026):
     *   · Lệ phí trước bạ ô tô con tại Hà Nội: 12%.
     *   · Ô tô điện chạy pin: 0% đến hết 2030 (Nghị định 202/2026/NĐ-CP).
     *   · Phí cấp biển số ô tô đến 9 chỗ tại Hà Nội: 14 triệu đồng từ 1/1/2026
     *     (Thông tư 155/2025/TT-BTC).
     *   · Phí bảo trì đường bộ xe cá nhân < 10 chỗ: 130.000 đ/tháng.
     *   · Bảo hiểm TNDS bắt buộc xe không kinh doanh dưới 6 chỗ: 437.000 đ
     *     chưa VAT (Nghị định 67/2023/NĐ-CP).
     * Khi quy định đổi, sửa bài trong admin — ngày cập nhật tự đổi theo.
     */
    private function posts(): void
    {
        // Ba bài demo của template: nội dung chung chung, không trả lời câu
        // hỏi nào — dọn đi để không thành trang mỏng trong chỉ mục.
        Catalog::query('post')->whereIn('slug', [
            'khi-su-hoan-hao-bat-dau-tu-nhung-dieu-nho-nhat',
            'omotenashi-su-thau-hieu-khong-can-loi-noi',
            'tim-lai-nhip-rieng-tren-nhung-cung-duong-moi',
        ])->forceDelete();

        $categories = [
            'bang-gia-mua-xe'  => 'Bảng giá & mua xe',
            'tu-van-chon-xe'   => 'Tư vấn chọn xe',
            'lexus-thang-long' => 'Lexus Thăng Long',
        ];

        $categoryIds = [];
        $sort = 0;
        foreach ($categories as $slug => $name) {
            $categoryIds[$slug] = Catalog::query('post_category')
                ->updateOrCreate(['slug' => $slug], ['name' => $name, 'sort' => ++$sort])->id;
        }

        // Chuyên mục demo cũ không còn bài nào thì dọn.
        Catalog::query('post_category')
            ->whereIn('slug', ['nghe-thuat-che-tac', 'lexus-experience', 'hanh-trinh'])
            ->whereDoesntHave('posts')->delete();

        foreach ($this->articles() as $i => $article) {
            Catalog::query('post')->updateOrCreate(
                ['slug' => $article['slug']],
                [
                    'title'            => $article['title'],
                    'post_category_id' => $categoryIds[$article['category']],
                    'cover'            => $this->facility($article['cover']) ?? $this->carImage($article['cover']),
                    'excerpt'          => $article['excerpt'],
                    'status'           => 'published',
                    'published_at'     => now()->subDays($i * 3),
                    'sections'         => $article['sections'],
                    'seo'              => [
                        'title'       => $article['seo_title'],
                        'description' => $article['excerpt'],
                        'keywords'    => $article['keywords'],
                    ],
                ],
            );
        }
    }

    /** Ảnh xe đã nạp bởi Brands\LexusSeeder, dạng "rx/hero". */
    private function carImage(string $key): ?string
    {
        return str_contains($key, '/') ? "catalog/lexus/{$key}.webp" : null;
    }

    /** @return array<int, array<string, mixed>> */
    private function articles(): array
    {
        $text = fn (string $title, string $intro, string $body) => ['type' => 'text', 'title' => $title, 'intro' => $intro, 'body' => $body];
        $table = fn (string $title, string $intro, array $rows) => [
            'type' => 'table', 'title' => $title, 'intro' => $intro,
            'rows' => collect($rows)->map(fn ($v, $k) => ['label' => $k, 'value' => $v])->values()->all(),
        ];
        $faq = fn (array $rows) => [
            'type' => 'faq', 'title' => 'Hỏi đáp', 'intro' => 'Câu hỏi thường gặp',
            'rows' => collect($rows)->map(fn ($v, $k) => ['label' => $k, 'value' => $v])->values()->all(),
        ];
        // Thẻ ảnh 2 cột (layout cols-2): so sánh phiên bản bằng ảnh + giá.
        $cards = fn (string $title, string $intro, array $items) => [
            'type' => 'media', 'layout' => 'cols-2', 'title' => $title, 'intro' => $intro, 'items' => $items,
        ];
        // Ảnh đã nạp bởi Brands\LexusSeeder (media/lexus/{xe}/…).
        $car = fn (string $path) => "catalog/lexus/{$path}.webp";

        return [
            [
                'slug'      => 'lexus-es-2026-the-he-moi-nhung-dieu-can-biet',
                'title'     => 'Lexus ES 2026 thế hệ mới: 350h hybrid và 500e thuần điện — những điều cần biết',
                'seo_title' => 'Lexus ES 2026 thế hệ mới: ES 350h, ES 500e — thiết kế, trang bị, giá',
                'category'  => 'tu-van-chon-xe',
                'cover'     => 'es/360/xanh-duong/04',
                'keywords'  => 'Lexus ES 2026, ES thế hệ mới, Lexus ES 350h, Lexus ES 500e, ES thuần điện, giá Lexus ES 2026',
                'excerpt'   => 'Lexus ES thế hệ thứ 8 có 3 phiên bản: ES 350h Premium, ES 350h Luxury và ES 500e thuần điện. '
                    .'Thiết kế Spindle Body, màn hình 14 inch, gương chiếu hậu kỹ thuật số. Giá đang cập nhật — đăng ký để nhận giá sớm.',
                'sections'  => [
                    $text('Trả lời nhanh', 'ES 2026 có gì mới?',
                        '<p><strong>Lexus ES 2026 là thế hệ thứ 8, lần đầu có bản thuần điện ES 500e bên cạnh hybrid ES 350h.</strong> '
                        .'Tại Việt Nam, ES có 3 phiên bản: ES 350h Premium, ES 350h Luxury và ES 500e. Giá bán đang được cập nhật.</p>'
                        .'<p>Xe đổi sang dáng fastback với khối đầu xe "Spindle Body" liền thân thay cho lưới tản nhiệt truyền thống, '
                        .'khoang sau rộng hơn và khoang lái nhiều màn hình.</p>'),
                    $cards('Phiên bản', 'Ba phiên bản ES tại Việt Nam', [
                        ['image' => $car('es/phien-ban/premium'), 'eyebrow' => 'Hybrid', 'label' => 'ES 350h Premium',
                            'desc' => 'Hybrid xăng – điện, không cần sạc. Phiên bản khởi điểm của ES thế hệ mới.', 'url' => '/san-pham/es#versions'],
                        ['image' => $car('es/360/xam/04'), 'eyebrow' => 'Hybrid', 'label' => 'ES 350h Luxury',
                            'desc' => 'Cùng hệ hybrid, trang bị tiện nghi cao hơn.', 'url' => '/san-pham/es#versions'],
                        ['image' => $car('es/360/xanh-duong/04'), 'eyebrow' => 'Thuần điện', 'label' => 'ES 500e',
                            'desc' => 'Chạy hoàn toàn bằng điện, lệ phí trước bạ 0% đến hết năm 2030.', 'url' => '/san-pham/es#versions'],
                        ['image' => $car('es/chi-tiet/man-hinh-14'), 'eyebrow' => 'Khoang lái', 'label' => 'Màn hình 14 inch',
                            'desc' => 'Apple CarPlay, Android Auto không dây; HUD và gương chiếu hậu kỹ thuật số.', 'url' => '/san-pham/es#chi-tiet'],
                    ]),
                    $table('Tóm tắt', 'ES 350h và ES 500e khác nhau thế nào?', [
                        'Truyền động' => 'ES 350h: hybrid xăng – điện · ES 500e: thuần điện',
                        'Sạc điện'    => 'ES 350h: không cần sạc · ES 500e: sạc tại nhà hoặc trạm sạc',
                        'Lệ phí trước bạ tại Hà Nội' => 'ES 350h: 12% · ES 500e: 0% đến hết 31/12/2030',
                        'Phù hợp'     => 'ES 350h: đi phố và đường dài · ES 500e: đi phố hằng ngày, có chỗ sạc',
                        'Giá niêm yết' => 'Đang cập nhật',
                    ]),
                    $text('Nhận giá sớm', 'Làm sao để có giá ES 2026 sớm nhất?',
                        '<p>Để lại họ tên, số điện thoại và chọn Lexus ES tại mục <a href="/bao-gia?xe=es">Nhận báo giá</a>. '
                        .'Chuyên viên Thu Hà sẽ gọi báo giá niêm yết và giá lăn bánh từng phiên bản ngay khi Lexus Việt Nam công bố, '
                        .'kèm lịch xe lái thử tại Lexus Thăng Long, Cầu Giấy.</p>'),
                    $faq([
                        'Lexus ES 2026 có mấy phiên bản?' => 'Ba phiên bản: ES 350h Premium, ES 350h Luxury (hybrid) và ES 500e (thuần điện).',
                        'Giá Lexus ES 2026 bao nhiêu?' => 'Giá niêm yết đang được cập nhật. Đăng ký nhận báo giá để được báo ngay khi có giá chính thức.',
                        'ES 500e có được miễn lệ phí trước bạ không?' => 'Có. Ô tô điện chạy pin được áp lệ phí trước bạ lần đầu 0% đến hết năm 2030 (Nghị định 202/2026/NĐ-CP).',
                        'ES 350h có phải cắm sạc không?' => 'Không. ES 350h là hybrid tự sạc khi phanh và khi động cơ xăng chạy.',
                    ]),
                ],
            ],
            [
                'slug'      => 'lexus-nx-350h-hay-nx-350-f-sport',
                'title'     => 'Lexus NX 350h hay NX 350 F SPORT: hybrid tiết kiệm hay tăng áp thể thao?',
                'seo_title' => 'NX 350h hay NX 350 F SPORT? So sánh giá, công suất, mức tiêu hao',
                'category'  => 'tu-van-chon-xe',
                'cover'     => 'nx/hero',
                'keywords'  => 'Lexus NX 350h, Lexus NX 350 F SPORT, so sánh NX 350h và NX 350, giá Lexus NX 2026',
                'excerpt'   => 'NX 350 F SPORT (3,13 tỷ) mạnh 275 HP, có treo thích ứng AVS; NX 350h (3,27 tỷ) là hybrid 240 HP, '
                    .'tiêu thụ 6,65 L/100 km. So sánh chi tiết để chọn đúng bản NX cho bạn.',
                'sections'  => [
                    $text('Trả lời nhanh', 'Nên chọn NX 350h hay NX 350 F SPORT?',
                        '<p><strong>Chọn NX 350h nếu ưu tiên tiết kiệm và êm ái; chọn NX 350 F SPORT nếu thích cảm giác lái thể thao.</strong> '
                        .'NX 350h là hybrid 2.5L, 240 HP, tiêu thụ 6,65 L/100 km đường hỗn hợp. NX 350 F SPORT dùng máy xăng 2.4L tăng áp, '
                        .'275 HP, 430 Nm, treo thích ứng AVS.</p>'
                        .'<p>Bản F SPORT lại rẻ hơn 140 triệu đồng: 3,13 tỷ so với 3,27 tỷ của NX 350h.</p>'),
                    $table('So sánh', 'NX 350h và NX 350 F SPORT', [
                        'Giá niêm yết'   => 'NX 350h: 3,27 tỷ · NX 350 F SPORT: 3,13 tỷ',
                        'Động cơ'        => 'NX 350h: hybrid 2.5L · F SPORT: xăng 2.4L tăng áp',
                        'Công suất'      => 'NX 350h: 240 HP (tổng hệ thống) · F SPORT: 275 HP, 430 Nm',
                        'Tiêu thụ hỗn hợp' => 'NX 350h: 6,65 L/100 km · F SPORT: 10,75 L/100 km',
                        'Treo thích ứng AVS' => 'NX 350h: không · F SPORT: có',
                        'Dẫn động'       => 'Cả hai: AWD',
                    ]),
                    $cards('Phiên bản', 'Hai lựa chọn NX', [
                        ['image' => $car('nx/phien-ban/350h'), 'eyebrow' => '3,27 tỷ', 'label' => 'NX 350h',
                            'desc' => 'Hybrid 240 HP, 6,65 L/100 km — hợp đi phố đông mỗi ngày.', 'url' => '/san-pham/nx#versions'],
                        ['image' => $car('nx/360/xanh-duong/02'), 'eyebrow' => '3,13 tỷ', 'label' => 'NX 350 F SPORT',
                            'desc' => 'Tăng áp 275 HP, treo AVS, màu Heat Blue và nội thất Flare Red riêng.', 'url' => '/san-pham/nx#versions'],
                    ]),
                    $text('Tư vấn', 'Chọn theo cách bạn đi xe',
                        '<ul><li>Đi phố Hà Nội là chính, tắc đường nhiều: <strong>NX 350h</strong> — hybrid chạy điện ở tốc độ thấp, tiết kiệm rõ rệt.</li>'
                        .'<li>Hay đi cao tốc, thích tăng tốc và vào cua chắc chắn: <strong>NX 350 F SPORT</strong>.</li>'
                        .'<li>Chưa chắc chắn: lái thử cả hai trong một buổi tại Lexus Thăng Long — <a href="/dang-ky-lai-thu">đăng ký lái thử</a>.</li></ul>'),
                    $faq([
                        'NX 350 F SPORT có phải xe hybrid không?' => 'Không. NX 350 F SPORT dùng động cơ xăng 2.4L tăng áp; bản hybrid là NX 350h.',
                        'NX 350h tốn bao nhiêu xăng?' => 'Khoảng 6,65 L/100 km đường hỗn hợp và 6,44 L/100 km trong đô thị theo công bố.',
                        'Bản nào được bảo hành 10 năm?' => 'Chương trình bảo hành 10 năm không giới hạn km áp dụng cho xe hybrid bán mới từ 8/5/2026 — tức NX 350h, với điều kiện bảo dưỡng định kỳ tại đại lý ủy quyền.',
                    ]),
                ],
            ],
            [
                'slug'      => 'lexus-lm-500h-4-cho-hay-6-cho',
                'title'     => 'Lexus LM 500h 4 chỗ hay 6 chỗ: khác nhau thế nào, chọn bản nào?',
                'seo_title' => 'Lexus LM 500h 4 chỗ VIP hay 6 chỗ? Giá 7,21 – 8,95 tỷ, so sánh',
                'category'  => 'tu-van-chon-xe',
                'cover'     => 'lm/360/xam/05',
                'keywords'  => 'Lexus LM 500h, LM 4 chỗ, LM 6 chỗ, giá Lexus LM 2026, MPV hạng sang Hà Nội',
                'excerpt'   => 'LM 500h 6 chỗ giá 7,21 tỷ hợp gia đình và doanh nghiệp; LM 500h 4 chỗ VIP giá 8,95 tỷ có vách ngăn, '
                    .'màn hình 48 inch và hai ghế thương gia — dành cho đưa đón lãnh đạo.',
                'sections'  => [
                    $text('Trả lời nhanh', 'Khác biệt lớn nhất là gì?',
                        '<p><strong>LM 500h 4 chỗ VIP (8,95 tỷ) là "văn phòng di động": vách ngăn với khoang lái, màn hình 48 inch, hai ghế thương gia. '
                        .'LM 500h 6 chỗ (7,21 tỷ) thêm hàng ghế thứ ba cho gia đình và doanh nghiệp.</strong> Hai bản cùng hệ truyền động hybrid, '
                        .'chênh nhau 1,74 tỷ đồng.</p>'),
                    $cards('Phiên bản', 'Hai cấu hình LM', [
                        ['image' => $car('lm/phien-ban/6-cho'), 'eyebrow' => '7,21 tỷ', 'label' => 'LM 500h 6 chỗ',
                            'desc' => 'Hai ghế thương gia hàng giữa + hàng ghế thứ ba gập linh hoạt.', 'url' => '/san-pham/lm#versions'],
                        ['image' => $car('lm/phien-ban/4-cho'), 'eyebrow' => '8,95 tỷ', 'label' => 'LM 500h 4 chỗ VIP',
                            'desc' => 'Vách ngăn kính mờ, màn hình 48 inch, ghế ngả gần như phẳng.', 'url' => '/san-pham/lm#versions'],
                    ]),
                    $table('So sánh', 'LM 500h 6 chỗ và 4 chỗ VIP', [
                        'Giá niêm yết'   => '6 chỗ: 7,21 tỷ · 4 chỗ VIP: 8,95 tỷ',
                        'Số chỗ'         => '6 chỗ: 2 + 2 ghế thương gia + hàng 3 · 4 chỗ: 2 + 2 ghế thương gia',
                        'Vách ngăn khoang lái' => '6 chỗ: không · 4 chỗ: có, kính làm mờ bằng điện',
                        'Màn hình khoang sau' => '4 chỗ: 48 inch',
                        'Phù hợp'        => '6 chỗ: gia đình, doanh nghiệp · 4 chỗ: đưa đón lãnh đạo, khách VIP',
                    ]),
                    $faq([
                        'Lexus LM 500h giá bao nhiêu?' => 'LM 500h 6 chỗ 7,21 tỷ đồng; LM 500h 4 chỗ VIP 8,95 tỷ đồng (giá niêm yết, đã gồm VAT).',
                        'LM có tự lái được không hay cần tài xế?' => 'Tự lái được; tuy vậy bản 4 chỗ VIP thiết kế tối ưu cho hành khách phía sau nên thường đi kèm tài xế.',
                        'Có xem LM thực tế ở Hà Nội không?' => 'Có. Hẹn trước với chuyên viên tại Lexus Thăng Long, Cầu Giấy — hotline 0989 345 989.',
                    ]),
                ],
            ],
            [
                'slug'      => 'lexus-gx-550m-gx-550-hay-lx-600',
                'title'     => 'Lexus GX 550M, GX 550 hay LX 600: chọn SUV khung gầm rời nào?',
                'seo_title' => 'GX 550M, GX 550 hay LX 600? So sánh giá, động cơ, phiên bản',
                'category'  => 'tu-van-chon-xe',
                'cover'     => 'gx/hero',
                'keywords'  => 'Lexus GX 550, GX 550M, Lexus LX 600, so sánh GX và LX, SUV khung gầm rời Lexus',
                'excerpt'   => 'GX 550M (6,40 tỷ) và GX 550 (6,45 tỷ) dùng V6 3.5L tăng áp kép 349 HP, 7 chỗ; LX 600 (8,59 – 9,70 tỷ) mạnh 409 HP '
                    .'với ba bản Urban, F SPORT, VIP. So sánh để chọn đúng xe.',
                'sections'  => [
                    $text('Trả lời nhanh', 'Chọn GX hay LX?',
                        '<p><strong>GX 550 gọn hơn và rẻ hơn LX khoảng 2 tỷ đồng; LX 600 lớn hơn, mạnh hơn và có bản VIP 4 chỗ.</strong> '
                        .'Cả hai đều là SUV khung gầm rời dùng động cơ V6 3.5L tăng áp kép: GX 349 HP, LX 409 HP.</p>'
                        .'<p>Giữa hai bản GX: GX 550M (6,40 tỷ) thiên về khám phá với mâm địa hình và ghế kháng bẩn; '
                        .'GX 550 (6,45 tỷ) thiên về sang trọng với da nâu Flaxen và ốp gỗ.</p>'),
                    $table('So sánh', 'GX 550 và LX 600', [
                        'Giá niêm yết' => 'GX: 6,40 – 6,45 tỷ · LX: 8,59 – 9,70 tỷ',
                        'Động cơ'      => 'GX: V6 3.5L tăng áp kép, 349 HP, 650 Nm · LX: V6 3.5L tăng áp kép, 409 HP, 650 Nm',
                        'Số chỗ'       => 'GX: 7 · LX: 7 (Urban), 5 (F SPORT), 4 (VIP)',
                        'Khoảng sáng gầm' => 'GX: 220 mm · LX: 205 mm',
                        'Phiên bản'    => 'GX: 550M, 550 · LX: Urban, F SPORT, VIP',
                    ]),
                    $cards('Phiên bản', 'Năm lựa chọn khung gầm rời', [
                        ['image' => $car('gx/phien-ban/overtrail'), 'eyebrow' => '6,40 tỷ', 'label' => 'GX 550M',
                            'desc' => 'Mâm địa hình chuyên dụng, ghế da tổng hợp kháng bẩn.', 'url' => '/san-pham/gx#versions'],
                        ['image' => $car('gx/phien-ban/luxury'), 'eyebrow' => '6,45 tỷ', 'label' => 'GX 550',
                            'desc' => 'Da nâu Flaxen, ốp gỗ nội thất.', 'url' => '/san-pham/gx#versions'],
                        ['image' => $car('lx/phien-ban/urban'), 'eyebrow' => '8,59 tỷ', 'label' => 'LX 600 Urban',
                            'desc' => '7 chỗ, mâm 22 inch.', 'url' => '/san-pham/lx#versions'],
                        ['image' => $car('lx/phien-ban/vip'), 'eyebrow' => '9,70 tỷ', 'label' => 'LX 600 VIP',
                            'desc' => '4 chỗ, ghế sau thương gia ngả 48 độ.', 'url' => '/san-pham/lx#versions'],
                    ]),
                    $faq([
                        'GX 550M khác GX 550 thế nào?' => 'Cùng động cơ và 7 chỗ. GX 550M có mâm địa hình, ghế da tổng hợp kháng bẩn; GX 550 có da nâu Flaxen, ốp gỗ. Chênh 50 triệu đồng.',
                        'LX 600 có mấy phiên bản?' => 'Ba: LX 600 Urban 7 chỗ 8,59 tỷ, LX 600 F SPORT 5 chỗ 8,84 tỷ, LX 600 VIP 4 chỗ 9,70 tỷ.',
                        'GX và LX có bản hybrid không?' => 'Các phiên bản GX 550 và LX 600 đang bán dùng động cơ xăng V6 tăng áp kép, không có bản hybrid.',
                    ]),
                ],
            ],
            [
                'slug'      => 'bang-gia-xe-lexus-2026-tai-ha-noi',
                'title'     => 'Bảng giá xe Lexus 2026 tại Hà Nội: đủ 7 dòng xe, từng phiên bản',
                'seo_title' => 'Bảng giá xe Lexus 2026 tại Hà Nội — 7 dòng xe, 16 phiên bản',
                'category'  => 'bang-gia-mua-xe',
                'cover'     => 'khu-trung-bay',
                'keywords'  => 'bảng giá xe Lexus 2026, giá xe Lexus Hà Nội, giá Lexus RX, giá Lexus ES, giá Lexus LX',
                'excerpt'   => 'Giá xe Lexus 2026 tại Hà Nội từ 3,13 tỷ đồng (NX 350 F SPORT) đến 9,7 tỷ đồng (LX 600 VIP); ES thế hệ mới đang cập nhật giá. '
                    .'Bảng giá niêm yết đủ 7 dòng xe, 16 phiên bản tại Lexus Thăng Long, Hà Nội.',
                'sections'  => [
                    $text('Trả lời nhanh', 'Xe Lexus 2026 giá bao nhiêu?',
                        '<p>Tại Hà Nội, <strong>xe Lexus 2026 có giá niêm yết từ 3,13 tỷ đồng (NX 350 F SPORT) đến 9,7 tỷ đồng (LX 600 VIP)</strong>; '
                        .'riêng ES thế hệ mới (350h, 500e thuần điện) đang cập nhật giá. '
                        .'Lexus Thăng Long đang phân phối 7 dòng xe với 16 phiên bản: sedan ES, LS; SUV NX, RX, GX, LX và MPV LM.</p>'
                        .'<p>Giá dưới đây là giá niêm yết đã gồm VAT, chưa gồm lệ phí trước bạ và phí đăng ký. '
                        .'Bảng giá luôn cập nhật theo đại lý có tại trang <a href="/bang-gia">Bảng giá xe Lexus</a>.</p>'),
                    $table('Giá khởi điểm', 'Giá từ của từng dòng xe', [
                        'Lexus ES (sedan)'       => 'đang cập nhật — 3 phiên bản, gồm ES 500e thuần điện',
                        'Lexus NX (SUV cỡ nhỏ)'  => 'từ 3.130.000.000 đ — NX 350 F SPORT và NX 350h',
                        'Lexus RX (SUV cỡ trung)' => 'từ 3.350.000.000 đ — 3 phiên bản',
                        'Lexus GX (SUV khung rời)' => 'từ 6.400.000.000 đ — GX 550M và GX 550',
                        'Lexus LM (MPV)'         => 'từ 7.210.000.000 đ — 6 chỗ và 4 chỗ VIP',
                        'Lexus LS (sedan đầu bảng)' => '8.030.000.000 đ — LS 500h',
                        'Lexus LX (SUV đầu bảng)' => 'từ 8.590.000.000 đ — 3 phiên bản',
                    ]),
                    $text('Chọn theo ngân sách', 'Nên chọn Lexus nào với ngân sách của bạn?',
                        '<h3>Sedan cỡ trung</h3><p>ES thế hệ mới gồm ES 350h Premium, ES 350h Luxury và ES 500e thuần điện — giá đang cập nhật. '
                        .'ES là sedan êm, khoang sau rộng — hợp đi phố và đưa đón gia đình.</p>'
                        .'<h3>Từ 3 đến 5 tỷ đồng</h3><p>NX 350 F SPORT (3,13 tỷ) hoặc NX 350h hybrid (3,27 tỷ) cho người cần SUV gọn trong phố; RX 350h Premium (3,35 tỷ), '
                        .'RX 350h Luxury (4,14 tỷ) và RX 500h F SPORT Performance (4,94 tỷ) cho gia đình cần khoang rộng hơn.</p>'
                        .'<h3>Trên 6 tỷ đồng</h3><p>GX 550M, GX 550 (từ 6,4 tỷ) và LX 600 (từ 8,59 tỷ) cho nhu cầu SUV khung gầm rời, 7 chỗ; '
                        .'LM 500h (từ 7,21 tỷ) cho đưa đón lãnh đạo; LS 500h (8,03 tỷ) cho người thích sedan tự lái.</p>'),
                    $faq([
                        'Xe Lexus rẻ nhất năm 2026 là xe nào?' => 'Trong các phiên bản đã có giá, rẻ nhất là Lexus NX 350 F SPORT 3,13 tỷ đồng (đã gồm VAT). ES thế hệ mới đang cập nhật giá.',
                        'Xe Lexus đắt nhất tại Việt Nam là xe nào?' => 'Lexus LX 600 VIP 4 chỗ, giá niêm yết 9,7 tỷ đồng.',
                        'Giá niêm yết đã là giá lăn bánh chưa?' => 'Chưa. Cần cộng lệ phí trước bạ 12% (xe xăng/hybrid tại Hà Nội), phí biển số 14 triệu đồng và các phí đăng ký khác. '
                            .'Xem cách tính trong bài giá lăn bánh Lexus tại Hà Nội.',
                        'Mua xe Lexus ở đâu tại Hà Nội?' => 'Lexus Thăng Long, ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy. Hotline 0989 345 989 (Thu Hà).',
                    ]),
                ],
            ],
            [
                'slug'      => 'gia-lan-banh-lexus-ha-noi-2026-cach-tinh',
                'title'     => 'Giá lăn bánh Lexus tại Hà Nội 2026: cách tính từng khoản, ví dụ RX và ES 500e',
                'seo_title' => 'Giá lăn bánh Lexus Hà Nội 2026: cách tính, ví dụ RX 350h, ES 500e',
                'category'  => 'bang-gia-mua-xe',
                'cover'     => 'khu-ban-giao',
                'keywords'  => 'giá lăn bánh Lexus, lăn bánh Lexus Hà Nội, lệ phí trước bạ ô tô Hà Nội 2026, phí biển số Hà Nội 14 triệu',
                'excerpt'   => 'Giá lăn bánh Lexus tại Hà Nội = giá niêm yết + 12% lệ phí trước bạ + 14 triệu phí biển số + phí bảo trì đường bộ, '
                    .'bảo hiểm TNDS và đăng kiểm. Ví dụ: RX 350h Premium lăn bánh khoảng 3,77 tỷ đồng.',
                'sections'  => [
                    $text('Trả lời nhanh', 'Giá lăn bánh Lexus ở Hà Nội tính thế nào?',
                        '<p><strong>Giá lăn bánh = giá niêm yết + lệ phí trước bạ + phí cấp biển số + phí bảo trì đường bộ + bảo hiểm trách nhiệm dân sự + phí đăng kiểm.</strong> '
                        .'Tại Hà Nội năm 2026, lệ phí trước bạ ô tô con chạy xăng và hybrid là 12%, phí cấp biển số là 14 triệu đồng (giảm từ 20 triệu kể từ 1/1/2026).</p>'
                        .'<p>Riêng ô tô điện chạy pin như Lexus ES 500e được áp lệ phí trước bạ 0% đến hết năm 2030, nên giá lăn bánh gần như bằng giá niêm yết.</p>'),
                    $table('Các khoản phí', 'Các khoản khi đăng ký xe tại Hà Nội (2026)', [
                        'Lệ phí trước bạ — xe xăng, hybrid' => '12% giá tính lệ phí trước bạ',
                        'Lệ phí trước bạ — ô tô điện chạy pin' => '0% đến hết 31/12/2030 (Nghị định 202/2026/NĐ-CP)',
                        'Phí cấp biển số (xe đến 9 chỗ)'     => '14.000.000 đ (Thông tư 155/2025/TT-BTC, từ 1/1/2026)',
                        'Phí bảo trì đường bộ'             => '130.000 đ/tháng — 1.560.000 đ/năm (xe cá nhân dưới 10 chỗ)',
                        'Bảo hiểm TNDS bắt buộc'           => '480.700 đ/năm (xe dưới 6 chỗ) — 873.400 đ/năm (6–11 chỗ), đã gồm VAT',
                        'Phí đăng kiểm'                   => 'vài trăm nghìn đồng, theo biểu phí hiện hành',
                        'Bảo hiểm vật chất'               => 'không bắt buộc, thường 1–1,5% giá xe/năm tùy gói',
                    ]),
                    $text('Ví dụ', 'Ví dụ: Lexus RX 350h Premium lăn bánh bao nhiêu?',
                        '<table><thead><tr><th>Khoản</th><th>Số tiền</th></tr></thead><tbody>'
                        .'<tr><td>Giá niêm yết</td><td>3.350.000.000 đ</td></tr>'
                        .'<tr><td>Lệ phí trước bạ 12%</td><td>402.000.000 đ</td></tr>'
                        .'<tr><td>Phí cấp biển số</td><td>14.000.000 đ</td></tr>'
                        .'<tr><td>Phí bảo trì đường bộ 1 năm</td><td>1.560.000 đ</td></tr>'
                        .'<tr><td>Bảo hiểm TNDS 1 năm (5 chỗ)</td><td>480.700 đ</td></tr>'
                        .'<tr><th>Tạm tính (chưa gồm đăng kiểm, bảo hiểm vật chất)</th><th>khoảng 3.768.040.700 đ</th></tr>'
                        .'</tbody></table>'
                        .'<p>Với <strong>Lexus ES 500e thuần điện</strong>, lệ phí trước bạ là 0 đồng, nên giá lăn bánh chỉ cao hơn giá niêm yết '
                        .'<strong>khoảng 16 triệu đồng</strong> (biển số 14 triệu + phí đường bộ 1 năm + bảo hiểm TNDS). Với xe hybrid, riêng '
                        .'lệ phí trước bạ đã là 12% giá xe.</p>'
                        .'<p>Lệ phí trước bạ tính trên giá trong bảng giá tính lệ phí trước bạ của cơ quan thuế, có thể chênh nhẹ so với giá bán. '
                        .'Các con số trên để tham khảo; bảng tính chính xác theo phiên bản và ưu đãi hiện hành, chuyên viên gửi riêng khi bạn '
                        .'<a href="/bao-gia">nhận báo giá</a>.</p>'),
                    $faq([
                        'Lệ phí trước bạ ô tô ở Hà Nội năm 2026 là bao nhiêu?' => '12% với ô tô con chạy xăng, dầu và hybrid. Ô tô điện chạy pin được áp mức 0% đến hết năm 2030.',
                        'Phí biển số ô tô Hà Nội 2026 là bao nhiêu?' => '14 triệu đồng với ô tô chở người từ 9 chỗ trở xuống, áp dụng từ 1/1/2026 theo Thông tư 155/2025/TT-BTC (trước đó là 20 triệu đồng).',
                        'Xe hybrid Lexus có được giảm lệ phí trước bạ không?' => 'Không. Ưu đãi 0% chỉ áp dụng cho ô tô điện chạy pin (như ES 500e). Xe hybrid như RX 350h, NX 350h, ES 350h vẫn nộp 12% tại Hà Nội.',
                        'Lexus RX 350h Premium lăn bánh Hà Nội khoảng bao nhiêu?' => 'Khoảng 3,77 tỷ đồng, chưa gồm phí đăng kiểm và bảo hiểm vật chất.',
                    ]),
                ],
            ],
            [
                'slug'      => 'lexus-rx-350h-hay-rx-500h-chon-ban-nao',
                'title'     => 'Lexus RX 350h hay RX 500h: chọn bản nào cho bạn?',
                'seo_title' => 'Lexus RX 350h hay RX 500h F SPORT: so sánh giá, vận hành',
                'category'  => 'tu-van-chon-xe',
                'cover'     => 'rx/van-hanh',
                'keywords'  => 'Lexus RX 350h, Lexus RX 500h, so sánh RX 350h và RX 500h, giá Lexus RX 2026',
                'excerpt'   => 'RX 350h (từ 3,35 tỷ) hợp người đi phố, ưu tiên êm và tiết kiệm; RX 500h F SPORT Performance (4,94 tỷ) có 371 HP, '
                    .'0–100 km/h 5,9 giây cho người thích cảm giác lái. So sánh chi tiết từ chuyên viên Lexus Thăng Long.',
                'sections'  => [
                    $text('Trả lời nhanh', 'Khác biệt lớn nhất giữa RX 350h và RX 500h là gì?',
                        '<p><strong>RX 350h là hybrid 2.5L thiên về êm ái và tiết kiệm; RX 500h là hybrid tăng áp 2.4L, 371 mã lực, thiên về hiệu năng.</strong> '
                        .'Chênh lệch giá giữa RX 350h Premium (3,35 tỷ) và RX 500h F SPORT Performance (4,94 tỷ) là 1,59 tỷ đồng.</p>'
                        .'<p>Nếu phần lớn quãng đường là trong phố Hà Nội, RX 350h là lựa chọn hợp lý hơn. Nếu bạn hay đi cao tốc, thích tăng tốc mạnh '
                        .'và muốn ngoại hình F SPORT, hãy lái thử RX 500h.</p>'),
                    $table('So sánh', 'RX 350h và RX 500h F SPORT Performance', [
                        'Giá niêm yết'  => 'RX 350h: 3,35 tỷ (Premium) · 4,14 tỷ (Luxury) — RX 500h: 4,94 tỷ',
                        'Động cơ'       => 'RX 350h: hybrid 2.5L — RX 500h: 2.4L tăng áp + 2 mô-tơ điện',
                        'Công suất'     => 'RX 500h: 371 HP, mô-men xoắn 460 Nm',
                        'Tăng tốc 0–100 km/h' => 'RX 500h: 5,9 giây',
                        'Dẫn động'      => 'RX 350h: AWD E-Four — RX 500h: AWD DIRECT4',
                        'Hộp số'        => 'RX 350h: e-CVT — RX 500h: tự động 6 cấp',
                        'Số chỗ'        => '5 chỗ, khoang hành lý 612 lít (cả hai)',
                    ]),
                    $text('Tư vấn', 'Ai nên chọn bản nào?',
                        '<h3>Chọn RX 350h Premium nếu</h3><ul><li>Bạn đi phố là chính, thường xuyên tắc đường — hybrid chạy điện nhiều ở tốc độ thấp.</li>'
                        .'<li>Bạn muốn một chiếc SUV hạng sang êm, bền, chi phí vận hành thấp.</li></ul>'
                        .'<h3>Chọn RX 350h Luxury nếu</h3><ul><li>Bạn muốn trang bị tiện nghi cao hơn cho gia đình với cùng hệ truyền động hybrid 2.5L.</li></ul>'
                        .'<h3>Chọn RX 500h F SPORT Performance nếu</h3><ul><li>Bạn thích cảm giác lái thể thao: 371 HP, 0–100 km/h 5,9 giây.</li>'
                        .'<li>Bạn thích ngoại hình F SPORT với mâm 21 inch và hệ DIRECT4 phân bổ lực kéo linh hoạt.</li></ul>'
                        .'<p>Cách chắc chắn nhất: lái thử cả hai bản trong cùng một buổi. <a href="/dang-ky-lai-thu">Đăng ký lái thử</a> tại Lexus Thăng Long.</p>'),
                    $faq([
                        'RX 350h có phải cắm sạc không?' => 'Không. RX 350h là hybrid tự sạc: pin được nạp lại khi phanh và khi động cơ xăng chạy.',
                        'RX 500h tiêu hao bao nhiêu nhiên liệu?' => 'Theo công bố, RX 500h F SPORT Performance tiêu thụ khoảng 8,1 L/100 km đường hỗn hợp.',
                        'Lexus RX có bản 7 chỗ không?' => 'Các phiên bản RX đang bán tại Lexus Thăng Long đều là 5 chỗ. Nếu cần 7 chỗ, tham khảo Lexus GX 550 hoặc LX 600 Urban.',
                    ]),
                ],
            ],
            [
                'slug'      => 'showroom-lexus-thang-long-dia-chi-gio-mo-cua',
                'title'     => 'Showroom Lexus Thăng Long: địa chỉ, giờ mở cửa và cách đặt lịch lái thử',
                'seo_title' => 'Lexus Thăng Long ở đâu? Địa chỉ, giờ mở cửa, lái thử',
                'category'  => 'lexus-thang-long',
                'cover'     => 'mat-tien-toan-canh',
                'keywords'  => 'Lexus Thăng Long, đại lý Lexus Hà Nội, showroom Lexus Cầu Giấy, Lexus Phạm Hùng, lái thử Lexus Hà Nội',
                'excerpt'   => 'Lexus Thăng Long ở ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy, Hà Nội, mở cửa 8:00–18:00 tất cả các ngày. '
                    .'Có đủ 7 dòng xe Lexus, xe lái thử và xưởng dịch vụ chính hãng.',
                'sections'  => [
                    $text('Trả lời nhanh', 'Lexus Thăng Long ở đâu?',
                        '<p><strong>Lexus Thăng Long nằm tại ngã tư Phạm Hùng – Dương Đình Nghệ, quận Cầu Giấy, Hà Nội, mở cửa từ 8:00 đến 18:00, Thứ Hai đến Chủ Nhật.</strong> '
                        .'Hotline chuyên viên tư vấn: 0989 345 989 (Thu Hà).</p>'
                        .'<p>Đại lý có showroom trưng bày đủ 7 dòng xe Lexus, xe lái thử, khu bàn giao riêng, phòng chờ và xưởng dịch vụ với buồng sơn sấy.</p>'),
                    $table('Thông tin', 'Thông tin nhanh về đại lý', [
                        'Địa chỉ'      => 'Ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy, Hà Nội',
                        'Giờ mở cửa'   => 'Thứ Hai – Chủ Nhật, 08:00 – 18:00',
                        'Hotline'      => '0989 345 989 — Thu Hà, chuyên viên tư vấn',
                        'Dòng xe'      => 'ES, NX, RX, GX, LX, LM, LS',
                        'Dịch vụ'      => 'Bán xe mới, lái thử, bảo dưỡng, sửa chữa chung, đồng sơn',
                    ]),
                    $text('Lái thử', 'Đặt lịch lái thử Lexus như thế nào?',
                        '<ol><li>Để lại họ tên, số điện thoại và dòng xe muốn lái trên trang <a href="/dang-ky-lai-thu">Đăng ký lái thử</a>, hoặc gọi 0989 345 989.</li>'
                        .'<li>Chuyên viên gọi lại trong giờ làm việc để chốt ngày giờ và chuẩn bị đúng phiên bản bạn quan tâm.</li>'
                        .'<li>Khi đến, mang theo giấy phép lái xe hạng B còn hiệu lực. Buổi lái thử thường kéo dài 30–60 phút, kèm tư vấn phiên bản và báo giá.</li></ol>'
                        .'<p>Nên đặt lịch trước khi đến vào cuối tuần để có sẵn xe lái thử.</p>'),
                    $faq([
                        'Lexus Thăng Long có mở cửa Chủ Nhật không?' => 'Có. Showroom mở cửa tất cả các ngày trong tuần, từ 8:00 đến 18:00.',
                        'Từ Mỹ Đình đến Lexus Thăng Long bao xa?' => 'Đại lý nằm trên trục Phạm Hùng, cách khu Mỹ Đình vài phút lái xe.',
                        'Lái thử Lexus có mất phí không?' => 'Không. Lái thử miễn phí; chỉ cần đặt lịch và mang giấy phép lái xe hạng B.',
                    ]),
                ],
            ],
        ];
    }

    /**
     * Trang tĩnh `trang-chu` — chỉ để chứa năm khối biên tập giữa trang chủ.
     *
     * KHÔNG có route riêng (/trang-chu vẫn mở được nhưng không ai link tới);
     * nó tồn tại để biên tập viên sửa được chữ/ảnh/link của các khối đó trong
     * admin. HomeController đọc `sections` của trang này.
     *
     * Ảnh dùng lại bộ ảnh đã nạp vào kho media cho các mục khác.
     */
    private function homePage(): void
    {
        // Ảnh thật của cơ sở Lexus Thăng Long thay cho ảnh minh hoạ của template.
        $img = fn (string $name) => $this->facility($name);

        $sections = [
            [
                'type'   => 'media',
                'layout' => 'bleed',
                'title'  => 'Lexus Thăng Long · Cầu Giấy, Hà Nội',
                'intro'  => "Không chỉ là nơi mua xe.\nMà là nơi được đón tiếp.",
                'body'   => 'Showroom và xưởng dịch vụ Lexus chính hãng tại ngã tư Phạm Hùng – Dương Đình Nghệ. '
                    .'Ghé thăm để xem tận nơi, lái thử và trò chuyện cùng chuyên viên.',
                'cta_label' => 'Bước vào thế giới Lexus',
                'cta_url'   => '/the-gioi-lexus',
                'cta2_label' => 'Đường đến showroom',
                'cta2_url'   => '/showroom',
                'items'  => [['image' => $img('showroom'), 'label' => 'Showroom Lexus Thăng Long tại Cầu Giấy, Hà Nội']],
            ],
            [
                'type'   => 'media',
                'layout' => 'shortcuts',
                'title'  => 'Hành trình sở hữu',
                'intro'  => 'Chiếc Lexus của bạn, từ đây.',
                'items'  => [
                    ['label' => 'Bảng giá Lexus',      'desc' => 'Tìm lựa chọn phù hợp',        'url' => '/bang-gia'],
                    ['label' => 'Giải pháp tài chính', 'desc' => 'Chủ động kế hoạch sở hữu',    'url' => '/tai-chinh'],
                    ['label' => 'Trải nghiệm lái thử', 'desc' => 'Cảm nhận bằng mọi giác quan', 'url' => '/dang-ky-lai-thu'],
                    ['label' => 'Tư vấn dành riêng',   'desc' => 'Kết nối cùng chuyên viên',    'url' => '/bao-gia'],
                ],
            ],
            [
                'type'   => 'media',
                'layout' => 'split',
                'title'  => 'Omotenashi · tận tâm từ chi tiết',
                'intro'  => "Được thấu hiểu.\nTrước cả khi bạn nói.",
                'body'   => 'Một không gian biết đón chào. Sự êm ái vừa đủ, những chất liệu chạm đến '
                    .'cảm xúc. Mọi chi tiết đều bắt đầu từ bạn.',
                'cta_label' => 'Khám phá Omotenashi',
                'cta_url'   => '/the-gioi-lexus#omotenashi',
                'items'  => [['image' => $img('phong-cho'), 'label' => 'Phòng chờ và tư vấn tại Lexus Thăng Long']],
            ],
            [
                'type'   => 'media',
                'layout' => 'split-alt',
                'title'  => 'Takumi · nghệ thuật chế tác',
                'intro'  => "Sự tinh tế nằm ở\nnhững điều rất nhỏ.",
                'body'   => 'Từ đường chỉ khâu trên xe đến từng công đoạn ở xưởng dịch vụ, sự chăm chút '
                    .'tạo nên một vẻ đẹp không cần phô trương.',
                'cta_label' => 'Câu chuyện nghệ nhân',
                'cta_url'   => '/the-gioi-lexus#takumi',
                'items'  => [['image' => $img('xuong-dich-vu-es'), 'label' => 'Kỹ thuật viên chăm sóc Lexus ES tại xưởng Lexus Thăng Long']],
            ],
            [
                'type'   => 'media',
                'layout' => 'cols-2',
                'title'  => 'Đặc quyền sở hữu',
                'intro'  => 'Thêm cảm hứng. Trọn an tâm.',
                'cta_label' => 'Khám phá đặc quyền',
                'cta_url'   => '/uu-dai',
                'items'  => [
                    [
                        'image'   => $img('don-tiep'),
                        'eyebrow' => 'Tư vấn sở hữu',
                        'label'   => 'Một kế hoạch dành riêng cho bạn.',
                        'desc'  => 'Khám phá các phương án tài chính minh họa và lựa chọn phù hợp với kế hoạch của bạn.',
                        'url'   => '/tai-chinh',
                    ],
                    [
                        'image'   => $img('xuong-dich-vu'),
                        'eyebrow' => 'Chăm sóc Lexus',
                        'label'   => 'An tâm trên mỗi chặng đường.',
                        'desc'  => 'Chăm sóc chu đáo từ buổi hẹn đầu tiên đến những hành trình về sau.',
                        'url'   => '/dich-vu',
                    ],
                ],
            ],
        ];

        Catalog::query('page')->updateOrCreate(
            ['slug' => 'trang-chu'],
            [
                'title'    => 'Trang chủ — khối nội dung',
                'status'   => 'published',
                'sections' => $sections,
                'seo'      => ['title' => 'Trang chủ — khối nội dung'],
            ],
        );
    }

    /**
     * Một banner hero mẫu, đúng nội dung bản thiết kế.
     *
     * Đây là khối duy nhất của trang chủ mà chủ website muốn tự đổi thường
     * xuyên, nên seed sẵn một bản ghi để vào admin là sửa được ngay thay vì
     * phải tự dựng từ đầu.
     *
     * Xoá bản ghi này đi thì hero lùi về ảnh và chữ của bản thiết kế —
     * trang chủ không vỡ.
     */
    private function banner(): void
    {
        // Ảnh thật: khu trưng bày ngoài trời của Lexus Thăng Long. Không có ảnh
        // dọc riêng cho điện thoại — trang chủ tự cắt giữa ảnh ngang.
        Catalog::query('banner')->updateOrCreate(
            ['title' => "Dấu ấn riêng.\nHành trình khác biệt."],
            [
                'eyebrow'      => 'LEXUS THĂNG LONG · CẦU GIẤY, HÀ NỘI',
                'subtitle'     => "Đại lý Lexus chính hãng tại ngã tư Phạm Hùng – Dương Đình Nghệ.\nĐón tiếp bạn mỗi ngày, 8:00 – 18:00.",
                'image'        => $this->facility('khu-trung-bay'),
                'image_mobile' => null,
                'cta_label'    => 'Khám phá các dòng xe',
                'cta_url'      => '/san-pham',
                'is_active'    => true,
                'sort'         => 1,
            ],
        );
    }
}
