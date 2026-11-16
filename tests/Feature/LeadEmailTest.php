<?php

namespace Tests\Feature;

use App\Mail\LeadNotification;
use App\Models\Form;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Báo khách mới qua mail SMTP (Slack không kêu trên điện thoại). Địa chỉ trong
 * LEAD_NOTIFY_EMAILS luôn nhận — không phụ thuộc form đã seed hay chưa, vì trên
 * VPS không chạy lại seeder. Nhiều web dùng chung một hộp thư nên tiêu đề phải
 * ghi rõ web nào.
 */
class LeadEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $form = Form::create(['key' => 'nhan-bao-gia', 'name' => 'Nhận báo giá']);
        $form->fields()->createMany([
            ['key' => 'name', 'label' => 'Họ tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1],
            ['key' => 'phone', 'label' => 'Điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2],
            ['key' => 'note', 'label' => 'Ghi chú', 'type' => 'text', 'rules' => [], 'sort' => 3],
        ]);
    }

    private function send(): void
    {
        $this->post('/gui-form/nhan-bao-gia', ['name' => 'Anh Nam', 'phone' => '0987 654 321', 'note' => 'Hỏi màu trắng']);
    }

    public function test_dia_chi_trong_env_luon_nhan_mail_du_form_chua_ghi_notify_emails(): void
    {
        Mail::fake();
        config(['catalog.leads.notify_emails' => ['toi@example.com']]);

        $this->send();

        Mail::assertSent(LeadNotification::class, fn ($mail) => $mail->hasTo('toi@example.com'));
    }

    public function test_tieu_de_ghi_ro_web_ten_va_so_khach(): void
    {
        Mail::fake();
        config(['catalog.leads.notify_emails' => ['toi@example.com'], 'catalog.leads.site_label' => 'ES350h Lexus Thăng Long']);

        $this->send();

        Mail::assertSent(LeadNotification::class, function ($mail) {
            $subject = $mail->envelope()->subject;

            return str_starts_with($subject, '[ES350h Lexus Thăng Long]')
                && str_contains($subject, 'Anh Nam')
                && str_contains($subject, '0987654321');
        });
    }

    public function test_noi_dung_mail_co_web_va_loi_khach_ghi(): void
    {
        Mail::fake();
        config(['catalog.leads.notify_emails' => ['toi@example.com'], 'catalog.leads.site_label' => 'ES350h Lexus Thăng Long']);

        $this->send();

        Mail::assertSent(LeadNotification::class, function ($mail) {
            $html = $mail->render();

            return str_contains($html, 'ES350h Lexus Thăng Long')
                && str_contains($html, 'Hỏi màu trắng')
                && str_contains($html, 'tel:0987654321');
        });
    }

    public function test_khong_gui_trung_khi_dia_chi_vua_o_env_vua_o_form(): void
    {
        Mail::fake();
        config(['catalog.leads.notify_emails' => ['toi@example.com']]);
        Form::where('key', 'nhan-bao-gia')->update(['notify_emails' => json_encode(['toi@example.com'])]);

        $this->send();

        Mail::assertSentCount(1);
    }

    public function test_chua_cau_hinh_email_thi_khong_gui(): void
    {
        Mail::fake();
        config(['catalog.leads.notify_emails' => []]);

        $this->send();

        Mail::assertNothingOutgoing();
    }
}
