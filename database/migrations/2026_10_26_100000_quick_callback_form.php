<?php

use App\Models\Form;
use Illuminate\Database\Migrations\Migration;

/**
 * Form "để lại số" một ô trên /bang-gia (26/10/2026): chỉ số điện thoại + ô
 * đồng ý. Email nhận thông báo lấy theo form "Nhận báo giá" để chị Hà nhận
 * mail như các form khác. Đã có form thì không đụng (admin có thể đã sửa).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Form::query()->where('key', 'goi-lai-nhanh')->exists()) {
            return;
        }

        $form = Form::create([
            'key'             => 'goi-lai-nhanh',
            'name'            => 'Để lại số (bảng giá)',
            'description'     => 'Form một ô số điện thoại trên trang bảng giá.',
            'success_message' => 'Đã nhận số của bạn. Thu Hà sẽ gọi lại báo giá lăn bánh sớm nhất.',
            'notify_emails'   => Form::query()->where('key', 'nhan-bao-gia')->first()?->notify_emails,
        ]);

        $form->fields()->createMany([
            ['key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 1],
            ['key' => 'consent', 'label' => 'Đồng ý chính sách quyền riêng tư', 'type' => 'checkbox',
                'rules' => ['required'], 'sort' => 2,
                'options' => ['1' => 'Tôi đã đọc và đồng ý với [chính sách quyền riêng tư](/quyen-rieng-tu).']],
        ]);
    }

    public function down(): void
    {
        // Không xoá: lead đã gửi qua form này phải giữ.
    }
};
