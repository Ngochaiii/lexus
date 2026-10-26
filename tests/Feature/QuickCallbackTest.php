<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Tests\TestCase;

/**
 * Form "để lại số" một ô trên /bang-gia (26/10/2026). Bấm Gọi/Zalo không chắc
 * thành cuộc gọi (4 lượt bấm, chị Hà không nhận được gì); số khách để lại thì
 * nằm trên máy chủ, không thất lạc. Chỉ hỏi số điện thoại — không tên, không
 * chọn xe — để khách đang xem giá gửi được trong vài giây.
 */
class QuickCallbackTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_10_26_100000_quick_callback_form.php';

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put('advisor_name', 'Thu Hà');
        Setting::put('advisor_phone', '0934846666');

        $rx = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now(),
            'price_from' => 3_350_000_000]);
        $rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'sort' => 1]);

        Page::create(['slug' => 'bang-gia', 'title' => 'Bảng giá xe Lexus', 'status' => 'published']);
    }

    private function migrate(): void
    {
        (require database_path(self::MIGRATION))->up();
    }

    private function callbackForm(): string
    {
        $html = $this->get('/bang-gia')->assertOk()->getContent();
        preg_match('#<form class="quick-callback".*?</form>#s', $html, $m);

        return $m[0] ?? '';
    }

    public function test_migration_tao_form_chi_hoi_so_dien_thoai_va_dong_y(): void
    {
        $this->migrate();
        $this->migrate();

        $form = Form::where('key', 'goi-lai-nhanh')->sole();
        $this->assertSame(['phone', 'consent'], $form->fields->pluck('key')->all());
    }

    public function test_migration_lay_email_nhan_thong_bao_cua_form_bao_gia(): void
    {
        Form::where('key', 'goi-lai-nhanh')->delete();
        Form::create(['key' => 'nhan-bao-gia', 'name' => 'Nhận báo giá', 'notify_emails' => ['ha@example.com']]);

        $this->migrate();

        $this->assertSame(['ha@example.com'], Form::where('key', 'goi-lai-nhanh')->value('notify_emails'));
    }

    public function test_bang_gia_co_form_mot_o_so_dien_thoai_tren_bang(): void
    {
        $this->migrate();

        $form = $this->callbackForm();
        $this->assertStringContainsString('action="'.route('leads.store', 'goi-lai-nhanh').'"', $form);
        $this->assertStringContainsString('name="phone"', $form);
        $this->assertStringNotContainsString('name="name"', $form);
        $this->assertStringNotContainsString('name="product_id"', $form);

        $html = $this->get('/bang-gia')->getContent();
        $this->assertLessThan(strpos($html, '<table'), strpos($html, 'class="quick-callback"'), 'form nằm trên bảng giá');
    }

    public function test_khach_de_lai_so_thanh_lead(): void
    {
        $this->migrate();

        $this->post(route('leads.store', 'goi-lai-nhanh'), ['phone' => '098 765 4321', 'consent' => ['1']])
            ->assertRedirect();

        $lead = Lead::sole();
        $this->assertSame('0987654321', $lead->phone);
        $this->assertSame('goi-lai-nhanh', $lead->form->key);
    }

    public function test_chua_co_form_thi_khong_hien(): void
    {
        Form::where('key', 'goi-lai-nhanh')->delete();

        $this->assertSame('', $this->callbackForm());
    }

    public function test_seeder_tao_form_goi_lai_nhanh(): void
    {
        $this->assertStringContainsString("'goi-lai-nhanh'", file_get_contents(database_path('seeders/LexusSiteSeeder.php')));
    }
}
