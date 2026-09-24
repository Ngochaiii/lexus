<?php

namespace Tests\Feature;

use App\Events\LeadReceived;
use App\Models\Form;
use App\Models\Lead;
use App\Models\Product;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;

class LeadApiTest extends TestCase
{
    protected Form $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form = Form::create([
            'key'             => 'dat-lich-lai-thu',
            'name'            => 'Đặt lịch lái thử',
            'success_message' => 'Tư vấn viên sẽ gọi lại trong 15 phút.',
        ]);

        $this->form->fields()->createMany([
            ['key' => 'name', 'label' => 'Họ tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1],
            ['key' => 'phone', 'label' => 'Điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2],
            ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'rules' => ['nullable'], 'sort' => 3],
        ]);
    }

    public function test_form_tra_ve_field_kem_co_bat_buoc(): void
    {
        $fields = $this->getJson('/api/v1/forms/dat-lich-lai-thu')->assertOk()->json('data.fields');

        $this->assertSame(['name', 'phone', 'email'], array_column($fields, 'key'));
        $this->assertTrue($fields[0]['required']);
        $this->assertFalse($fields[2]['required']);
    }

    public function test_form_tat_thi_khong_lo_ra(): void
    {
        $this->form->update(['is_active' => false]);

        $this->getJson('/api/v1/forms/dat-lich-lai-thu')->assertNotFound();
    }

    public function test_luat_validate_dung_tu_form_fields_chu_khong_hardcode(): void
    {
        $this->postJson('/api/v1/leads', ['form' => 'dat-lich-lai-thu', 'name' => 'Đạt'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');

        // Thêm một field bắt buộc mới → API tự đòi, không sửa code
        $this->form->fields()->create([
            'key' => 'tinh', 'label' => 'Tỉnh thành', 'type' => 'text', 'rules' => ['required'], 'sort' => 4,
        ]);

        $this->postJson('/api/v1/leads', [
            'form' => 'dat-lich-lai-thu', 'name' => 'Đạt', 'phone' => '0987654321',
        ])->assertStatus(422)->assertJsonValidationErrors('tinh');
    }

    public function test_email_sai_dinh_dang_bi_chan(): void
    {
        $this->postJson('/api/v1/leads', [
            'form' => 'dat-lich-lai-thu', 'name' => 'Đạt', 'phone' => '0987654321', 'email' => 'khong-phai-email',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_luu_lead_kem_utm_referrer_va_san_pham(): void
    {
        Event::fake([LeadReceived::class]);

        $product = Product::create(['name' => 'Lexus GX 550', 'status' => 'published']);

        $this->postJson('/api/v1/leads?utm_source=facebook&utm_campaign=tet-2026', [
            'form'       => 'dat-lich-lai-thu',
            'name'       => 'Đạt',
            'phone'      => '0987654321',
            'product_id' => $product->id,
        ], ['referer' => 'https://lexus.vn/gx-550'])
            ->assertCreated()
            ->assertJsonPath('message', 'Tư vấn viên sẽ gọi lại trong 15 phút.');

        $lead = Lead::sole();

        $this->assertSame('Đạt', $lead->name);
        $this->assertSame('0987654321', $lead->phone);
        $this->assertSame($product->id, $lead->product_id);
        $this->assertSame(['utm_source' => 'facebook', 'utm_campaign' => 'tet-2026'], $lead->utm);
        $this->assertSame('https://lexus.vn/gx-550', $lead->referrer);
        $this->assertSame('new', $lead->status);

        Event::assertDispatched(LeadReceived::class);
    }

    public function test_form_khong_ton_tai_tra_404(): void
    {
        $this->postJson('/api/v1/leads', ['form' => 'khong-co-that'])->assertNotFound();
    }
}
