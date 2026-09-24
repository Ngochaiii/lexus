<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Setting;
use Tests\TestCase;

/**
 * Nút "Báo giá", trang /bao-gia và popup tự bật.
 *
 * Lỗi gốc từng gặp: mọi nút "Báo giá" trỏ vào một trang tĩnh rỗng — không có
 * form nào để gửi. Và form gửi tên xe dạng chữ nên cột "Dòng xe" trong danh
 * sách Lead ở admin luôn trống.
 */
class LeadCaptureTest extends TestCase
{
    private Product $rx;

    private Product $es;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'site_name'], ['value' => 'Lexus Thăng Long']);

        $this->rx = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now(), 'sort' => 1]);
        $this->es = Product::create(['name' => 'Lexus ES', 'slug' => 'es', 'status' => 'published', 'published_at' => now(), 'sort' => 2]);

        // Hai form như LexusSiteSeeder: cùng trường, khác khoá để admin tách nguồn.
        foreach (['nhan-bao-gia' => 'Nhận báo giá', 'popup-bao-gia' => 'Popup trang chủ'] as $key => $name) {
            Form::create(['key' => $key, 'name' => $name, 'success_message' => 'Đã nhận yêu cầu của bạn.'])
                ->fields()->createMany([
                    ['key' => 'name', 'label' => 'Họ và tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1],
                    ['key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2],
                    ['key' => 'product_id', 'label' => 'Dòng xe', 'type' => 'product', 'rules' => ['required'], 'sort' => 3],
                    ['key' => 'consent', 'label' => 'Đồng ý', 'type' => 'checkbox', 'rules' => ['required'], 'sort' => 4,
                        'options' => ['1' => 'Đồng ý']],
                ]);
        }
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nguyễn Khách Thử',
            'phone' => '0912 345 678',
            'product_id' => $this->es->id,
            'consent' => ['1'],
        ], $overrides);
    }

    public function test_moi_nut_bao_gia_tro_toi_trang_co_form_chu_khong_phai_trang_rong(): void
    {
        $html = $this->get('/san-pham/rx')->assertOk()->getContent();

        preg_match_all('/<a [^>]*data-quote[^>]*>/', $html, $links);

        $this->assertNotEmpty($links[0], 'trang chi tiết xe phải có nút báo giá');
        foreach ($links[0] as $link) {
            $this->assertStringContainsString('/bao-gia', $link);
        }

        // Nút ở trang xe mang sẵn id xe để popup chọn đúng.
        $this->assertStringContainsString('data-product="'.$this->rx->id.'"', $html);
    }

    public function test_trang_bao_gia_co_form_va_chon_san_xe_theo_query(): void
    {
        $html = $this->get('/bao-gia?xe=es')->assertOk()->getContent();

        $this->assertStringContainsString('name="product_id"', $html);
        $this->assertMatchesRegularExpression(
            '/<option value="'.$this->es->id.'"[^>]*selected/s',
            $html,
            '?xe=es phải chọn sẵn Lexus ES'
        );
    }

    public function test_gui_bao_gia_luu_lead_kem_dong_xe_de_admin_thay(): void
    {
        $this->from('/bao-gia')->post('/gui-form/nhan-bao-gia', $this->payload())
            ->assertRedirect('/bao-gia')
            ->assertSessionHas('lead_success');

        $lead = Lead::sole();
        $this->assertSame('Nguyễn Khách Thử', $lead->name);
        $this->assertSame('0912345678', $lead->phone);          // đã chuẩn hoá
        $this->assertSame($this->es->id, $lead->product_id);    // cột "Dòng xe" trong admin có dữ liệu
        $this->assertSame('nhan-bao-gia', $lead->form->key);
    }

    public function test_gui_bang_js_nhan_json_de_popup_khong_tai_lai_trang(): void
    {
        $this->postJson('/gui-form/nhan-bao-gia', $this->payload())
            ->assertCreated()
            ->assertJsonPath('message', 'Đã nhận yêu cầu của bạn.');

        $this->postJson('/gui-form/nhan-bao-gia', $this->payload(['phone' => '123 456 789', 'name' => 'Khác']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_xe_khong_ton_tai_hoac_chua_dang_thi_bi_tu_choi(): void
    {
        $draft = Product::create(['name' => 'Xe nháp', 'slug' => 'nhap', 'status' => 'draft']);

        $this->postJson('/gui-form/nhan-bao-gia', $this->payload(['product_id' => $draft->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_popup_gui_vao_form_rieng_de_admin_tach_nguon(): void
    {
        $this->postJson('/gui-form/popup-bao-gia', $this->payload())->assertCreated();

        $this->assertSame('Popup trang chủ', Lead::sole()->form->name);
    }

    public function test_trang_chu_tu_bat_popup_theo_config_con_trang_khac_thi_khong(): void
    {
        config(['catalog.frontend.popup.delay' => 20, 'catalog.frontend.popup.mobile' => false]);

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('id="quote-dialog"', $home);
        $this->assertStringContainsString('data-auto-delay="20"', $home);
        $this->assertStringContainsString('data-auto-mobile="0"', $home);
        $this->assertStringContainsString('data-auto-action="'.route('leads.store', 'popup-bao-gia').'"', $home);

        // Trang xe: popup có để nút mở, nhưng KHÔNG tự bật.
        $product = $this->get('/san-pham/rx')->assertOk()->getContent();
        $this->assertStringContainsString('id="quote-dialog"', $product);
        $this->assertStringNotContainsString('data-auto-delay', $product);

        // Trang /bao-gia đã có form sẵn — không nhúng thêm popup.
        $this->assertStringNotContainsString('id="quote-dialog"', $this->get('/bao-gia')->getContent());
    }

    public function test_tat_popup_trong_config_thi_trang_chu_khong_tu_bat(): void
    {
        config(['catalog.frontend.popup.enabled' => false]);

        $this->get('/')->assertOk()->assertDontSee('data-auto-delay', false);
    }

    public function test_trang_co_hai_form_khong_bi_trung_id(): void
    {
        config(['catalog.frontend.product_forms' => ['nhan-bao-gia']]);

        $html = $this->get('/san-pham/rx')->assertOk()->getContent();
        preg_match_all('/\sid="([^"]+)"/', $html, $m);

        $duplicates = array_keys(array_filter(array_count_values($m[1]), fn ($n) => $n > 1));
        $this->assertSame([], $duplicates, 'id trùng làm nhãn <label for> trỏ nhầm ô');
    }

    public function test_danh_sach_xe_phan_trang_khong_lam_vo_popup(): void
    {
        // Lỗi từng gặp: @include kế thừa $products (paginator) của trang cha,
        // popup đem paginator đi collect() → số nguyên → getKey() vỡ trang.
        config(['catalog.frontend.per_page' => 1]);

        $this->get('/san-pham')->assertOk()->assertSee('id="quote-dialog"', false);
    }

    public function test_lead_ghi_dung_phien_ban_khach_chon(): void
    {
        $luxury = $this->es->variants()->create(['name' => 'ES 350h Luxury', 'price' => 2_580_000_000, 'sort' => 2]);

        $this->postJson('/gui-form/nhan-bao-gia', $this->payload(['variant_id' => $luxury->id]))->assertCreated();

        $lead = Lead::sole();
        $this->assertSame($luxury->id, $lead->product_variant_id);   // cột "Phiên bản" trong admin
        $this->assertSame($this->es->id, $lead->product_id);
        $this->assertSame('ES 350h Luxury', $lead->data['variant']);  // hiện trong mail/webhook
    }

    public function test_phien_ban_khong_thuoc_dong_xe_da_chon_thi_bo_qua(): void
    {
        // Khách bấm thẻ RX rồi đổi dòng xe sang ES: ô ẩn còn id phiên bản RX.
        $rxVariant = $this->rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'sort' => 1]);

        $this->postJson('/gui-form/nhan-bao-gia', $this->payload(['variant_id' => $rxVariant->id]))->assertCreated();

        $lead = Lead::sole();
        $this->assertNull($lead->product_variant_id);
        $this->assertSame($this->es->id, $lead->product_id);
    }

    public function test_trang_bao_gia_chon_san_phien_ban_theo_query(): void
    {
        $luxury = $this->es->variants()->create(['name' => 'ES 350h Luxury', 'price' => 2_580_000_000, 'sort' => 2]);

        $html = $this->get('/bao-gia?xe=es&phien-ban='.$luxury->id)->assertOk()->getContent();

        $this->assertStringContainsString('name="variant_id" value="'.$luxury->id.'"', $html);
        $this->assertStringContainsString('ES 350h Luxury', $html);
        $this->assertMatchesRegularExpression('/<option value="'.$this->es->id.'"[^>]*selected/s', $html);
    }

    public function test_trang_chu_hien_tung_phien_ban_kem_nut_bao_gia_rieng(): void
    {
        $premium = $this->rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'sort' => 1]);
        $this->rx->variants()->create(['name' => 'RX 350h Luxury', 'price' => 4_140_000_000, 'sort' => 2]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'class="model-card variant-card"'));
        $this->assertStringContainsString('data-variant="'.$premium->id.'" data-variant-name="RX 350h Premium"', $html);
        // Tên tách "Lexus" (dòng nhỏ) + tên phiên bản (chữ lớn) — so theo chữ hiển thị.
        $this->assertStringContainsString('Lexus RX 350h Luxury', preg_replace('/\s+/', ' ', strip_tags($html)));
    }

    public function test_admin_khong_con_man_hinh_form_chi_con_lead(): void
    {
        $names = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())->map->getName()->filter();

        $this->assertTrue($names->contains('filament.admin.resources.leads.index'));
        $this->assertFalse($names->contains('filament.admin.resources.forms.index'));
    }
}
