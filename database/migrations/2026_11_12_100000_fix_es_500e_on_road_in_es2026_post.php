<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;

/**
 * Kiểm tra site thật 12/11/2026: bài lexus-es2026-bang-gia-va-gia-lan-banh vẫn
 * ghi lăn bánh ES 500e "2.994.000.000 đ" (bảng giá và một đoạn văn) — đúng là
 * 2.954.000.000 đ (2,94 tỷ + biển số 14 triệu, miễn trước bạ), cùng lỗi đã sửa
 * ở bài gia-lan-banh-lexus-es-500e. Chỉ thay đúng chữ cũ trong đúng bài; chạy
 * lại không đổi thêm.
 */
return new class extends Migration
{
    private const MAP = ['2.994.000.000' => '2.954.000.000'];

    public function up(): void
    {
        $walk = function ($value) use (&$walk) {
            if (is_array($value)) {
                return array_map($walk, $value);
            }

            return is_string($value) ? strtr($value, self::MAP) : $value;
        };

        Post::withTrashed()->where('slug', 'lexus-es2026-bang-gia-va-gia-lan-banh')->get()->each(function (Post $post) use ($walk) {
            $changes = [];
            foreach (['title', 'excerpt', 'seo', 'sections', 'share_kit'] as $field) {
                if ($post->{$field} !== null && ($new = $walk($post->{$field})) !== $post->{$field}) {
                    $changes[$field] = $new;
                }
            }
            if ($changes) {
                $post->forceFill($changes)->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        // Không hoàn tác: sửa số sai về đúng giá trên trang xe.
    }
};
