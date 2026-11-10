<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SiteEvent;
use Tests\TestCase;

/**
 * Trang đích quảng cáo (05/11/2026): khách đã có đủ giá nên không còn lý do liên
 * hệ, thẻ giới thiệu chị Hà nằm tận 3.300 px trên điện thoại, nút báo giá bắt
 * điền 4 ô. Nay: ngay trên giá có ảnh, tên, kinh nghiệm và lời mời nhắn Zalo nơi
 * đăng ký xe; popup báo giá chỉ còn số điện thoại + đồng ý, xe chọn sẵn theo trang.
 */
class LandingTrustTest extends TestCase
{
    private const MIGRATION_FORMS = 'migrations/2026_11_05_090000_quote_forms_phone_only.php';

    private const MIGRATION_NOTICE = 'migrations/2026_11_05_090100_rx_notice_without_zalo_clause.php';

    private const OLD_NOTICE = 'Bạn đang tìm Lexus RX 350? Bản RX đang bán tại Lexus Thăng Long là RX 350h hybrid 2.5L '
        .'(Premium và Luxury) và RX 500h F SPORT Performance. Giá niêm yết và lăn bánh từng bản ngay bên dưới; '
        .'nhắn Zalo Thu Hà để nhận báo giá theo nơi đăng ký xe.';

    private Product $rx;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put('advisor_name', 'Thu Hà');
        Setting::put('advisor_phone', '0977573366');
        Setting::put('advisor_zalo', '0977573366');
        Setting::put('advisor_experience', 'Tư vấn Lexus tại Lexus Thăng Long từ 2017');

