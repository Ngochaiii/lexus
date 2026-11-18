<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\ProductVariant;
use App\Support\Availability;
use Tests\TestCase;

/**
 * "Có sẵn, giao ngay" đứng cạnh nút Gọi (18/11/2026).
 *
 * Cả tuần ~440 lượt nhấp quảng cáo mà không ai bấm Gọi trên điện thoại: khách
 * xem giá rồi đi, chưa có lý do gọi ngay. Chị Hà xác nhận 18/11: RX 350h
 * Premium có sẵn, giao ngay. Đại lý không có ưu đãi — giao xe sớm là lý do
 * thật duy nhất. Nhãn "Có sẵn xe" đã có trên thẻ phiên bản nhưng nằm xa nút
 * Gọi; phiên bản có ghi chú "giao ngay" (Admin → Dòng xe → Phiên bản) thì
 * hiện một dòng ngay trên nút Gọi đầu trang và trong khối người tư vấn. Hết
 * xe: sửa ghi chú trong admin là dòng tự mất — không viết cứng vào trang.
 */
class ReadyNowStockTest extends TestCase
{
    private Product $rx;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put('advisor_name', 'Thu Hà');
        Setting::put('advisor_phone', '0977573366');
        Setting::put('advisor_zalo', '0977573366');
        (require database_path('migrations/2026_10_26_100000_quick_callback_form.php'))->up();

        $this->rx = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now(),
            'price_from' => 3_350_000_000]);
        $this->rx->variants()->create(['name' => 'RX 350h Premium', 'slug' => 'rx-350h-premium', 'price' => 3_350_000_000, 'sort' => 1,
            'availability' => 'in_stock', 'availability_note' => 'nhiều màu, thay đổi theo ngày']);
        $this->rx->variants()->create(['name' => 'RX 350h Luxury', 'slug' => 'rx-350h-luxury', 'price' => 3_700_000_000, 'sort' => 2,
            'availability' => 'in_stock', 'availability_note' => 'nhiều màu, thay đổi theo ngày']);
    }

    private function migrate(): void
    {
        (require database_path('migrations/2026_11_18_100000_rx_350h_premium_ready_now.php'))->up();
    }

    public function test_migration_chi_doi_rx_350h_premium_con_ghi_chu_cu(): void
    {
        $this->migrate();
        $this->migrate();

        $premium = ProductVariant::where('slug', 'rx-350h-premium')->sole();
        $this->assertSame('in_stock', $premium->availability);
        $this->assertSame('giao ngay', $premium->availability_note);
        $this->assertSame('nhiều màu, thay đổi theo ngày', ProductVariant::where('slug', 'rx-350h-luxury')->sole()->availability_note,
            'chị Hà chỉ xác nhận bản Premium');
    }

    public function test_admin_da_doi_tinh_trang_thi_migration_khong_de_len(): void
    {
        ProductVariant::where('slug', 'rx-350h-premium')->update(['availability' => 'contact', 'availability_note' => null]);

        $this->migrate();

        $this->assertSame('contact', ProductVariant::where('slug', 'rx-350h-premium')->sole()->availability);
    }

    public function test_dong_giao_ngay_dung_ngay_tren_nut_goi_va_trong_khoi_tu_van(): void
    {
        $this->migrate();

        $html = $this->get('/san-pham/rx')->assertOk()->getContent();

        $line = strpos($html, '<p class="hero-ready">RX 350h Premium có sẵn, giao ngay</p>');
        $this->assertNotFalse($line, 'dòng giao ngay ở đầu trang');
        $this->assertLessThan(strpos($html, 'href="tel:0977573366">Gọi Thu Hà</a>'), $line, 'nằm ngay trên nút Gọi');

        preg_match('#<aside class="advisor-strip".*?</aside>#s', $html, $m);
        $this->assertStringContainsString('<p class="advisor-strip__ready">RX 350h Premium có sẵn, giao ngay.</p>', $m[0] ?? '');
        $this->assertStringNotContainsString('Luxury có sẵn, giao ngay', $html);
    }

    public function test_khong_co_ban_giao_ngay_thi_khong_hien_gi(): void
    {
        $html = $this->get('/san-pham/rx')->assertOk()->getContent();

        $this->assertStringNotContainsString('hero-ready', $html);
        $this->assertStringNotContainsString('advisor-strip__ready', $html);
    }

    public function test_het_xe_hoac_dat_truoc_thi_khong_tinh_la_giao_ngay(): void
    {
        $variants = collect([
            new ProductVariant(['name' => 'A', 'availability' => 'pre_order', 'availability_note' => 'giao ngay sau Tết']),
            new ProductVariant(['name' => 'B', 'availability' => 'in_stock', 'availability_note' => 'Giao ngay']),
            new ProductVariant(['name' => 'C', 'availability' => 'in_stock', 'availability_note' => null]),
        ]);

        $this->assertSame(['B'], Availability::readyNow($variants));
    }
}
