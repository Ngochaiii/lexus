<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Tests\TestCase;

/**
 * Nút Zalo (25/10/2026): 5 lượt bấm Gọi/Zalo từ quảng cáo, chị Hà không nhận
 * được gì; một khách bấm Zalo hai lần cách 9 giây (Zalo không mở). Link
 * zalo.me/<số> của Zalo cá nhân chỉ mở trang cá nhân, khách còn phải bấm
 * "Nhắn tin" và tự gõ. Nay bấm Zalo: chép sẵn lời nhắn có tên xe, vẫn mở Zalo,
 * và hiện khung dự phòng (mở lại Zalo, chép số Zalo, gọi, để lại số).
 */
class ZaloSheetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Setting::put('advisor_name', 'Thu Hà');
        Setting::put('advisor_phone', '0934846666');
        Setting::put('advisor_zalo', '0989345989');

        $rx = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now(),
            'price_from' => 3_350_000_000]);
        $rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'sort' => 1]);
    }

    private function sheet(string $url): string
    {
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('#<dialog class="zalo-sheet".*?</dialog>#s', $html, $m);

        return $m[0] ?? '';
    }

    public function test_moi_trang_co_khung_zalo_du_phong(): void
    {
        $sheet = $this->sheet('/');

        $this->assertStringContainsString('href="https://zalo.me/0989345989"', $sheet);
        $this->assertStringContainsString('data-copy="0989345989"', $sheet);
        $this->assertStringContainsString('0989 345 989', $sheet);
        $this->assertStringContainsString('href="tel:0934846666"', $sheet);
        $this->assertStringContainsString('data-quote', $sheet);
    }

    public function test_loi_nhan_mac_dinh_goi_chi_ha_va_hoi_gia_xe_lexus(): void
    {
        $sheet = $this->sheet('/');

        $this->assertStringContainsString('data-greeting="Chào chị Hà, em muốn hỏi giá lăn bánh"', $sheet);
        $this->assertStringContainsString('data-car="xe Lexus"', $sheet);
    }

    public function test_trang_xe_dien_san_ten_xe_vao_loi_nhan(): void
    {
        $this->assertStringContainsString('data-car="Lexus RX"', $this->sheet('/san-pham/rx'));
    }

    public function test_bang_gia_moi_dong_gan_ten_phien_ban_cho_loi_nhan(): void
    {
        Page::create(['slug' => 'bang-gia', 'title' => 'Bảng giá xe Lexus', 'status' => 'published']);

        $html = $this->get('/bang-gia')->assertOk()->getContent();

        $this->assertStringContainsString('data-zalo-car="Lexus RX 350h Premium"', $html);
    }

    public function test_chua_cai_zalo_thi_khong_co_khung(): void
    {
        Setting::put('advisor_zalo', '');

        $this->assertSame('', $this->sheet('/'));
    }

    public function test_nut_mo_lai_zalo_trong_khung_khong_dem_them_luot_bam(): void
    {
        $this->assertStringContainsString('data-zalo-again', $this->sheet('/'));
        $this->assertStringContainsString(':not([data-zalo-again])', file_get_contents(public_path('assets/insight.js')));
    }
}
