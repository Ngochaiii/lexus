<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Tests\TestCase;

/**
 * SEO + GEO (tối ưu cho công cụ AI): robots.txt, llms.txt, JSON-LD dạng
 * @graph, sitemap có ảnh, bảng giá tự sinh, neo theo tiêu đề mục.
 */
class SeoGeoTest extends TestCase
{
    private Product $rx;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://lexusthanglong.test']);
        Setting::put('site_name', 'Lexus Thăng Long');
        Setting::put('hotline', '0989345989');
        Setting::put('advisor_name', 'Thu Hà');

        $this->rx = Product::create([
            'name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now(),
            'price_from' => 3_350_000_000,
            'hero' => ['type' => 'image', 'src' => 'catalog/lexus/rx/hero.webp'],
            'sections' => [[
                'type' => 'faq', 'title' => 'Hỏi đáp',
                'rows' => [['label' => 'Giá xe Lexus RX bao nhiêu?', 'value' => 'Từ 3,35 tỷ đồng.']],
            ]],
            'spec_notes' => [['label' => 'Lưu ý', 'body' => 'Thông số theo công bố của hãng.']],
        ]);
        $this->rx->variants()->createMany([
            ['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'sort' => 1],
            ['name' => 'RX 500h F SPORT Performance', 'price' => 4_940_000_000, 'sort' => 2],
        ]);
    }

    /** @return array<int, array<string, mixed>> các node trong @graph của trang */
    private function graph(string $url): array
    {
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $data = json_decode($m[1] ?? '', true, flags: JSON_THROW_ON_ERROR);

        return $data['@graph'] ?? [$data];
    }

    private function node(array $graph, string $type): ?array
    {
        return collect($graph)->firstWhere('@type', $type);
    }

    public function test_robots_sinh_theo_app_url_va_moi_bot_ai(): void
    {
        $txt = $this->get('/robots.txt')->assertOk()->getContent();

        // Lỗi gốc: file tĩnh copy từ bản VinFast khai sitemap của domain khác.
        $this->assertStringNotContainsString('vinfast', $txt);
        $this->assertStringContainsString('Sitemap: https://lexusthanglong.test/sitemap.xml', $txt);
        $this->assertStringContainsString('User-agent: GPTBot', $txt);
        $this->assertStringContainsString('User-agent: PerplexityBot', $txt);
        $this->assertStringContainsString('Disallow: /admin/', $txt);
        $this->assertFileDoesNotExist(public_path('robots.txt'), 'file tĩnh sẽ che mất route động');
    }

    public function test_llms_txt_liet_ke_xe_kem_gia_tung_phien_ban(): void
    {
        $txt = $this->get('/llms.txt')->assertOk()->getContent();

        $this->assertStringStartsWith('# Lexus Thăng Long', $txt);
        $this->assertStringContainsString('[Lexus RX](https://lexusthanglong.test/san-pham/rx)', $txt);
        $this->assertStringContainsString('RX 500h F SPORT Performance', $txt);
        $this->assertStringContainsString('0989 345 989', $txt);
    }

    public function test_trang_xe_co_graph_product_dai_gia_faq_va_dai_ly(): void
    {
        $graph = $this->graph('/san-pham/rx');

        $product = $this->node($graph, 'Product');
        $this->assertSame('AggregateOffer', $product['offers']['@type']);
        $this->assertSame('3350000000.00', $product['offers']['lowPrice']);
        $this->assertSame('4940000000.00', $product['offers']['highPrice']);
        $this->assertCount(2, $product['offers']['offers']);
        $this->assertSame('Lexus', $product['brand']['name']);
        // Xe nối với đại lý bằng @id — một thực thể, không phải hai mẩu rời.
        $this->assertSame('https://lexusthanglong.test/#organization', $product['offers']['seller']['@id']);

        $faq = $this->node($graph, 'FAQPage');
        $this->assertSame('Giá xe Lexus RX bao nhiêu?', $faq['mainEntity'][0]['name']);

        $dealer = $this->node($graph, 'AutoDealer');
        $this->assertSame('Cầu Giấy', $dealer['address']['addressLocality']);
        $this->assertSame('+84989345989', $dealer['telephone']);
        $this->assertSame('Thu Hà', $dealer['employee']['name']);

        $this->assertNotNull($this->node($graph, 'BreadcrumbList'));
    }

