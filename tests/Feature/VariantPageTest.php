<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use Tests\TestCase;

/** Trang riêng từng phiên bản /san-pham/{xe}/{phien-ban}. */
class VariantPageTest extends TestCase
{
    private Product $rx;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://lexus.test']);
        Setting::put('site_name', 'Lexus Thăng Long');
        Setting::put('hotline', '0989345989');

        $this->rx = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now(),
            'specs' => [
                ['group' => 'Động cơ — RX 500h F SPORT Performance', 'rows' => [['label' => 'Công suất', 'value' => '371 HP']]],
                ['group' => 'Kích thước', 'rows' => [['label' => 'Dài', 'value' => '4.890 mm']]],
            ]]);
        $this->rx->variants()->createMany([
            ['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'note' => 'Hybrid 2.5L', 'sort' => 1],
            ['name' => 'RX 500h F SPORT Performance', 'price' => 4_940_000_000, 'note' => 'Hybrid tăng áp', 'sort' => 2],
        ]);
    }

    public function test_slug_tu_sinh_tu_ten(): void
    {
        $this->assertSame(['rx-350h-premium', 'rx-500h-f-sport-performance'],
            $this->rx->variants()->orderBy('sort')->pluck('slug')->all());
    }

    public function test_trang_phien_ban_co_gia_lan_banh_so_sanh_faq_va_schema(): void
    {
        $html = $this->get('/san-pham/rx/rx-350h-premium')->assertOk()->getContent();

        $this->assertStringContainsString('<h1>Lexus RX 350h Premium 2026</h1>', $html);
        $this->assertStringContainsString('3.350.000.000 đ', $html);
        $this->assertStringContainsString('3.766.000.000 đ', $html, 'lăn bánh = 3,35 tỷ + 12% + 14 triệu');
        $this->assertStringContainsString('href="/san-pham/rx/rx-500h-f-sport-performance"', $html, 'link sang bản cùng dòng');
        $this->assertStringContainsString('4.890 mm', $html);
        $this->assertStringNotContainsString('371 HP', $html, 'thông số của bản khác không hiện');
        $this->assertStringContainsString('<link rel="canonical" href="https://lexus.test/san-pham/rx/rx-350h-premium">', $html);
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertMatchesRegularExpression('/"@type":"Offer","price":"3350000000.00"/', $html);
        $this->assertStringContainsString('data-variant-name="RX 350h Premium"', $html, 'nút báo giá gửi kèm phiên bản');
    }

    // Search Console (Đoạn trích sản phẩm) coi mỗi {"@id": "…#product"} trỏ tới
    // nút không có trên trang là một Product thứ hai → báo "thiếu name" và
    // "phải có offers" với Tên mục "Không áp dụng". Mọi Product trên trang phải
    // tự đủ tên + giá, và không tham chiếu tới #product của trang khác.
    public function test_schema_phien_ban_khong_sinh_product_thieu_ten_hay_gia(): void
    {
        $html = $this->get('/san-pham/rx/rx-350h-premium')->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $defined = $referenced = [];
        $walk = function ($node) use (&$walk, &$defined, &$referenced) {
            if (! is_array($node)) {
                return;
            }
            if (isset($node['@id']) && str_ends_with($node['@id'], '#product')) {
                if (isset($node['@type'])) {
                    $defined[] = $node['@id'];
                    $this->assertNotEmpty($node['name'] ?? null, 'Product thiếu name');
                    $this->assertNotEmpty($node['offers'] ?? null, 'Product thiếu offers');
                } else {
                    $referenced[] = $node['@id'];
                }
            }
            array_map($walk, $node);
        };
        foreach ($m[1] as $json) {
            $walk(json_decode(html_entity_decode($json), true));
        }

        $this->assertSame(['https://lexus.test/san-pham/rx/rx-350h-premium#product'], $defined);
        $this->assertSame([], array_diff($referenced, $defined), 'tham chiếu tới Product không có trên trang');
    }

    // Quảng cáo ghi "10 Năm Kinh Nghiệm Bán Ô Tô", "Phương Án Trả Góp": trang đích
    // phải có đúng thông tin đó (điểm liên quan trang đích, tuyên bố có căn cứ).
    public function test_trang_dich_quang_cao_co_khoi_chuyen_vien_kinh_nghiem_va_tra_gop(): void
    {
        Setting::put('advisor_name', 'Thu Hà');
        Setting::put('advisor_experience', '10 năm kinh nghiệm bán ô tô');
        // Quảng cáo ghi "Đại Lý 3S Chính Hãng" → trang đích phải nói đúng điều đó.
        Setting::put('advisor_dealer_note', 'Lexus Thăng Long – đại lý 3S chính hãng: bán xe, bảo hành, dịch vụ, phụ tùng');
        \App\Models\Page::create(['slug' => 'bang-gia', 'title' => 'Bảng giá', 'status' => 'published', 'sections' => []]);
        \App\Models\Page::create(['slug' => 'lien-he', 'title' => 'Liên hệ', 'status' => 'published', 'sections' => []]);

        foreach (['/san-pham/rx', '/san-pham/rx/rx-350h-premium', '/bang-gia', '/lien-he'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('class="advisor-card-band"', $html, $url);
            $this->assertStringContainsString('10 năm kinh nghiệm bán ô tô', $html, $url);
            $this->assertStringContainsString('đại lý 3S chính hãng: bán xe, bảo hành, dịch vụ, phụ tùng', $html, $url);
            $this->assertStringContainsString('trả góp', $html, $url);
            $this->assertStringContainsString('href="tel:0989345989"', $html, $url);
        }

        $html = $this->get('/san-pham/rx/rx-350h-premium')->getContent();
        $this->assertStringContainsString('Báo giá lăn bánh Lexus RX 350h Premium', $html);
        $this->assertStringContainsString('data-variant-name="RX 350h Premium"', $html);
        $this->assertStringContainsString('"description":"10 năm kinh nghiệm bán ô tô"', $html, 'Person trong JSON-LD');
    }

    public function test_trang_phien_ban_co_du_cac_muc_anh_cua_dong_xe(): void
    {
        $this->rx->update(['sections' => [
            ['type' => 'text', 'title' => 'Nội thất', 'intro' => 'Khoang lái Tazuna', 'body' => 'Ghế da bán aniline.'],
            ['type' => 'faq', 'title' => 'Hỏi đáp', 'rows' => [['label' => 'Câu hỏi của dòng xe?', 'value' => 'Trả lời.']]],
        ]]);

        $html = $this->get('/san-pham/rx/rx-350h-premium')->assertOk()->getContent();

        $this->assertStringContainsString('Khoang lái Tazuna', $html, 'khách xem phiên bản vẫn thấy đủ nội thất, ngoại thất');
        $this->assertStringNotContainsString('Câu hỏi của dòng xe?', $html, 'dùng hỏi đáp riêng của phiên bản');
        $this->assertSame(1, substr_count($html, '"@type":"FAQPage"'));
    }

    public function test_sai_dong_xe_hoac_xe_an_thi_404(): void
    {
        $es = Product::create(['name' => 'Lexus ES', 'slug' => 'es', 'status' => 'published', 'published_at' => now()]);
        $this->get('/san-pham/es/rx-350h-premium')->assertNotFound();

        $this->rx->update(['status' => 'draft']);
        $this->get('/san-pham/rx/rx-350h-premium')->assertNotFound();
    }

    public function test_sitemap_llms_va_trang_dong_xe_tro_toi_trang_phien_ban(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>https://lexus.test/san-pham/rx/rx-350h-premium</loc>', false);
        $this->get('/llms.txt')->assertOk()->assertSee('(https://lexus.test/san-pham/rx/rx-350h-premium)', false);
        $this->get('/san-pham/rx')->assertOk()->assertSee('href="/san-pham/rx/rx-350h-premium"', false);
    }

    public function test_moi_trang_co_hotline_va_nut_goi_zalo(): void
    {
        Setting::put('zalo', '0989345989');

        $this->get('/san-pham')->assertOk()
            ->assertSee('class="header-phone" href="tel:0989345989"', false)
            ->assertSee('class="contact-float"', false)
            ->assertSee('class="sales-bar"', false);
    }

    // Mục tiêu quảng cáo là cuộc gọi tới chuyên viên: trên điện thoại, nút Gọi
    // đứng đầu thanh dưới cùng (CSS :first-child tô đậm, rộng gấp đôi).
    public function test_thanh_duoi_dien_thoai_nut_goi_dung_dau(): void
    {
        Setting::put('advisor_name', 'Thu Hà');
        Setting::put('zalo', '0989345989');

        $html = $this->get('/san-pham/rx')->assertOk()->getContent();
        preg_match('#<nav class="sales-bar"[^>]*>(.*?)</nav>#s', $html, $bar);
        preg_match_all('#<a [^>]*href="([^"]+)"#', $bar[1], $links);

        $this->assertSame('tel:0989345989', $links[1][0], 'nút đầu là Gọi');
        $this->assertStringContainsString('zalo.me/0989345989', $links[1][1], 'thứ hai là Zalo');
        $this->assertStringContainsString('/bao-gia', $links[1][2], 'cuối là Báo giá');
        $this->assertStringContainsString('>Gọi Thu Hà<', $bar[1]);
    }
}
