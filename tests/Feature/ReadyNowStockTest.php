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

    public function test_nut_zalo_dau_trang_hoi_mau_con_xe_khi_co_ban_giao_ngay(): void
    {
        // 23/11/2026: quảng cáo + trang đã cho đủ giá niêm yết và lăn bánh tạm
        // tính → "nhắn Zalo hỏi giá" không còn là lý do liên hệ. Màu nào còn xe
        // hôm nay thì web không trả lời được, chỉ chị Hà biết.
        $this->migrate();

        $html = $this->get('/san-pham/rx')->assertOk()->getContent();
        preg_match('#<div class="actions">.*?</div>#s', $html, $m);

        $this->assertStringContainsString('data-zalo-text="Chào chị Hà, RX 350h Premium hôm nay còn màu nào ạ?"', $m[0] ?? '');
        $this->assertStringContainsString('>Hỏi màu còn xe</a>', $m[0] ?? '');
        $this->assertStringContainsString('href="https://zalo.me/0977573366"', $m[0] ?? '', 'vẫn là link Zalo: đo, khung QR máy tính giữ nguyên');
        $this->assertStringContainsString('link.dataset.zaloText', file_get_contents(public_path('assets/lead.js')), 'lời nhắn chép sẵn lấy từ nút');
    }

    public function test_khong_co_ban_giao_ngay_thi_khong_hien_gi(): void
    {
        $html = $this->get('/san-pham/rx')->assertOk()->getContent();

        $this->assertStringNotContainsString('hero-ready', $html);
        $this->assertStringNotContainsString('advisor-strip__ready', $html);
        $this->assertStringNotContainsString('data-zalo-text', $html);
        $this->assertStringNotContainsString('Hỏi màu còn xe', $html);
        $this->assertStringContainsString('rel="noopener">Nhắn Zalo</a>', $html);
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

    public function test_moi_ban_deu_giao_ngay_thi_ghi_gon_ca_n_ban(): void
    {
        $two = collect([
            new ProductVariant(['name' => 'GX 550 cửa trời đơn', 'availability' => 'in_stock', 'availability_note' => 'giao ngay']),
            new ProductVariant(['name' => 'GX 550 cửa trời toàn cảnh', 'availability' => 'in_stock', 'availability_note' => 'giao ngay']),
        ]);
        $this->assertSame('Cả 2 bản Lexus GX', Availability::readyLabel($two, 'Lexus GX'));
        $this->assertSame('Lexus GX', Availability::readySubject($two, 'Lexus GX'));

        $one = $this->rx->variants()->get();
        $one->first()->availability_note = 'giao ngay';
        $this->assertSame('RX 350h Premium', Availability::readyLabel($one, 'Lexus RX'), 'một trong hai bản: ghi tên bản');
        $this->assertSame('RX 350h Premium', Availability::readySubject($one, 'Lexus RX'));
        $this->assertNull(Availability::readyLabel(collect(), 'Lexus RX'));
    }

    public function test_gx_ca_hai_ban_giao_ngay(): void
    {
        // Chị Hà xác nhận 23/11/2026: GX có sẵn xe (tháng 11 tập trung RX 350h + GX 550).
        $gx = Product::create(['name' => 'Lexus GX', 'slug' => 'gx', 'status' => 'published', 'published_at' => now(),
            'price_from' => 6_400_000_000]);
        $gx->variants()->create(['name' => 'GX 550 cửa trời đơn', 'slug' => 'gx-550-cua-troi-don', 'price' => 6_400_000_000, 'sort' => 1,
            'availability' => 'in_stock', 'availability_note' => 'nhiều màu, thay đổi theo ngày']);
        $gx->variants()->create(['name' => 'GX 550 cửa trời toàn cảnh', 'slug' => 'gx-550', 'price' => 6_450_000_000, 'sort' => 2,
            'availability' => 'in_stock', 'availability_note' => 'nhiều màu, thay đổi theo ngày']);

        $migration = require database_path('migrations/2026_11_23_100000_gx_550_ready_now.php');
        $migration->up();
        $migration->up();

        $this->assertSame(['giao ngay', 'giao ngay'], ProductVariant::whereIn('slug', ['gx-550-cua-troi-don', 'gx-550'])->orderBy('sort')->pluck('availability_note')->all());
        $this->assertSame('nhiều màu, thay đổi theo ngày', ProductVariant::where('slug', 'rx-350h-luxury')->sole()->availability_note, 'chỉ đổi GX');

        $html = $this->get('/san-pham/gx')->assertOk()->getContent();
        $this->assertStringContainsString('<p class="hero-ready">Cả 2 bản Lexus GX có sẵn, giao ngay</p>', $html);
        $this->assertStringContainsString('<p class="advisor-strip__ready">Cả 2 bản Lexus GX có sẵn, giao ngay.</p>', $html);
        $this->assertStringContainsString('data-zalo-text="Chào chị Hà, Lexus GX hôm nay còn màu nào ạ?">Hỏi màu còn xe</a>', $html);
    }

    public function test_migration_gx_khong_de_len_tinh_trang_admin_da_doi(): void
    {
        $gx = Product::create(['name' => 'Lexus GX', 'slug' => 'gx', 'status' => 'published', 'published_at' => now()]);
        $gx->variants()->create(['name' => 'GX 550 cửa trời toàn cảnh', 'slug' => 'gx-550', 'price' => 6_450_000_000,
            'availability' => 'pre_order', 'availability_note' => 'giao tháng 12']);

        (require database_path('migrations/2026_11_23_100000_gx_550_ready_now.php'))->up();

        $this->assertSame('giao tháng 12', ProductVariant::where('slug', 'gx-550')->sole()->availability_note);
    }
}
