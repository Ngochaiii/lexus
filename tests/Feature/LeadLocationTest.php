<?php

namespace Tests\Feature;

use App\Events\LeadReceived;
use App\Jobs\ResolveLeadLocation;
use App\Models\Form;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sau khi nhận lead, tra IP → tỉnh/thành bằng ip-api.com (chạy nền) và ghi
 * vào cột location để nhân viên biết khách ở khu vực nào.
 */
class LeadLocationTest extends TestCase
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

    public function test_nhan_lead_thi_day_job_tra_vi_tri(): void
    {
        Queue::fake([ResolveLeadLocation::class]);

        $this->postJson('/gui-form/dang-ky', ['name' => 'Thu', 'phone' => '0987654321'])
            ->assertStatus(201);

        $lead = Lead::sole();
        Queue::assertPushed(ResolveLeadLocation::class, fn ($job) => $job->lead->is($lead));
    }

    public function test_job_ghi_tinh_thanh_tu_ip_api(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'Vietnam',
                'countryCode' => 'VN',
                'regionName' => 'Bac Giang',
                'city' => 'Bắc Giang',
            ]),
        ]);

        $lead = $this->form->leads()->create(['name' => 'Thu', 'phone' => '0987654321', 'ip' => '113.160.1.1']);

        (new ResolveLeadLocation($lead))->handle();

        $this->assertSame('Bắc Giang, Bac Giang, VN', $lead->fresh()->location);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'ip-api.com/json/113.160.1.1'));
    }

    public function test_job_bo_qua_ip_noi_bo_va_khi_api_loi(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response(['status' => 'fail', 'message' => 'private range']),
        ]);

        $local = $this->form->leads()->create(['name' => 'A', 'phone' => '0987654321', 'ip' => '127.0.0.1']);
        $failed = $this->form->leads()->create(['name' => 'B', 'phone' => '0987654322', 'ip' => '1.2.3.4']);

        (new ResolveLeadLocation($local))->handle();
        (new ResolveLeadLocation($failed))->handle();

        $this->assertNull($local->fresh()->location);
        $this->assertNull($failed->fresh()->location);
        Http::assertSentCount(1);
    }

    public function test_lenh_artisan_tra_lai_cac_lead_chua_co_vi_tri(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success', 'countryCode' => 'VN', 'regionName' => 'Hanoi', 'city' => 'Hanoi',
            ]),
        ]);

        $missing = $this->form->leads()->create(['name' => 'A', 'phone' => '0987654321', 'ip' => '1.2.3.4']);
        $done = $this->form->leads()->create(['name' => 'B', 'phone' => '0987654322', 'ip' => '1.2.3.5', 'location' => 'Bắc Giang, VN']);

        $this->artisan('leads:locate')->assertSuccessful();

        $this->assertSame('Hanoi, VN', $missing->fresh()->location);
        $this->assertSame('Bắc Giang, VN', $done->fresh()->location);
        Http::assertSentCount(1);
    }

    public function test_admin_thay_khu_vuc_trong_bang_lien_he(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'x']));

        $lead = $this->form->leads()->create([
            'name' => 'Thu', 'phone' => '0987654321', 'ip' => '113.160.1.1', 'location' => 'Bắc Giang, VN',
        ]);

        Livewire::test(ManageLeads::class)
            ->assertSuccessful()
            ->assertTableColumnExists('location')
            ->assertCanSeeTableRecords([$lead])
            ->assertSee('Bắc Giang, VN');
    }
}
