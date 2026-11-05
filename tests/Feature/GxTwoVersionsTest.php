<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Product;
use App\Support\Availability;
use Tests\TestCase;

/**
 * GX 550 có hai bản (người dùng xác nhận 05/11/2026): cửa trời đơn 6,4 tỷ, cửa
 * trời toàn cảnh 6,45 tỷ. Bản cũ giữ đường dẫn /san-pham/gx/gx-550 (quảng cáo
 * và Google đang trỏ tới). RX 350h và GX 550 có sẵn nhiều màu, thay đổi theo ngày.
 */
class GxTwoVersionsTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_11_05_100000_gx_two_versions_and_stock.php';

    private const RX_NOTICE = 'Bạn đang tìm Lexus RX 350? Bản RX đang bán tại Lexus Thăng Long là RX 350h hybrid 2.5L '
        .'(Premium và Luxury) và RX 500h F SPORT Performance. Giá niêm yết và lăn bánh từng bản ngay bên dưới.';

    private Product $gx;

    private Product $rx;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gx = Product::create(['name' => 'Lexus GX', 'slug' => 'gx', 'status' => 'published', 'published_at' => now(),
            'price_from' => 6_450_000_000,
            'seo' => [
                'title' => 'Lexus GX 550 2026: giá 6,45 tỷ, thông số | Thu Hà tư vấn Lexus',
                'description' => 'Giá Lexus GX 550 2026 tại Hà Nội: 6,45 tỷ đồng, lăn bánh khoảng 7,24 tỷ. SUV khung gầm rời.',
            ]]);
        $this->gx->variants()->create(['name' => 'GX 550', 'slug' => 'gx-550', 'image' => 'catalog/lexus/gx/phien-ban/luxury.webp',
            'price' => 6_450_000_000, 'note' => 'V6 3.5L tăng áp kép · 7 chỗ', 'sort' => 1, 'is_default' => true]);

        $this->rx = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now(),
            'price_from' => 3_350_000_000, 'notice' => self::RX_NOTICE]);
        $this->rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'sort' => 1]);
        $this->rx->variants()->create(['name' => 'RX 350h Luxury', 'price' => 4_140_000_000, 'sort' => 2]);
        $this->rx->variants()->create(['name' => 'RX 500h F SPORT Performance', 'price' => 4_940_000_000, 'sort' => 3]);
    }

    private function migrate(): void
    {
        (require database_path(self::MIGRATION))->up();
    }

    public function test_gx_co_hai_ban_dung_gia_ban_re_len_truoc(): void
    {
        $this->migrate();

        $variants = $this->gx->variants()->orderBy('sort')->get();
        $this->assertSame(['GX 550 cửa trời đơn', 'GX 550 cửa trời toàn cảnh'], $variants->pluck('name')->all());
        $this->assertSame([6_400_000_000, 6_450_000_000], $variants->map(fn ($v) => (int) $v->price)->all());
        $this->assertSame(6_400_000_000, (int) $this->gx->fresh()->price_from);
    }

    public function test_ban_toan_canh_giu_duong_dan_cu_cua_quang_cao(): void
    {
        $this->migrate();

        $this->assertSame('gx-550', $this->gx->variants()->where('price', 6_450_000_000)->value('slug'));
        $this->get('/san-pham/gx/gx-550')->assertOk()->assertSee('GX 550 cửa trời toàn cảnh');
        $this->get('/san-pham/gx/gx-550-cua-troi-don')->assertOk()->assertSee('6.400.000.000');
    }

    public function test_chay_lai_khong_tao_them(): void
    {
        $this->migrate();
        $this->migrate();

        $this->assertSame(2, $this->gx->variants()->count());
    }

    public function test_gia_gx_da_sua_tay_thi_khong_doi(): void
    {
        $this->gx->variants()->update(['price' => 6_500_000_000]);

        $this->migrate();

        $this->assertSame(['GX 550'], $this->gx->variants()->pluck('name')->all());
    }

    public function test_doi_chu_gia_gx_trong_seo_va_bai_viet(): void
    {
        $post = Post::create(['title' => 'GX hay LX', 'slug' => 'gx-hay-lx', 'status' => 'published', 'published_at' => now(),
            'excerpt' => 'Lexus GX 550 giá 6,45 tỷ, V6 3.5L. Đang bán 12 phiên bản.',
            'sections' => [['type' => 'content', 'body' => '<p>Lexus GX 550 (6,45 tỷ đồng) gọn hơn. Lexus LM 500h 7,21 tỷ. '
                .'GX 550 (6,45 tỷ) và LX 600. Lexus GX 550: Giá niêm yết 6.450.000.000 đ (lăn bánh Hà Nội tạm tính khoảng 7,24 tỷ đồng).</p>',
                'rows' => ['Giá niêm yết' => 'GX 550: 6,45 tỷ · LX 600: 8,59 – 9,70 tỷ', 'Lăn bánh' => 'GX 550: khoảng 7,24 tỷ · LX 600'],
                'faq' => ['Giá?' => 'Giá niêm yết 6,45 tỷ đồng (đã gồm VAT); lăn bánh tại Hà Nội tạm tính khoảng 7,24 tỷ đồng.'],
                'list' => ['Lexus GX (SUV khung rời)' => '6.450.000.000 đ — GX 550'],
                'cards' => [['eyebrow' => '6,45 tỷ', 'label' => 'GX 550']]]]]);

        $this->migrate();
        $text = json_encode($post->fresh()->only('excerpt', 'sections'), JSON_UNESCAPED_UNICODE);

        $this->assertStringNotContainsString('(6,45 tỷ', $text);
        $this->assertStringNotContainsString('GX 550: 6,45 tỷ', $text);
        $this->assertStringNotContainsString('GX 550 giá 6,45', $text);
        $this->assertStringNotContainsString('"6,45 tỷ"', $text);
        $this->assertStringContainsString('GX 550 giá từ 6,4 tỷ', $text);
        $this->assertStringContainsString('cửa trời đơn 6,4 tỷ', $text);
        $this->assertStringContainsString('7,18', $text);
        $this->assertStringContainsString('13 phiên bản', $text);
        $this->assertStringContainsString('7,21 tỷ', $text, 'không đụng giá LM');

        $seo = $this->gx->fresh()->seo;
        $this->assertStringContainsString('từ 6,4 tỷ', $seo['title']);
        $this->assertStringContainsString('từ 6,4 tỷ', $seo['description']);
    }

    public function test_rx_350h_va_gx_ghi_co_san_nhieu_mau_rx_500h_thi_khong(): void
    {
        $this->migrate();

        foreach ([...$this->gx->variants()->get(), $this->rx->variants()->where('name', 'RX 350h Premium')->first(),
            $this->rx->variants()->where('name', 'RX 350h Luxury')->first()] as $variant) {
            $this->assertSame('Có sẵn xe · nhiều màu, thay đổi theo ngày', Availability::label($variant), $variant->name);
        }
        $this->assertNull($this->rx->variants()->where('name', 'like', 'RX 500h%')->value('availability'));
    }

    public function test_tinh_trang_da_nhap_tay_thi_giu(): void
    {
        $this->rx->variants()->where('name', 'RX 350h Luxury')->update(['availability' => 'pre_order', 'availability_note' => 'giao 2 tuần']);

        $this->migrate();

        $this->assertSame('Đặt trước · giao 2 tuần', Availability::label($this->rx->variants()->where('name', 'RX 350h Luxury')->first()));
    }

    public function test_ghi_chu_moi_hoi_mau_o_rx_va_gx(): void
    {
        $this->migrate();

        $this->assertStringContainsString('màu bạn thích', $this->rx->fresh()->notice);
        $this->assertStringContainsString('cửa trời đơn', $this->gx->fresh()->notice);
        $this->assertStringContainsString('màu bạn thích', $this->gx->fresh()->notice);
    }

    public function test_ghi_chu_da_sua_tay_thi_giu(): void
    {
        $this->rx->update(['notice' => 'Chị Hà tự viết.']);
        $this->gx->update(['notice' => 'Chị Hà viết GX.']);

        $this->migrate();

        $this->assertSame('Chị Hà tự viết.', $this->rx->fresh()->notice);
        $this->assertSame('Chị Hà viết GX.', $this->gx->fresh()->notice);
    }

    public function test_seeder_co_hai_ban_gx_va_giu_duong_dan_cu(): void
    {
        $seed = file_get_contents(database_path('seeders/Brands/LexusSeeder.php'));

        $this->assertStringContainsString("'name' => 'GX 550 cửa trời đơn'", $seed);
        $this->assertStringContainsString("'name' => 'GX 550 cửa trời toàn cảnh', 'slug' => 'gx-550'", $seed);
    }
}
