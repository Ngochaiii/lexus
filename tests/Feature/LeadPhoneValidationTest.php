<?php

namespace Tests\Feature;

use App\Events\LeadReceived;
use App\Models\Form;
use App\Models\Lead;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Trường kiểu "tel" phải là số di động/bàn Việt Nam thật: 0 + 9 chữ số
 * (hoặc +84 thay cho số 0 đầu). Ngày 18/9/2026 có người gõ "7365435505"
 * lọt vào bảng Liên hệ — 10 chữ số nhưng không bắt đầu bằng 0, gọi không
 * được. Test này giữ cho cửa đó đóng.
 */
class LeadPhoneValidationTest extends TestCase
{
    protected Form $form;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([LeadReceived::class]);

        $this->form = Form::create(['key' => 'dang-ky', 'name' => 'Đăng ký']);
        $this->form->fields()->createMany([
            ['key' => 'name', 'label' => 'Họ tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1],
            ['key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2],
        ]);
    }

    public function test_so_khong_bat_dau_bang_0_bi_tu_choi(): void
    {
        $this->postJson('/gui-form/dang-ky', ['name' => 'Thu', 'phone' => '7365435505'])
            ->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'Số điện thoại không đúng định dạng.');

        $this->assertSame(0, Lead::count());
    }

    public function test_so_qua_ngan_hoac_qua_dai_bi_tu_choi(): void
    {
        foreach (['098765432', '09876543210', 'abc0987654'] as $bad) {
            $this->postJson('/gui-form/dang-ky', ['name' => 'Thu', 'phone' => $bad])
                ->assertStatus(422)
                ->assertJsonValidationErrors('phone');
        }

        $this->assertSame(0, Lead::count());
    }

    #[DataProvider('soHopLe')]
    public function test_so_hop_le_duoc_chuan_hoa_ve_0xxxxxxxxx(string $input, string $expected): void
    {
        $this->postJson('/gui-form/dang-ky', ['name' => 'Thu', 'phone' => $input])
            ->assertStatus(201);

        $lead = Lead::sole();
        $this->assertSame($expected, $lead->phone);
        $this->assertSame($expected, $lead->data['phone']);
    }

    public static function soHopLe(): array
    {
        return [
            'chuẩn'            => ['0987654321', '0987654321'],
            'có dấu cách'      => ['098 765 4321', '0987654321'],
            'có dấu chấm'      => ['098.765.4321', '0987654321'],
            'có gạch ngang'    => ['098-765-4321', '0987654321'],
            '+84'              => ['+84987654321', '0987654321'],
            '84 không dấu +'   => ['84987654321', '0987654321'],
            'số bàn 0204'      => ['0204 123 456', '0204123456'],
        ];
    }
}
