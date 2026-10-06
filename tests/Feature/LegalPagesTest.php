<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use Tests\TestCase;

/**
 * Chính sách quyền riêng tư và điều khoản sử dụng nói đúng tình trạng hiện
 * tại (06/10/2026): người chịu trách nhiệm là chị Nguyễn Thị Thu Hà, không
 * xưng "chúng tôi" như đại lý, không nói site đang chạy Google Ads (tài khoản
 * đã huỷ, chỉ còn GA4), ảnh xe là ảnh xe bán tại Việt Nam, website không nhận
 * thanh toán. Trang cũ trên máy chủ đổi bằng migration khi còn đúng bản cũ.
 */
class LegalPagesTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_10_06_100000_rewrite_legal_pages.php';

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put('site_name', 'Lexus Thăng Long');
    }

    private function oldPage(string $slug, string $date): Page
    {
        return Page::create(['slug' => $slug, 'title' => $slug, 'status' => 'published',
            'seo' => ['title' => 'Cũ | Lexus Thăng Long'],
            'sections' => [['type' => 'text', 'title' => 'Tóm tắt',
                'body' => '<p>Chúng tôi lưu dữ liệu.</p><p>Cập nhật lần cuối: '.$date.'.</p>']]]);
    }

    public function test_trang_cu_doi_sang_ban_moi_va_chay_lai_khong_doi_them(): void
    {
        $privacy = $this->oldPage('quyen-rieng-tu', '28/09/2026');
        $terms = $this->oldPage('dieu-khoan', '24/09/2026');

        $migration = require database_path(self::MIGRATION);
        $migration->up();
        $after = [$privacy->fresh()->sections, $terms->fresh()->sections];
        $migration->up();
        $this->assertSame($after, [$privacy->fresh()->sections, $terms->fresh()->sections]);

        $html = $this->get('/quyen-rieng-tu')->assertOk()->getContent();
        $this->assertStringContainsString('Nguyễn Thị Thu Hà', $html);
        $this->assertStringContainsString('Cập nhật lần cuối: 06/10/2026', $html);
        $this->assertStringContainsString('https://myadcenter.google.com/', $html);
        $this->assertStringNotContainsString('Website quảng cáo trên Google', $html);
        $this->assertSame('Chính sách quyền riêng tư | Thu Hà tư vấn Lexus', $privacy->fresh()->seo['title']);

        $html = $this->get('/dieu-khoan')->assertOk()->getContent();
        $this->assertStringContainsString('không nhận thanh toán', $html);
        $this->assertStringContainsString('xe bán tại Việt Nam', $html);
        $this->assertStringNotContainsString('thị trường khác', $html);

        foreach ([$privacy, $terms] as $page) {
            $text = json_encode($page->fresh()->only('sections', 'seo'), JSON_UNESCAPED_UNICODE);
            $this->assertDoesNotMatchRegularExpression('/chúng tôi|hotline/iu', $text);
        }
    }

    public function test_trang_da_sua_tay_thi_giu(): void
    {
        $page = Page::create(['slug' => 'dieu-khoan', 'title' => 'Điều khoản', 'status' => 'published',
            'sections' => [['type' => 'text', 'body' => '<p>Bản tự viết.</p>']]]);

        (require database_path(self::MIGRATION))->up();

        $this->assertSame('<p>Bản tự viết.</p>', $page->fresh()->sections[0]['body']);
    }

    public function test_seeder_dung_noi_dung_moi(): void
    {
        $seeder = file_get_contents(database_path('seeders/LexusSiteSeeder.php'));

        $this->assertStringContainsString("database_path('content/legal-pages.php')", $seeder);
        $this->assertStringNotContainsString('Chính sách quyền riêng tư | Lexus Thăng Long', $seeder);
    }
}
