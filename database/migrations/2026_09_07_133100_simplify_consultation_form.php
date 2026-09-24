<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $formId = DB::table('forms')
            ->where('key', 'dang-ky-tu-van')
            ->value('id');

        if (! $formId) {
            return;
        }

        DB::table('forms')
            ->where('id', $formId)
            ->update([
                'description' => 'Chọn mẫu xe bạn quan tâm, tư vấn viên sẽ liên hệ và gửi thông tin phù hợp.',
                'updated_at' => now(),
            ]);

        DB::table('form_fields')->where('form_id', $formId)->delete();

        $now = now();

        DB::table('form_fields')->insert([
            [
                'form_id' => $formId,
                'key' => 'name',
                'label' => 'Họ và tên',
                'type' => 'text',
                'rules' => json_encode(['required'], JSON_UNESCAPED_UNICODE),
                'placeholder' => 'Nguyễn Văn A',
                'sort' => 1,
                'width' => 'half',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'form_id' => $formId,
                'key' => 'phone',
                'label' => 'Số điện thoại',
                'type' => 'tel',
                'rules' => json_encode(['required'], JSON_UNESCAPED_UNICODE),
                'placeholder' => '09xx xxx xxx',
                'sort' => 2,
                'width' => 'half',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'form_id' => $formId,
                'key' => 'product_id',
                'label' => 'Mẫu xe quan tâm',
                'type' => 'product',
                'rules' => json_encode(['required'], JSON_UNESCAPED_UNICODE),
                'placeholder' => '— Chọn mẫu xe —',
                'sort' => 3,
                'width' => 'full',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'form_id' => $formId,
                'key' => 'agree',
                'label' => 'Đồng ý xử lý dữ liệu',
                'type' => 'checkbox',
                'options' => json_encode([
                    '1' => 'Tôi đồng ý cho đại lý xử lý dữ liệu cá nhân của tôi theo '
                        .'[Chính sách bảo vệ dữ liệu cá nhân](/chinh-sach-bao-mat).',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'rules' => json_encode(['required'], JSON_UNESCAPED_UNICODE),
                'sort' => 4,
                'width' => 'full',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        // Đây là thay đổi nội dung form; lead cũ vẫn được giữ nguyên.
    }
};
