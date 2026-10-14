<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use Tests\TestCase;

/**
 * Giá ES đã sửa trong admin (07/10/2026): 350h Premium 2,34 · Luxury 2,60 ·
 * ES 500e 2,94 tỷ — người dùng xác nhận 14/10. Chữ viết sẵn (giá từ, hỏi đáp,
 * SEO, bài viết, mô tả trang) vẫn ghi giá cũ 2,36 · 2,58 · 2,98 và lăn bánh
 * cũ 2,66 · 2,90 · 2,99, nên trang ES tự mâu thuẫn với bảng giá.
 */
class EsPriceSyncTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_10_14_110000_es_prices_in_content.php';

    private function migrate(): void
    {
        $migration = require database_path(self::MIGRATION);
        $migration->up();
        $migration->up();
    }

    public function test_doi_gia_es_cu_trong_trang_xe_bai_viet_va_cai_dat(): void
    {
        Setting::put('site_description', 'Bảng giá 6 dòng xe 2026 từ 2,36 tỷ, lái thử.');

        $es = Product::create([
            'name' => 'Lexus ES', 'slug' => 'es', 'status' => 'published', 'published_at' => now(),
            'price_from' => 2_360_000_000,
            'highlights' => [['value' => '2,36', 'unit' => 'tỷ', 'label' => 'Giá từ · thế hệ thứ 8']],
            'sections' => [['type' => 'faq', 'title' => 'Hỏi đáp', 'rows' => [[
                'label' => 'Giá xe Lexus ES 2026 bao nhiêu?',
                'value' => 'ES 350h Premium 2,36 tỷ đồng, ES 350h Luxury 2,58 tỷ đồng và ES 500e 2,98 tỷ đồng. '
                    .'Lăn bánh tại Hà Nội tạm tính khoảng 2,66 tỷ, 2,90 tỷ và 2,99 tỷ đồng.',
            ]]]],
            'seo' => ['title' => 'Lexus ES 350h 2026: giá từ 2,36 tỷ, thế hệ mới'],
        ]);

        $post = Post::create(['title' => 'Lexus ES 350h 2026: giá 2,36 – 2,58 tỷ', 'slug' => 'es', 'status' => 'published',
            'published_at' => now(), 'excerpt' => 'Lăn bánh Hà Nội khoảng 2,66 – 2,90 tỷ.',
            'sections' => [['type' => 'text', 'body' => '<p>Niêm yết 2.360.000.000 đ · trước bạ 12%: 283.200.000 đ · '
                .'biển số 14.000.000 đ → khoảng 2.657.200.000 đ. Luxury 2.580.000.000 đ, 309.600.000 đ, 2.903.600.000 đ. '
                .'ES 500e 2.980.000.000 đ. Tạm tính khoảng 2.659.240.700 đ.</p>']]]);

        $page = Page::create(['slug' => 'bang-gia', 'title' => 'Bảng giá xe Lexus', 'status' => 'published',
            'seo' => ['description' => 'Bảng giá xe Lexus 2026: ES 350h từ 2,36 tỷ, RX từ 3,35 tỷ.']]);

        $this->migrate();

        $es->refresh();
        $this->assertSame(2_340_000_000, (int) $es->price_from);
        $this->assertSame('2,34', $es->highlights[0]['value']);
        $this->assertSame('ES 350h Premium 2,34 tỷ đồng, ES 350h Luxury 2,60 tỷ đồng và ES 500e 2,94 tỷ đồng. '
            .'Lăn bánh tại Hà Nội tạm tính khoảng 2,63 tỷ, 2,93 tỷ và 2,95 tỷ đồng.', $es->sections[0]['rows'][0]['value']);
        $this->assertSame('Lexus ES 350h 2026: giá từ 2,34 tỷ, thế hệ mới', $es->seo['title']);

        $post->refresh();
        $this->assertSame('Lexus ES 350h 2026: giá 2,34 – 2,60 tỷ', $post->title);
        $this->assertSame('Lăn bánh Hà Nội khoảng 2,63 – 2,93 tỷ.', $post->excerpt);
        $this->assertSame('<p>Niêm yết 2.340.000.000 đ · trước bạ 12%: 280.800.000 đ · '
            .'biển số 14.000.000 đ → khoảng 2.634.800.000 đ. Luxury 2.600.000.000 đ, 312.000.000 đ, 2.926.000.000 đ. '
            .'ES 500e 2.940.000.000 đ. Tạm tính khoảng 2.636.840.700 đ.</p>', $post->sections[0]['body']);

        $this->assertSame('Bảng giá xe Lexus 2026: ES 350h từ 2,34 tỷ, RX từ 3,35 tỷ.', $page->fresh()->seo['description']);
        $this->assertSame('Bảng giá 6 dòng xe 2026 từ 2,34 tỷ, lái thử.', Setting::get('site_description'));
    }

    public function test_so_khac_khong_bi_doi(): void
    {
        $post = Post::create(['title' => 'Lãi suất 2,99%/năm, gói 2,90 triệu', 'slug' => 'lai', 'status' => 'published',
            'published_at' => now(), 'excerpt' => 'Phí 2,36 triệu đồng.']);

        $this->migrate();

        $this->assertSame('Lãi suất 2,99%/năm, gói 2,90 triệu', $post->fresh()->title);
        $this->assertSame('Phí 2,36 triệu đồng.', $post->fresh()->excerpt);
    }

    public function test_gia_tu_da_sua_tay_khac_thi_giu(): void
    {
        $es = Product::create(['name' => 'Lexus ES', 'slug' => 'es', 'status' => 'published', 'published_at' => now(),
            'price_from' => 2_300_000_000]);

        $this->migrate();

        $this->assertSame(2_300_000_000, (int) $es->fresh()->price_from);
    }

    public function test_seeder_khong_con_gia_es_cu(): void
    {
        foreach (['seeders/LexusSiteSeeder.php', 'seeders/Brands/LexusSeeder.php'] as $file) {
            $code = file_get_contents(database_path($file));
            // Bỏ phần chú thích lịch sử giá ở đầu file (không hiện trên site).
            $code = preg_replace('#/\*.*?\*/#s', '', $code);

            foreach (['2,36', '2,58', '2,98', '2,66 ', '2,90 tỷ', '2,99 tỷ', '2.360.000.000', '2.580.000.000', '2.980.000.000',
                '2_360_000_000', '2_580_000_000', '2_980_000_000'] as $old) {
                $this->assertStringNotContainsString($old, $code, "$file còn $old");
            }
        }
    }
}
