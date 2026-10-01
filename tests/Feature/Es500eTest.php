<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Tests\TestCase;

/**
 * Đại lý bán lại ES 500e từ 10/2026 (giá 2,98 tỷ). Site đang chạy đã có dữ
 * liệu ES do seeder đặt — migration thêm phiên bản và sửa các câu "2 phiên
 * bản" còn đúng bản gốc; nội dung đã sửa tay trong admin thì giữ nguyên.
 */
class Es500eTest extends TestCase
{
    private const OLD_PRICE_ANSWER = 'Lexus ES thế hệ mới có 2 phiên bản hybrid: ES 350h Premium 2,36 tỷ đồng và ES 350h Luxury 2,58 tỷ đồng '
        .'(giá niêm yết, đã gồm VAT). Lăn bánh tại Hà Nội tạm tính khoảng 2,66 tỷ và 2,90 tỷ đồng.';

    private Product $es;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put('site_name', 'Lexus Thăng Long');

        $this->es = Product::create([
            'name' => 'Lexus ES', 'slug' => 'es', 'status' => 'published', 'published_at' => now(),
            'tagline' => 'Sedan hạng sang thế hệ mới, hybrid 350h',
            'price_from' => 2_360_000_000,
            'highlights' => [
                ['value' => '350h', 'unit' => '', 'label' => 'Hybrid thế hệ mới'],
                ['value' => '2', 'unit' => 'phiên bản', 'label' => 'Premium · Luxury'],
            ],
            'sections' => [[
                'type' => 'faq', 'title' => 'Hỏi đáp', 'rows' => [
                    ['label' => 'Giá xe Lexus ES 2026 bao nhiêu?', 'value' => self::OLD_PRICE_ANSWER],
                    ['label' => 'ES 350h Premium và Luxury khác nhau thế nào?', 'value' => 'Hai bản cùng hệ truyền động hybrid 350h.'],
                ],
            ]],
            'seo' => ['title' => 'Lexus ES 350h 2026', 'description' => 'Giá Lexus ES 350h 2026 tại Hà Nội: Premium 2,36 tỷ, Luxury 2,58 tỷ. Sedan hybrid thế hệ mới, '
                .'màn hình lớn, an toàn LSS+. Xem màu và đăng ký lái thử tại Lexus Thăng Long.'],
        ]);
        $this->es->variants()->createMany([
            ['name' => 'ES 350h Premium', 'price' => 2_360_000_000, 'note' => 'Hybrid thế hệ mới', 'sort' => 1, 'is_default' => true],
            ['name' => 'ES 350h Luxury', 'price' => 2_580_000_000, 'note' => 'Hybrid · trang bị Luxury', 'sort' => 2],
        ]);
    }

    private function migrate(): void
    {
        (require database_path('migrations/2026_10_01_100000_add_lexus_es_500e.php'))->up();
    }

    public function test_them_es_500e_voi_gia_lan_banh_mien_truoc_ba(): void
    {
        $this->migrate();
        $this->migrate(); // chạy lại không nhân đôi

        $variants = $this->es->variants()->orderBy('sort')->get();
        $this->assertSame(['ES 350h Premium', 'ES 350h Luxury', 'ES 500e'], $variants->pluck('name')->all());
        $this->assertSame('es-500e', $variants[2]->slug);
        $this->assertEquals(2_980_000_000, $variants[2]->price);

        $html = $this->get('/san-pham/es/es-500e')->assertOk()->getContent();
        $this->assertStringContainsString('2.980.000.000 đ', $html);
        $this->assertStringContainsString('2.994.000.000 đ', $html, 'lăn bánh = 2,98 tỷ + trước bạ 0% + 14 triệu');
        $this->assertStringContainsString('Lệ phí trước bạ (0%)', $html);

        $this->get('/san-pham/es')->assertOk()->assertSee('href="/san-pham/es/es-500e"', false);

        Page::create(['slug' => 'bang-gia', 'title' => 'Bảng giá', 'status' => 'published', 'sections' => []]);
        $this->get('/bang-gia')->assertOk()->assertSee('ES 500e');
    }

    public function test_sua_noi_dung_2_phien_ban_con_dung_ban_goc(): void
    {
        $this->migrate();
        $es = $this->es->fresh();

        $this->assertSame('Sedan hạng sang thế hệ mới: hybrid 350h và thuần điện 500e', $es->tagline);
        $this->assertSame('3', $es->highlights[1]['value']);
        $this->assertStringContainsString('ES 500e thuần điện 2,98 tỷ', $es->seo['description']);
        $this->assertLessThanOrEqual(160, mb_strlen($es->seo['description']));

        $rows = collect($es->sections[0]['rows']);
        $this->assertStringContainsString('3 phiên bản', $rows[0]['value']);
        $this->assertSame('Lexus ES 500e có gì khác ES 350h?', $rows[1]['label'], 'câu hỏi mới ngay sau câu giá');
        $this->assertCount(3, $rows);

        $this->migrate();
        $this->assertCount(3, collect($this->es->fresh()->sections[0]['rows']), 'chạy lại không thêm câu hỏi trùng');
    }

    public function test_bai_viet_va_trang_tinh_noi_12_phien_ban_va_co_es_500e(): void
    {
        $post = \App\Models\Post::create([
            'title' => 'Bảng giá xe Lexus 2026 tại Hà Nội: 6 dòng xe, 11 phiên bản', 'slug' => 'bang-gia-xe-lexus-2026-tai-ha-noi',
            'status' => 'published', 'published_at' => now(),
            'sections' => [
                ['type' => 'table', 'rows' => [['label' => 'Lexus ES (sedan)', 'value' => 'từ 2.360.000.000 đ — ES 350h Premium và ES 350h Luxury']]],
                ['type' => 'text', 'body' => '<h3>Dưới 3 tỷ đồng</h3><p>ES 350h… khoang sau rộng, êm và tiết kiệm; hợp đi phố và đưa đón gia đình.</p><h3>Từ 3 đến 5 tỷ đồng</h3>'],
                ['type' => 'faq', 'rows' => [['label' => 'Lexus ES 2026 có mấy phiên bản?',
                    'value' => 'Hai phiên bản hybrid: ES 350h Premium 2,36 tỷ đồng và ES 350h Luxury 2,58 tỷ đồng.']]],
            ],
        ]);
        $page = Page::create(['slug' => 'faq', 'title' => 'Hỏi đáp', 'status' => 'published', 'sections' => [
            ['type' => 'faq', 'rows' => [['label' => 'Bán những dòng xe nào?', 'value' => '6 dòng xe Lexus, 11 phiên bản: ES (sedan)…']]],
        ]]);

        $this->migrate();
        $this->migrate();

        $post->refresh();
        $this->assertSame('Bảng giá xe Lexus 2026 tại Hà Nội: 6 dòng xe, 12 phiên bản', $post->title);
        $this->assertStringContainsString('ES 350h Luxury và ES 500e thuần điện', $post->sections[0]['rows'][0]['value']);
        $this->assertSame(1, substr_count($post->sections[1]['body'], 'ES 500e thuần điện (2,98 tỷ)'), 'đoạn chèn chỉ một lần');
        $this->assertStringStartsWith('Ba phiên bản', $post->sections[2]['rows'][0]['value']);
        $this->assertStringContainsString('12 phiên bản', $page->fresh()->sections[0]['rows'][0]['value']);
    }

    public function test_noi_dung_da_sua_tay_thi_giu_nguyen(): void
    {
        $this->es->update(['tagline' => 'Sedan của tôi', 'seo' => ['title' => 'T', 'description' => 'Mô tả tự viết']]);

        $this->migrate();

        $es = $this->es->fresh();
        $this->assertSame('Sedan của tôi', $es->tagline);
        $this->assertSame('Mô tả tự viết', $es->seo['description']);
    }
}
