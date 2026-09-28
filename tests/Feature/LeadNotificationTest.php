<?php

namespace Tests\Feature;

use App\Mail\LeadNotification;
use App\Models\Form;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadNotificationTest extends TestCase
{
    protected function form(array $attributes = []): Form
    {
        $form = Form::create([
            'key'  => 'dat-lich-lai-thu',
            'name' => 'Đặt lịch lái thử',
            ...$attributes,
        ]);

        $form->fields()->createMany([
            ['key' => 'name', 'label' => 'Họ tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1],
            ['key' => 'phone', 'label' => 'Điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2],
        ]);

        return $form;
    }

    protected function payload(array $override = []): array
    {
        return ['form' => 'dat-lich-lai-thu', 'name' => 'Đạt', 'phone' => '0987654321', ...$override];
    }

    // --- Gửi mail ---

    public function test_gui_mail_toi_notify_emails(): void
    {
        Mail::fake();
        $this->form(['notify_emails' => ['sale@lexus.vn', 'cskh@lexus.vn']]);

        $this->postJson('/api/v1/leads', $this->payload())->assertCreated();

        Mail::assertSent(LeadNotification::class, function (LeadNotification $mail) {
            return $mail->hasTo('sale@lexus.vn') && $mail->hasTo('cskh@lexus.vn');
        });
    }

    public function test_khong_gui_mail_khi_notify_emails_trong(): void
    {
        Mail::fake();
        $this->form();

        $this->postJson('/api/v1/leads', $this->payload())->assertCreated();

        Mail::assertNothingSent();
    }

    // --- Webhook ---

    public function test_ban_webhook_kem_du_lieu_lead(): void
    {
        Http::fake();
        $this->form(['webhook_url' => 'https://hooks.example.com/lead']);

        $this->postJson('/api/v1/leads', $this->payload(['product_id' => null]))->assertCreated();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.example.com/lead'
                && $request['phone'] === '0987654321'
                && $request['name'] === 'Đạt'
                && $request['form'] === 'dat-lich-lai-thu';
        });
    }

    public function test_khong_ban_webhook_khi_url_trong(): void
    {
        Http::fake();
        $this->form();

        $this->postJson('/api/v1/leads', $this->payload())->assertCreated();

        Http::assertNothingSent();
    }

    // --- Chống spam ---

    public function test_honeypot_dien_thi_khong_tao_lead_nhung_van_201(): void
    {
        Mail::fake();
        $this->form(['notify_emails' => ['sale@lexus.vn']]);

        $this->postJson('/api/v1/leads', $this->payload(['website' => 'http://bot.spam']))
            ->assertCreated();

        $this->assertSame(0, Lead::count());
        Mail::assertNothingSent();
    }

    public function test_gui_trung_so_trong_cua_so_ngan_khong_tao_lead_moi(): void
    {
        $this->form();

        $this->postJson('/api/v1/leads', $this->payload())->assertCreated();
        $this->postJson('/api/v1/leads', $this->payload(['name' => 'Đạt lần 2']))->assertCreated();

        $this->assertSame(1, Lead::count());
    }

    public function test_gui_trung_ngoai_cua_so_thi_tao_lead_moi(): void
    {
        config(['catalog.leads.dedupe_minutes' => 5]);
        $this->form();

        $this->postJson('/api/v1/leads', $this->payload())->assertCreated();

        // Lead cũ đẩy về quá khứ, ngoài cửa sổ 5 phút
        Lead::first()->update(['created_at' => now()->subMinutes(10)]);

        $this->postJson('/api/v1/leads', $this->payload())->assertCreated();

        $this->assertSame(2, Lead::count());
    }

    public function test_dedupe_tat_khi_cau_hinh_0(): void
    {
        config(['catalog.leads.dedupe_minutes' => 0]);
        $this->form();

        $this->postJson('/api/v1/leads', $this->payload())->assertCreated();
        $this->postJson('/api/v1/leads', $this->payload())->assertCreated();

        $this->assertSame(2, Lead::count());
    }
}