        $this->rx = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now(),
            'price_from' => 3_350_000_000]);
        $this->rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'sort' => 1]);

        Page::create(['slug' => 'bang-gia', 'title' => 'Bảng giá xe Lexus', 'status' => 'published']);
    }

    private function strip(string $html): string
    {
        preg_match('#<aside class="advisor-strip".*?</aside>#s', $html, $m);

        return $m[0] ?? '';
    }

    private function dialog(string $url): string
    {
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('#<dialog class="quote-dialog".*?</dialog>#s', $html, $m);

        return $m[0] ?? '';
    }

    private function quoteForm(string $key = 'nhan-bao-gia'): Form
    {
        $form = Form::firstOrCreate(['key' => $key], ['name' => $key, 'success_message' => 'Đã nhận.']);
        $form->fields()->delete();
        $form->fields()->createMany([
            ['key' => 'name', 'label' => 'Họ và tên', 'type' => 'text', 'rules' => ['required', 'max:100'], 'sort' => 1],
            ['key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2],
            ['key' => 'product_id', 'label' => 'Dòng xe quan tâm', 'type' => 'product', 'rules' => ['required'], 'sort' => 3],
            ['key' => 'consent', 'label' => 'Đồng ý', 'type' => 'checkbox', 'rules' => ['required'], 'sort' => 4, 'options' => ['1' => 'Đồng ý']],
        ]);

        return $form;
    }

    private function migrateForms(): void
    {
        (require database_path(self::MIGRATION_FORMS))->up();
    }

    public function test_trang_xe_co_nguoi_tu_van_ngay_tren_gia(): void
    {
        $html = $this->get('/san-pham/rx')->assertOk()->getContent();
        $strip = $this->strip($html);

        $this->assertStringContainsString('Thu Hà', $strip);
        $this->assertStringContainsString('Tư vấn Lexus tại Lexus Thăng Long từ 2017', $strip);
        $this->assertStringContainsString('avatar', $strip);
        $this->assertLessThan(strpos($html, 'class="variant-grid"'), strpos($html, 'class="advisor-strip"'));
    }

    public function test_bang_gia_co_nguoi_tu_van_ngay_tren_bang(): void
    {
        $html = $this->get('/bang-gia')->assertOk()->getContent();

        $this->assertNotSame('', $this->strip($html));
        $this->assertLessThan(strpos($html, '<table'), strpos($html, 'class="advisor-strip"'));
    }

    public function test_loi_moi_nhan_zalo_noi_dang_ky_xe(): void
    {
        $strip = $this->strip($this->get('/san-pham/rx')->getContent());

        $this->assertStringContainsString('nơi đăng ký xe', $strip);
        $this->assertStringContainsString('href="https://zalo.me/0977573366"', $strip);
        $this->assertStringContainsString('data-zalo-tail=', $strip);
        $this->assertStringContainsString('zaloTail', file_get_contents(public_path('assets/lead.js')));
    }

    public function test_chua_cai_zalo_thi_loi_moi_mo_form_bao_gia(): void
    {
        Setting::put('advisor_zalo', '');
        Setting::put('zalo', '');

        // Có form gọi lại nhanh thì ô số nằm ngay trong khối; không thì mở form báo giá.
        $strip = $this->strip($this->get('/san-pham/rx')->getContent());
        $this->assertTrue(str_contains($strip, 'data-quote') || str_contains($strip, 'class="quick-callback"'));
        $this->assertStringNotContainsString('zalo.me', $strip);
    }

    public function test_tat_nguoi_tu_van_thi_khong_hien(): void
    {
        Setting::put('advisor_off', '1');

        $this->assertSame('', $this->strip($this->get('/san-pham/rx')->getContent()));
    }

    public function test_luot_bam_tu_khoi_nay_ghi_vi_tri_rieng(): void
    {
        $this->postJson('/api/v1/events', ['events' => [['type' => 'zalo', 'path' => '/san-pham/rx', 'place' => 'strip', 'secs' => 20]]],
            ['User-Agent' => 'Mozilla/5.0 (iPhone) Safari/605'])->assertNoContent();

        $this->assertSame('strip', SiteEvent::sole()->place);
        $this->assertStringContainsString("['.advisor-strip', 'strip']", file_get_contents(public_path('assets/insight.js')));
    }

    public function test_popup_bao_gia_chi_hoi_so_dien_thoai_va_dong_y(): void
    {
        $this->quoteForm();

        $dialog = $this->dialog('/san-pham/rx');

        $this->assertStringNotContainsString('name="name"', $dialog);
        $this->assertStringContainsString('name="phone"', $dialog);
        $this->assertStringContainsString('name="consent[]"', $dialog);
        $this->assertStringContainsString('data-product-select', $dialog, 'vẫn còn ô xe để JS chọn sẵn');
        $this->assertDoesNotMatchRegularExpression('#<select[^>]*name="product_id"[^>]*required#s', $dialog);
    }

    public function test_trang_bao_gia_rieng_van_giu_form_day_du(): void
    {
        $this->quoteForm();

        $this->get('/bao-gia')->assertOk()->assertSee('name="name"', false);
    }

    public function test_migration_chi_bo_bat_buoc_ten_va_xe_o_hai_form_bao_gia(): void
    {
        $quote = $this->quoteForm();
        $popup = $this->quoteForm('popup-bao-gia');
        $driveTest = $this->quoteForm('dang-ky-lai-thu');

        $this->migrateForms();
        $this->migrateForms();

        foreach ([$quote, $popup] as $form) {
            $this->assertSame(['nullable', 'max:100'], $form->fields()->where('key', 'name')->value('rules'));
            $this->assertSame(['nullable'], $form->fields()->where('key', 'product_id')->value('rules'));
            $this->assertSame(['required'], $form->fields()->where('key', 'phone')->value('rules'));
        }
        $this->assertSame(['required', 'max:100'], $driveTest->fields()->where('key', 'name')->value('rules'));
    }

    public function test_migration_khong_de_luat_da_sua_tay(): void
    {
        $form = $this->quoteForm();
        $form->fields()->where('key', 'name')->update(['rules' => ['required', 'max:60']]);

        $this->migrateForms();

        $this->assertSame(['required', 'max:60'], $form->fields()->where('key', 'name')->value('rules'));
    }

    public function test_khach_chi_de_lai_so_van_thanh_lead_kem_xe_cua_trang(): void
    {
        $this->quoteForm();
        $this->migrateForms();

        $this->postJson('/gui-form/nhan-bao-gia', ['phone' => '098 765 4321', 'product_id' => $this->rx->id, 'consent' => ['1']])
            ->assertCreated();

        $lead = Lead::sole();
        $this->assertSame('0987654321', $lead->phone);
        $this->assertSame($this->rx->id, $lead->product_id);
    }

    public function test_khach_khong_chon_xe_van_gui_duoc(): void
    {
        $this->quoteForm();
        $this->migrateForms();

        $this->postJson('/gui-form/nhan-bao-gia', ['phone' => '0987654321', 'product_id' => '', 'consent' => ['1']])
            ->assertCreated();

        $this->assertNull(Lead::sole()->product_id);
    }

    public function test_van_bat_buoc_so_dien_thoai_va_dong_y(): void
    {
        $this->quoteForm();
        $this->migrateForms();

        $this->postJson('/gui-form/nhan-bao-gia', ['product_id' => $this->rx->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'consent']);
    }

    public function test_seeder_de_ten_va_xe_khong_bat_buoc_o_form_bao_gia(): void
    {
        $seeder = file_get_contents(database_path('seeders/LexusSiteSeeder.php'));

        $this->assertStringContainsString("['nhan-bao-gia', 'popup-bao-gia']", $seeder);
    }

    public function test_ghi_chu_rx_bo_doan_nhan_zalo_trung_voi_khoi_moi(): void
    {
        $this->rx->update(['notice' => self::OLD_NOTICE]);

        (require database_path(self::MIGRATION_NOTICE))->up();
        (require database_path(self::MIGRATION_NOTICE))->up();

        $notice = $this->rx->fresh()->notice;
        $this->assertStringContainsString('RX 350h', $notice);
        $this->assertStringNotContainsString('Zalo', $notice);
    }

    public function test_ghi_chu_rx_da_sua_tay_thi_giu_nguyen(): void
    {
        $this->rx->update(['notice' => 'Chị Hà tự viết, nhắn Zalo nhé.']);

        (require database_path(self::MIGRATION_NOTICE))->up();

        $this->assertSame('Chị Hà tự viết, nhắn Zalo nhé.', $this->rx->fresh()->notice);
    }
}
