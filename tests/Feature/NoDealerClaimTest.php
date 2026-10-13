<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use Tests\TestCase;

/**
 * 13/10/2026: mô tả trang (JSON-LD 48 trang, thẻ mô tả trang chủ) vẫn ghi
 * "chuyên viên tư vấn tại Lexus Thăng Long – đại lý Lexus chính hãng ở Hà
 * Nội"; khối chuyên viên ghi "đại lý 3S chính hãng"; hỏi đáp trang xe "Mua …
 * chính hãng ở đâu" — kế hoạch Ads bản 11 coi là dễ bị hiểu là trình bày sai.
 * Đổi sang "chuyên viên tư vấn tại showroom Lexus Thăng Long". Giữ các cụm mô
 * tả dịch vụ ("phụ tùng chính hãng", "bảo dưỡng chính hãng").
 */
class NoDealerClaimTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_10_13_100000_no_authorized_dealer_claim.php';

    public function test_doi_cai_dat_va_noi_dung_da_luu(): void
    {
        Setting::put('site_description', 'Thu Hà, chuyên viên tư vấn tại Lexus Thăng Long – đại lý Lexus chính hãng ở Hà Nội. Bảng giá 6 dòng xe 2026.');
        Setting::put('advisor_dealer_note', 'Lexus Thăng Long – đại lý 3S chính hãng: bán xe, bảo hành, dịch vụ, phụ tùng', 'home');

        $product = Product::create(['name' => 'Lexus ES', 'slug' => 'es', 'status' => 'published', 'sections' => [
            ['type' => 'faq', 'rows' => [['label' => 'Mua Lexus ES chính hãng ở đâu tại Hà Nội?',
                'value' => 'Lexus Thăng Long là đại lý Lexus chính hãng tại ngã tư Phạm Hùng. Showroom trưng bày, có xe lái thử và xưởng dịch vụ chính hãng ngay tại đại lý.']]],
        ]]);
        $home = Page::create(['slug' => 'trang-chu', 'title' => 'Trang chủ', 'status' => 'published', 'sections' => [
            ['type' => 'media', 'body' => 'Showroom và xưởng dịch vụ Lexus chính hãng tại ngã tư Phạm Hùng. Phụ tùng chính hãng.'],
        ]]);
        $post = Post::create(['title' => 'Showroom', 'slug' => 'showroom', 'status' => 'published', 'published_at' => now(),
            'excerpt' => 'Có đủ 6 dòng xe Lexus đang bán, xe lái thử và xưởng dịch vụ chính hãng.']);

        $migration = require database_path(self::MIGRATION);
        $migration->up();
        $migration->up();

        $this->assertSame('Thu Hà, chuyên viên tư vấn tại showroom Lexus Thăng Long, Hà Nội. Bảng giá 6 dòng xe 2026.', Setting::get('site_description'));
        $this->assertSame('Showroom Lexus Thăng Long: bán xe, bảo hành, dịch vụ, phụ tùng tại một nơi', Setting::get('advisor_dealer_note'));
        $row = $product->fresh()->sections[0]['rows'][0];
        $this->assertSame('Mua Lexus ES ở đâu tại Hà Nội?', $row['label']);
        $this->assertSame('Showroom Lexus Thăng Long ở ngã tư Phạm Hùng. Showroom trưng bày, có xe lái thử và xưởng dịch vụ ngay tại showroom.', $row['value']);
        $this->assertSame('Showroom và xưởng dịch vụ Lexus Thăng Long tại ngã tư Phạm Hùng. Phụ tùng chính hãng.', $home->fresh()->sections[0]['body']);
        $this->assertSame('Có đủ 6 dòng xe Lexus đang bán, xe lái thử và xưởng dịch vụ.', $post->fresh()->excerpt);
    }

    public function test_code_va_seeder_khong_con_cau_xung_dai_ly(): void
    {
        foreach ([
            'app/Http/Controllers/Frontend/VariantController.php', 'app/Services/GeminiArticleWriter.php',
            'app/Services/GeminiContentPlanner.php', 'config/catalog.php',
            'database/seeders/LexusSiteSeeder.php', 'database/seeders/Brands/LexusSeeder.php',
        ] as $file) {
            $text = file_get_contents(base_path($file));
            foreach (['đại lý Lexus chính hãng', 'đại lý 3S chính hãng', 'chính hãng ở đâu', 'xưởng dịch vụ chính hãng'] as $phrase) {
                $this->assertStringNotContainsString($phrase, $text, "{$file}: {$phrase}");
            }
        }
    }
}