    public function test_ghi_chu_thong_so_dang_mang_khong_lam_vo_trang(): void
    {
        $this->rx->update(['specs' => [['group' => 'Động cơ', 'rows' => [['label' => 'Công suất', 'value' => '371 HP']]]]]);

        $this->get('/san-pham/rx')->assertOk()->assertSee('Thông số theo công bố của hãng.');
    }

    public function test_bang_gia_tu_sinh_tu_du_lieu_xe(): void
    {
        Page::create(['slug' => 'bang-gia', 'title' => 'Bảng giá xe Lexus', 'status' => 'published']);

        $this->get('/bang-gia')
            ->assertOk()
            ->assertSee('RX 500h F SPORT Performance')
            ->assertSee(catalog_money(4_940_000_000));
    }

    public function test_muc_co_tieu_de_nhan_neo_theo_slug(): void
    {
        Page::create(['slug' => 'the-gioi-lexus', 'title' => 'Thế giới Lexus', 'status' => 'published', 'sections' => [
            ['type' => 'text', 'title' => 'Omotenashi', 'body' => 'Tinh thần hiếu khách.'],
            ['type' => 'text', 'title' => 'Takumi', 'body' => 'Nghệ nhân.'],
        ]]);

        $html = $this->get('/the-gioi-lexus')->assertOk()->getContent();

        // Trang chủ và chân trang link tới /the-gioi-lexus#omotenashi.
        $this->assertStringContainsString('id="omotenashi"', $html);
        $this->assertStringContainsString('id="takumi"', $html);
        $this->assertSame('AboutPage', $this->node($this->graph('/the-gioi-lexus'), 'AboutPage')['@type']);
    }

    public function test_muc_chia_nhom_moi_nhom_mot_tab_khong_can_js(): void
    {
        $this->rx->update(['sections' => [[
            'type' => 'media', 'layout' => 'groups', 'title' => 'Chi tiết', 'intro' => 'Chi tiết & tùy chọn',
            'items' => [
                ['image' => 'catalog/lexus/rx/chi-tiet/mam-premium.webp', 'eyebrow' => 'Mâm xe', 'label' => 'Mâm 21 inch', 'desc' => 'RX 350h Premium'],
                ['image' => 'catalog/lexus/rx/chi-tiet/mam-luxury.webp', 'eyebrow' => 'Mâm xe', 'label' => 'Mâm hợp kim', 'desc' => 'RX 350h Luxury'],
                ['image' => 'catalog/lexus/rx/chi-tiet/op-sumi.webp', 'eyebrow' => 'Ốp trang trí', 'label' => 'Vân gỗ Sumi'],
            ],
        ]]]);

        $html = $this->get('/san-pham/rx')->assertOk()->getContent();

        // Hai nhóm → hai radio + hai panel; nhóm đầu được chọn sẵn.
        $this->assertSame(2, substr_count($html, 'class="og-radio"'));
        $this->assertSame(2, substr_count($html, 'class="og-panel"'));
        $this->assertMatchesRegularExpression('/aria-label="Mâm xe"[^>]*checked|checked[^>]*aria-label="Mâm xe"/', $html);
        $this->assertStringContainsString('Mâm xe<small>2</small>', $html);
        $this->assertStringContainsString('RX 350h Luxury', $html);
    }

    public function test_sitemap_bo_trang_noi_bo_trang_rong_va_co_anh(): void
    {
        Page::create(['slug' => 'trang-chu', 'title' => 'Khối trang chủ', 'status' => 'published',
            'sections' => [['type' => 'text', 'title' => 'x', 'body' => 'y']]]);
        Page::create(['slug' => 'uu-dai', 'title' => 'Ưu đãi', 'status' => 'published']);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringNotContainsString('/trang-chu', $xml);
        $this->assertStringNotContainsString('/uu-dai', $xml, 'trang chưa có nội dung là trang mỏng');
        $this->assertStringContainsString('xmlns:image=', $xml);
        $this->assertStringContainsString('<image:loc>https://lexusthanglong.test/storage/catalog/lexus/rx/hero.webp</image:loc>', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
    }
}
