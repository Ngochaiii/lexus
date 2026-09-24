<?php

namespace Database\Seeders\Brands;

/**
 * MẪU — copy file này khi thêm hãng mới.
 *
 *   cp database/seeders/Brands/MauSeeder.php database/seeders/Brands/ToyotaSeeder.php
 *   (đổi tên class thành ToyotaSeeder, sửa brand(), categories(), products())
 *
 * File này chạy được thật (một xe giả tên "Mẫu M1") để bạn thử ngay, nhưng
 * KHÔNG nằm trong DatabaseSeeder — chỉ chạy khi gọi đích danh:
 *
 *   php artisan db:seed --class="Database\Seeders\Brands\MauSeeder"
 *
 * Mọi khoá dữ liệu đều tuỳ chọn trừ `slug` và `name`. Bỏ khoá nào thì phần
 * đó không sinh ra — đúng quy tắc "ô trống thì không render".
 */
class MauSeeder extends BrandSeeder
{
    /** Tên hãng. Cũng là tên thư mục ảnh: {MEDIA_ROOT}/catalog/{slug-hãng}/ */
    protected function brand(): string
    {
        return 'Mẫu';
    }

    /** Danh mục của hãng: slug => tên hiển thị. Xe trỏ vào bằng khoá 'category'. */
    protected function categories(): array
    {
        return [
            'suv-co-b' => 'SUV cỡ B',
        ];
    }

    /** Mỗi phần tử là một xe. Thứ tự trong mảng = thứ tự hiện trên trang. */
    protected function products(): array
    {
        return [
            [
                // ── Bắt buộc ──────────────────────────────────────────────
                'slug' => 'mau-m1',            // duy nhất; đổi slug sau khi publish sẽ tự tạo redirect 301
                'name' => 'Mẫu M1',

                // ── Thông tin cơ bản ──────────────────────────────────────
                'tagline'    => 'Một dòng mô tả ngắn, hiện dưới tên xe',
                'category'   => 'suv-co-b',     // khoá trong categories() ở trên
                'price_from' => 599_000_000,    // giá "từ", hiện ở thẻ và đầu trang chi tiết
                'hero'       => true,           // sinh ảnh hero placeholder; bỏ đi thì trang không có ảnh lớn

                // ── Chỉ số nổi bật: 3–4 con số đập vào mắt ────────────────
                'highlights' => [
                    ['value' => '180', 'unit' => 'mã lực', 'label' => 'Công suất tối đa'],
                    ['value' => '5',   'unit' => 'chỗ',    'label' => 'Số chỗ ngồi'],
                ],

                // ── Mục ẢNH ───────────────────────────────────────────────
                // 'Tên mục' => ['layout' => slider|cols-1|cols-2|cols-3, 'items' => [nhãn ảnh…]]
                // Mỗi nhãn sinh một ảnh placeholder; upload ảnh thật đè lên trong admin.
                'media' => [
                    'Thư viện' => [
                        'layout' => 'slider',
                        'items'  => ['Góc trước', 'Góc sau', 'Nội thất'],
                    ],
                    'Ngoại thất' => [
                        'intro'  => 'Đoạn mở đầu của mục. Bỏ trống thì không render.',
                        'layout' => 'cols-2',
                        'items'  => ['Đèn trước', 'Mâm xe'],
                    ],
                ],

                // ── Mục VĂN BẢN: 'Tên mục' => nội dung ────────────────────
                'story' => [
                    'Vận hành' => "Xuống dòng trong chuỗi này được giữ nguyên khi render.\n"
                        .'Viết như viết bài, không cần thẻ HTML.',
                ],

                // ── Mục VIDEO: link YouTube/Vimeo hoặc file .mp4 ──────────
                'video'       => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                'video_title' => 'Phim giới thiệu',   // tuỳ chọn

                // ── Mục BẢNG: 'Tên mục' => ['nhãn' => 'giá trị'] ──────────
                'tables' => [
                    'Thời gian sạc' => [
                        'Sạc nhanh DC (10–70%)' => 'khoảng 30 phút',
                    ],
                ],

                // ── Form cuối trang: true (mặc định) | false ──────────────
                'form' => true,

                // ── Bảng màu: 'Tên màu' => mã hex ─────────────────────────
                'colors' => [
                    'Trắng' => '#F2F2F2',
                    'Đen'   => '#1A1A1A',
                ],

                // ── Phiên bản: cái đầu tiên tự thành bản mặc định ─────────
                'variants' => [
                    ['name' => 'Bản tiêu chuẩn', 'price' => 599_000_000, 'note' => 'Ghi chú tuỳ chọn'],
                    ['name' => 'Bản cao cấp', 'price' => 699_000_000, 'price_original' => 729_000_000],
                    // Xe điện thêm 'battery_kwh' => 59.6, 'range_km' => 480 vào từng
                    // biến thể để bật bộ so sánh chi phí ở trang chi tiết
                    // (config('catalog.features.fuel_calc')). Xe xăng dầu bỏ qua.
                ],

                // ── Thông số: 'Nhóm' => ['nhãn' => 'giá trị'] ─────────────
                // Nhóm tự đặt tên: xe xăng gõ "Động cơ", xe điện gõ "Pin & sạc".
                'specs' => [
                    'Động cơ & hiệu năng' => [
                        'Công suất tối đa' => '180 mã lực',
                        'Dẫn động'         => 'Cầu trước',
                    ],
                    'Kích thước' => [
                        'Dài × Rộng × Cao' => '4.300 × 1.800 × 1.600 mm',
                        'Số chỗ ngồi'      => '5',
                    ],
                ],

                // ── SEO: bỏ qua thì lấy tên xe làm title ──────────────────
                'seo' => [
                    'title'       => 'Mẫu M1 — giá và thông số kỹ thuật',
                    'description' => 'Câu mô tả hiện trên Google, khoảng 150 ký tự.',
                ],
            ],
        ];
    }
}
