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
}
