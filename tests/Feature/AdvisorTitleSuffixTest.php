<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use App\Support\SeoText;
use Tests\TestCase;

/**
 * Tiêu đề trang không kết thúc bằng "| Lexus Thăng Long" (đọc như website
 * của đại lý — lý do Google Ads tạm ngưng) mà bằng "| Thu Hà tư vấn Lexus".
 * Site không có chuyên viên thì vẫn dùng tên site.
 */
class AdvisorTitleSuffixTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_10_06_110000_advisor_title_suffix.php';

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put('site_name', 'Lexus Thăng Long');
        Setting::put('advisor_name', 'Thu Hà', 'home');
    }

    public function test_duoi_tieu_de_la_ten_chuyen_vien(): void
    {
        $this->assertSame(' | Thu Hà tư vấn Lexus', SeoText::titleSuffix());

        Page::create(['slug' => 'faq', 'title' => 'Hỏi đáp', 'status' => 'published']);
        foreach (['/san-pham', '/tin-tuc', '/faq'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            preg_match('#<title>(.*?)</title>#s', $html, $m);
            $this->assertStringEndsWith('| Thu Hà tư vấn Lexus', html_entity_decode($m[1]), $url);
        }
    }

    public function test_khong_co_chuyen_vien_thi_dung_ten_site(): void
    {
        Setting::put('advisor_name', '', 'home');

        $this->assertSame(' | Lexus Thăng Long', SeoText::titleSuffix());
    }

    public function test_migration_doi_tieu_de_da_luu(): void
    {
        $bangGia = Page::create(['slug' => 'bang-gia', 'title' => 'Bảng giá', 'status' => 'published',
            'seo' => ['title' => 'Bảng giá xe Lexus 2026 mới nhất tại Hà Nội | Lexus Thăng Long', 'eyebrow' => 'GIÁ']]);
        $showroom = Page::create(['slug' => 'showroom', 'title' => 'Showroom', 'status' => 'published',
            'seo' => ['title' => 'Showroom Lexus Thăng Long — Phạm Hùng, Cầu Giấy, Hà Nội']]);
        $tuViet = Page::create(['slug' => 'faq', 'title' => 'FAQ', 'status' => 'published',
            'seo' => ['title' => 'Tiêu đề tự viết']]);
        $product = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published',
            'seo' => ['title' => 'Lexus RX 2026: giá từ 3,35 tỷ, thông số, màu | Lexus Thăng Long']]);
        $post = Post::create(['title' => 'Bài', 'slug' => 'bai', 'status' => 'published', 'published_at' => now(),
            'seo' => ['title' => 'Bài viết | Lexus Thăng Long']]);

        $migration = require database_path(self::MIGRATION);
        $migration->up();
        $migration->up();

        $this->assertSame('Bảng giá xe Lexus 2026 mới nhất tại Hà Nội | Thu Hà tư vấn Lexus', $bangGia->fresh()->seo['title']);
        $this->assertSame('GIÁ', $bangGia->fresh()->seo['eyebrow']);
        $this->assertSame('Xem xe tại showroom Lexus Thăng Long, Cầu Giấy | Thu Hà tư vấn Lexus', $showroom->fresh()->seo['title']);
        $this->assertSame('Tiêu đề tự viết', $tuViet->fresh()->seo['title']);
        $this->assertSame('Lexus RX 2026: giá từ 3,35 tỷ, thông số, màu | Thu Hà tư vấn Lexus', $product->fresh()->seo['title']);
        $this->assertSame('Bài viết | Thu Hà tư vấn Lexus', $post->fresh()->seo['title']);
    }

    public function test_seeder_khong_con_duoi_ten_dai_ly(): void
    {
        foreach (['seeders/LexusSiteSeeder.php', 'seeders/Brands/LexusSeeder.php', 'content/legal-pages.php'] as $file) {
            $this->assertStringNotContainsString('| Lexus Thăng Long', file_get_contents(database_path($file)), $file);
        }
    }
}
