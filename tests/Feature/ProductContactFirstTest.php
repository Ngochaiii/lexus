<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use Tests\TestCase;

/**
 * Trang xe là trang đích quảng cáo dòng xe (C4). Sale ra nhiều cuộc gọi nhất
 * (báo cáo đội sale 25/09/2026) dùng trang chỉ có Gọi + Zalo, không form; trang
 * xe cũ có Gọi/Zalo ở giữa trang và hai form (popup + cuối trang). Nay: Gọi/Zalo
 * ngay ở đầu trang, chỉ còn một form (popup "để lại số").
 */
class ProductContactFirstTest extends TestCase
{
    private Product $rx;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put('advisor_name', 'Thu Hà');
        Setting::put('advisor_phone', '0934846666');
        Setting::put('advisor_zalo', '0989345989');

        $this->rx = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now()]);
        $this->rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'sort' => 1]);
    }

    private function hero(string $html): string
    {
        preg_match('#<section class="hero model-hero.*?</section>#s', $html, $m);

        return $m[0] ?? '';
    }

    public function test_dau_trang_xe_co_nut_goi_va_zalo_chuyen_vien(): void
    {
        $hero = $this->hero($this->get('/san-pham/rx')->assertOk()->getContent());

        $this->assertStringContainsString('href="tel:0934846666"', $hero);
        $this->assertStringContainsString('href="https://zalo.me/0989345989"', $hero);
        $this->assertStringContainsString('Gọi Thu Hà', $hero);
    }

    public function test_trang_xe_chi_con_mot_form_la_popup(): void
    {
        $html = $this->get('/san-pham/rx')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<form class="lead-form"'));
        $this->assertStringContainsString('id="quote-dialog"', $html);
    }

    public function test_cuoi_trang_xe_moi_goi_zalo_va_de_lai_so_qua_popup(): void
    {
        $html = $this->get('/san-pham/rx')->assertOk()->getContent();
        preg_match('#<section class="section stone contact-close".*?</section>#s', $html, $m);
        $close = $m[0] ?? '';

        $this->assertStringContainsString('href="tel:0934846666"', $close);
        $this->assertStringContainsString('href="https://zalo.me/0989345989"', $close);
        $this->assertStringContainsString('data-quote data-product="'.$this->rx->id.'"', $close);
    }

    public function test_config_mac_dinh_khong_dat_form_cuoi_trang_xe(): void
    {
        $this->assertSame([], config('catalog.frontend.product_forms'));
    }
}
