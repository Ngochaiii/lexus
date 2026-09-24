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

        $field = DB::table('form_fields')
            ->where('form_id', $formId)
            ->where('key', 'agree')
            ->first(['id', 'options']);

        if (! $field || filled($field->options)) {
            return;
        }

        DB::table('form_fields')
            ->where('id', $field->id)
            ->update([
                'options' => json_encode([
                    '1' => 'Tôi đồng ý cho đại lý xử lý dữ liệu cá nhân của tôi theo '
                        .'[Chính sách bảo vệ dữ liệu cá nhân](/chinh-sach-bao-mat).',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Chỉ phục hồi dữ liệu bị thiếu; rollback không xoá nội dung hợp lệ.
    }
};
