<?php

use App\Models\Form;
use Illuminate\Database\Migrations\Migration;

/**
 * 05/11/2026: popup báo giá chỉ còn số điện thoại + đồng ý, xe chọn sẵn theo
 * trang (partials/quote-dialog). Bỏ bắt buộc tên và dòng xe ở hai form popup
 * dùng. Chỉ thay khi còn đúng luật cũ; chạy lại không đổi thêm.
 */
return new class extends Migration
{
    private const CHANGES = [
        'name'       => [['required', 'max:100'], ['nullable', 'max:100']],
        'product_id' => [['required'], ['nullable']],
    ];

    public function up(): void
    {
        foreach (Form::query()->whereIn('key', ['nhan-bao-gia', 'popup-bao-gia'])->get() as $form) {
            foreach (self::CHANGES as $key => [$old, $new]) {
                $field = $form->fields()->where('key', $key)->first();
                if ($field && $field->rules === $old) {
                    $field->update(['rules' => $new]);
                }
            }
        }
    }

    public function down(): void
    {
        // Không đổi lại: luật có thể đã sửa tay sau migration này.
    }
};
