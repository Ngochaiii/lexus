<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Support\ZaloQr;
use Tests\TestCase;

/**
 * Máy tính: hành động chính là quét mã QR Zalo (16/11/2026).
 *
 * Cả 3 form từ quảng cáo (#7, #16, #17) đều gửi từ máy tính và người nghe máy
 * đều nói không hề gửi. Trên máy tính khách không gọi được (tel: không quay
 * số), Zalo thường không mở → lối duy nhất là gõ một số vào form, gõ số nào
 * cũng được. Quét QR bằng điện thoại thì khách nhắn bằng chính Zalo của mình:
 * chị Hà thấy tên, ảnh thật. Điện thoại giữ nguyên (Gọi/Zalo vẫn là nút chính).
 * Form vẫn còn trên máy tính, chỉ thu lại sau một nút "Hoặc để lại số".
 */
class DesktopZaloQrTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Setting::put('advisor_name', 'Thu Hà');
        Setting::put('advisor_phone', '0977573366');
        Setting::put('advisor_zalo', '0977573366');
        (require database_path('migrations/2026_10_26_100000_quick_callback_form.php'))->up();

        $rx = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now(),
            'price_from' => 3_350_000_000]);
        $rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'sort' => 1]);
        Page::create(['slug' => 'bang-gia', 'title' => 'Bảng giá xe Lexus', 'status' => 'published']);
    }

    private function block(string $html, string $tag, string $class): string
    {
        preg_match('#<'.$tag.' class="'.$class.'".*?</'.$tag.'>#s', $html, $m);

        return $m[0] ?? '';
    }

    public function test_ma_qr_la_svg_tu_tao_tren_may_chu_khong_goi_dich_vu_ngoai(): void
    {
        $svg = ZaloQr::svg('https://zalo.me/0977573366');

        $this->assertStringStartsWith('<svg', $svg);
        $this->assertStringNotContainsString('<?xml', $svg, 'chèn thẳng vào trang');
        $this->assertStringContainsString('aria-hidden="true"', $svg);
        $this->assertNotSame($svg, ZaloQr::svg('https://zalo.me/0912345678'), 'mỗi số một mã');
    }

    public function test_khoi_nguoi_tu_van_co_ma_qr_canh_form_khong_bat_bam_them(): void
    {
        // 17/11: người dùng — đừng bắt khách thật bấm thêm để chặn kẻ phá.
        // Máy tính hiện form NGAY cạnh mã QR: hai lựa chọn ngang nhau.
        $strip = $this->block($this->get('/san-pham/rx')->assertOk()->getContent(), 'aside', 'advisor-strip');

        $qr = $this->block($strip, 'div', 'zalo-qr desk-only');
        $this->assertStringContainsString('<svg', $qr);
        $this->assertStringContainsString('Quét bằng camera điện thoại để nhắn Zalo Thu Hà', $qr);
        $this->assertStringContainsString('0977 573 366', $qr, 'số gọi in rõ cho ai muốn gọi từ điện thoại');

        $this->assertMatchesRegularExpression('#<div class="advisor-strip__contact">.*id="xe-phone".*class="zalo-qr desk-only"#s', $strip, 'form và mã QR cạnh nhau');
        $this->assertStringNotContainsString('data-desk-collapse', $strip);
        $this->assertStringNotContainsString('data-desk-reveal', $strip);
        $this->assertStringContainsString('class="text-link advisor-strip__zalo touch-only"', $strip, 'link Zalo chỉ cho điện thoại');
    }

    public function test_bang_gia_cung_co_ma_qr(): void
    {
        $strip = $this->block($this->get('/bang-gia')->assertOk()->getContent(), 'aside', 'advisor-strip');

        $this->assertStringContainsString('class="zalo-qr desk-only"', $strip);
    }

    public function test_khung_zalo_va_khung_goi_co_ma_qr_cho_may_tinh(): void
    {
        $html = $this->get('/san-pham/rx')->assertOk()->getContent();

        $zalo = $this->block($html, 'dialog', 'zalo-sheet');
        $this->assertStringContainsString('class="zalo-qr desk-only"', $zalo);
        $this->assertStringContainsString('Mở Zalo trên máy tính', $zalo, 'ai có Zalo PC vẫn mở được');

        $call = $this->block($html, 'dialog', 'call-sheet');
        $this->assertStringContainsString('class="zalo-qr desk-only"', $call);
        $this->assertStringContainsString('id="call-sheet-phone"', $call);
        $this->assertStringNotContainsString('data-desk-collapse', $call, 'form hiện ngay, không thu lại');
    }

    public function test_chua_cai_zalo_thi_khong_co_ma_qr(): void
    {
        Setting::put('advisor_zalo', '');
        Setting::put('zalo', '');

        $html = $this->get('/san-pham/rx')->assertOk()->getContent();

        $this->assertStringNotContainsString('zalo-qr', $html);
        $this->assertStringContainsString('id="xe-phone"', $html, 'form vẫn còn');
    }

    public function test_js_may_tinh_mo_khung_qr_va_khong_tinh_chuyen_doi_ads(): void
    {
        $lead = file_get_contents(public_path('assets/lead.js'));
        $insight = file_get_contents(public_path('assets/insight.js'));
        $css = file_get_contents(public_path('assets/style.css'));

        $this->assertStringContainsString('(hover: hover) and (pointer: fine)', $lead);
        $this->assertStringContainsString('preventDefault', $lead);
        // Mở khung QR chưa phải liên hệ: không báo click_call/click_zalo cho Ads.
        $this->assertStringContainsString("'contact_open'", $insight);
        $this->assertStringContainsString('@media (hover:hover) and (pointer:fine)', $css);
    }
}
