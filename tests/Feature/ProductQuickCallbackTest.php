<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Setting;
use Tests\TestCase;

/**
 * Ô "để lại số" ngay đầu bảng giá trang dòng xe (10/11/2026). Đo hành trình
 * 09/11: 87 lượt khách quảng cáo, chỉ 17% thấy form, trung vị kéo tới 23%
 * trang — trang dòng xe không có form nằm sẵn (chỉ có khung bật lên), khách
 * đọc giá ở đầu trang rồi đi. Đặt ô một-số-điện-thoại (cùng form
 * goi-lai-nhanh của /bang-gia) ngay trên các thẻ phiên bản, kèm xe của trang.
 */
class ProductQuickCallbackTest extends TestCase
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
        $this->rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'sort' => 1]);
    }

    private function strip(string $html): string
    {
        preg_match('#<aside class="advisor-strip".*?</aside>#s', $html, $m);

        return $m[0] ?? '';
    }

    public function test_trang_dong_xe_co_o_de_lai_so_ngay_tren_the_phien_ban(): void
    {
        $html = $this->get('/san-pham/rx')->assertOk()->getContent();

        $form = strpos($html, 'id="xe-phone"');
        $this->assertNotFalse($form, 'có ô số điện thoại nằm sẵn trên trang');
        $this->assertLessThan(strpos($html, 'class="variant-grid"'), $form, 'nằm trên các thẻ phiên bản');
        $this->assertGreaterThan(strpos($html, 'id="versions"'), $form, 'nằm trong khối giá, ngay dưới đầu trang');

        preg_match('#<form class="quick-callback"[^>]*>.*?id="xe-phone".*?</form>#s', $html, $m);
        $this->assertStringContainsString('name="product_id" value="'.$this->rx->id.'"', $m[0] ?? '');
    }

    public function test_o_de_lai_so_gop_vao_khoi_nguoi_tu_van_mot_loi_moi_duy_nhat(): void
    {
        // UX 10/11: ô để lại số và khối chị Hà nói cùng một ý (giá lăn bánh
        // theo nơi đăng ký) — gộp làm một khối có ảnh người tư vấn cạnh ô số,
        // không chồng hai khối đẩy giá xuống.
        $html = $this->get('/san-pham/rx')->assertOk()->getContent();
        $strip = $this->strip($html);

        $this->assertStringContainsString('id="xe-phone"', $strip);
        $this->assertStringContainsString('Để lại số, Thu Hà gọi lại báo giá lăn bánh Lexus RX theo nơi đăng ký xe của bạn.', $strip);
        $this->assertStringContainsString('href="https://zalo.me/', $strip, 'vẫn còn lối nhắn Zalo cho ai thích nhắn');
        $this->assertSame(1, substr_count($html, 'class="advisor-strip"'));
        $this->assertStringNotContainsString('Cần giá lăn bánh', $html);
    }

    public function test_tat_khoi_nguoi_tu_van_thi_o_de_lai_so_van_con(): void
    {
        Setting::put('advisor_off', '1');

        $html = $this->get('/san-pham/rx')->assertOk()->getContent();

        $this->assertSame('', $this->strip($html));
        $this->assertStringContainsString('id="xe-phone"', $html);
    }

    public function test_bang_gia_cung_gop_mot_khoi(): void
    {
        \App\Models\Page::create(['slug' => 'bang-gia', 'title' => 'Bảng giá xe Lexus', 'status' => 'published']);

        $html = $this->get('/bang-gia')->assertOk()->getContent();

        $this->assertStringContainsString('class="quick-callback"', $this->strip($html));
        $this->assertSame(1, substr_count($html, 'id="quick-callback-phone"'), 'không còn ô để lại số riêng thứ hai');
    }

    public function test_khach_de_lai_so_o_trang_dong_xe_thi_lead_ghi_dung_xe(): void
    {
        $this->postJson('/gui-form/goi-lai-nhanh', [
            'phone' => '0912 345 678', 'consent' => ['1'], 'product_id' => $this->rx->id, '_rt' => time() - 30,
        ])->assertCreated()->assertJsonPath('data.new', true);

        $lead = Lead::sole();
        $this->assertSame($this->rx->id, $lead->product_id);
        $this->assertSame('goi-lai-nhanh', Form::find($lead->form_id)->key);
    }
}
