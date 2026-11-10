<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Tests\TestCase;

/**
 * Khung sau nút Gọi (04/11/2026): 3 lượt bấm Gọi từ quảng cáo trong một ngày,
 * chị Hà không có cuộc gọi nào; gọi thử vẫn đổ chuông — khách mở màn hình quay
 * số rồi đổi ý. Bấm Gọi vẫn quay số như cũ; khách quay lại web có sẵn khung
 * "Để lại số" để không mất khách.
 */
class CallSheetTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_10_26_100000_quick_callback_form.php';

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put('advisor_name', 'Thu Hà');
        Setting::put('advisor_phone', '0977573366');

        (require database_path(self::MIGRATION))->up();

        Page::create(['slug' => 'bang-gia', 'title' => 'Bảng giá xe Lexus', 'status' => 'published']);
    }

    private function rx(): void
    {
        $rx = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now(),
            'price_from' => 3_350_000_000]);
        $rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'sort' => 1]);
    }

    private function sheet(string $url = '/'): string
    {
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('#<dialog class="call-sheet".*?</dialog>#s', $html, $m);

        return $m[0] ?? '';
    }

    public function test_moi_trang_co_khung_de_lai_so_sau_nut_goi(): void
    {
        $sheet = $this->sheet();

        $this->assertStringContainsString('Để lại số, Thu Hà gọi lại', $sheet);
        $this->assertStringContainsString('action="'.route('leads.store', 'goi-lai-nhanh').'"', $sheet);
        $this->assertStringContainsString('name="phone"', $sheet);
        $this->assertStringContainsString('0977 573 366', $sheet);
    }

    public function test_nut_goi_lai_trong_khung_khong_dem_them_luot_bam(): void
    {
        $this->assertStringContainsString('data-call-again', $this->sheet());
        $this->assertStringContainsString(':not([data-call-again])', file_get_contents(public_path('assets/insight.js')));
    }

    public function test_bam_goi_van_quay_so_khong_chan_link(): void
    {
        $js = file_get_contents(public_path('assets/lead.js'));

        preg_match('#const callSheet.*?/\* ── 2\. Popup#s', $js, $m);
        $this->assertNotEmpty($m, 'lead.js có khối khung Gọi trước mục popup');
        $this->assertStringContainsString('a[href^="tel:"]', $m[0]);
        $this->assertStringNotContainsString('preventDefault', $m[0]);
    }

    public function test_bang_gia_co_hai_form_de_lai_so_khong_trung_id(): void
    {
        $this->rx();

        $html = $this->get('/bang-gia')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'class="quick-callback"'));
        preg_match_all('#\sid="((?:quick-callback|call-sheet)[^"]*)"#', $html, $m);
        $this->assertSame(count($m[1]), count(array_unique($m[1])), 'id không trùng: '.implode(', ', $m[1]));
    }

    public function test_form_tren_bang_gia_van_nam_truoc_bang(): void
    {
        $this->rx();

        $html = $this->get('/bang-gia')->assertOk()->getContent();

        // Từ 10/11 ô này nằm trong khối người tư vấn (advisor-strip), cạnh ảnh chị Hà.
        $this->assertStringContainsString('gọi lại báo giá lăn bánh theo nơi đăng ký xe của bạn.', $html);
        $this->assertLessThan(strpos($html, '<table'), strpos($html, 'class="quick-callback"'));
    }

    public function test_khach_de_lai_so_trong_khung_thanh_lead(): void
    {
        $this->post(route('leads.store', 'goi-lai-nhanh'), ['phone' => '098 765 4321', 'consent' => ['1']])
            ->assertRedirect();

        $this->assertSame('0987654321', Lead::sole()->phone);
    }

    public function test_chua_cai_so_thi_khong_co_khung(): void
    {
        Setting::put('advisor_phone', '');
        Setting::put('hotline', '');

        $this->assertSame('', $this->sheet());
    }

    public function test_ghi_rieng_lead_tu_khung_goi_cho_ga4(): void
    {
        $this->assertStringContainsString('.call-sheet', file_get_contents(public_path('assets/insight.js')));
    }
}
