<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use App\Support\ArticleContext;
use Tests\TestCase;

/**
 * Nội dung nói bằng lời chuyên viên, không bằng lời đại lý.
 *
 * Rà toàn site 04/10/2026 (sau 2 lần Google Ads từ chối kháng nghị): nhiều
 * câu xưng "chúng tôi" như đại lý ("showroom chính hãng của chúng tôi") và gọi
 * số di động của chị Thu Hà là "Hotline" — người xét duyệt dễ hiểu đó là tổng
 * đài đại lý. Sửa bài cũ bằng migration, chặn bài mới ở hồ sơ gửi Gemini.
 */
class AdvisorVoiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://lexusthanglong.test']);
        Setting::put('site_name', 'Lexus Thăng Long');
        Setting::put('hotline', '0989345989');
        Setting::put('address', 'Ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy, Hà Nội');
        Setting::put('advisor_name', 'Thu Hà', 'home');
        Setting::put('advisor_phone', '0989345989', 'home');
    }

    private function migrate(): void
    {
        (require database_path('migrations/2026_10_04_110000_advisor_voice_in_content.php'))->up();
    }

    public function test_bai_viet_cu_doi_sang_loi_chuyen_vien(): void
    {
        $post = Post::create(['title' => 'Giá lăn bánh', 'slug' => 'gia-lan-banh', 'status' => 'published', 'published_at' => now(),
            'excerpt' => 'Gọi hotline 0989 345 989 để nhận báo giá.',
            'sections' => [
                ['type' => 'text', 'body' => '<p>Kính mời quý khách hàng ghé thăm showroom chính hãng của chúng tôi.</p>'
                    .'<p>Gửi yêu cầu tại <a href="/bao-gia">trang nhận báo giá lăn bánh</a> của chúng tôi.</p>'
                    .'<p>Thông tin liên hệ đại lý chính hãng:</p><ul><li>Hotline tư vấn: 0989 345 989 (Chuyên viên Thu Hà)</li></ul>'
                    .'<p>Để lại số để nhận phản hồi nhanh chóng từ chúng tôi.</p>'
                    .'<p>Liên hệ ngay với Thu Hà qua hotline hoặc ghé showroom.</p>'],
                ['type' => 'faq', 'rows' => [
                    ['label' => 'Mua xe Lexus ở đâu tại Hà Nội?', 'value' => 'Lexus Thăng Long, Cầu Giấy. Hotline 0989 345 989 (Thu Hà).'],
                    ['label' => 'Hotline', 'value' => '0989 345 989'],
                ]],
            ],
        ]);

        $this->migrate();
        $this->migrate(); // chạy lại không đổi thêm

        $post->refresh();
        $body = $post->sections[0]['body'];
        $this->assertSame('Gọi số 0989 345 989 để nhận báo giá.', $post->excerpt);
        $this->assertStringContainsString('hẹn chuyên viên Thu Hà để ghé xem xe tại showroom Lexus Thăng Long', $body);
        $this->assertStringContainsString('trang nhận báo giá lăn bánh</a> trên website', $body);
        $this->assertStringContainsString('Liên hệ chuyên viên Thu Hà tại Lexus Thăng Long:', $body);
        $this->assertStringContainsString('Điện thoại chuyên viên Thu Hà: 0989 345 989', $body);
        $this->assertStringContainsString('được Thu Hà phản hồi nhanh chóng', $body);
        $this->assertStringContainsString('qua điện thoại, Zalo hoặc', $body);
        $this->assertStringNotContainsString('chúng tôi', $body);
        $this->assertStringNotContainsStringIgnoringCase('hotline', $body);

        $rows = $post->sections[1]['rows'];
        $this->assertSame('Lexus Thăng Long, Cầu Giấy. Điện thoại 0989 345 989 (chuyên viên Thu Hà).', $rows[0]['value']);
        $this->assertSame('Điện thoại chuyên viên', $rows[1]['label']);
    }

    public function test_trang_tinh_va_trang_xe_cung_duoc_sua(): void
    {
        $page = Page::create(['slug' => 'the-gioi-lexus', 'title' => 'Thế giới Lexus', 'status' => 'published',
            'sections' => [['type' => 'text', 'body' => 'Những giá trị làm nên Lexus — và cách chúng tôi mang chúng đến với bạn tại Hà Nội.']]]);
        $product = Product::create(['name' => 'Lexus ES', 'slug' => 'es', 'status' => 'published',
            'sections' => [['type' => 'faq', 'rows' => [['label' => 'Mua ở đâu?', 'value' => 'Đặt lịch trên website hoặc gọi hotline 0989 345 989.']]]]]);

        $this->migrate();

        $this->assertStringContainsString('cách chuyên viên Thu Hà mang chúng đến với bạn', $page->fresh()->sections[0]['body']);
        $this->assertSame('Đặt lịch trên website hoặc gọi số 0989 345 989.', $product->fresh()->sections[0]['rows'][0]['value']);
    }

    public function test_hoi_dap_trang_phien_ban_khong_goi_so_chuyen_vien_la_hotline(): void
    {
        $es = Product::create(['name' => 'Lexus ES', 'slug' => 'es', 'status' => 'published', 'published_at' => now()]);
        $es->variants()->create(['name' => 'ES 350h Premium', 'price' => 2_360_000_000, 'sort' => 1, 'is_default' => true]);

        $html = $this->get('/san-pham/es/es-350h-premium')->assertOk()->getContent();

        $this->assertStringNotContainsString('Hotline 0989', $html);
        $this->assertStringContainsString('Gọi chuyên viên Thu Hà 0989 345 989 để hẹn lái thử và nhận báo giá.', $html);
    }

    public function test_menu_di_dong_va_llms_txt_ghi_so_cua_chuyen_vien(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('>Gọi Thu Hà 0989 345 989</a>', $html);
        $this->assertStringNotContainsString('>Hotline 0989', $html);

        $txt = $this->get('/llms.txt')->assertOk()->getContent();
        $this->assertStringStartsWith('# Thu Hà – Tư vấn Lexus Thăng Long', $txt);
        $this->assertStringContainsString('Điện thoại chuyên viên Thu Hà: 0989 345 989', $txt);
        $this->assertStringNotContainsString('Hotline', $txt);
    }

    public function test_ho_so_gui_gemini_dan_viet_bang_loi_chuyen_vien(): void
    {
        $context = ArticleContext::build('Giá Lexus ES');

        $this->assertStringContainsString('Điện thoại chuyên viên Thu Hà: 0989 345 989', $context);
        $this->assertStringContainsString('website tư vấn cá nhân của chuyên viên', $context);
        $this->assertStringNotContainsString('Hotline', $context);
    }
}
