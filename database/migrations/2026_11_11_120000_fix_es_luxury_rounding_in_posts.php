<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;

/**
 * Kiểm tra site thật 11/11/2026 sau migration 2026_11_11_100000: ES 350h Luxury
 * (lăn bánh 2.926.000.000 đ) còn ghi "khoảng 2,9 tỷ" ở 3 bài Gemini khác, trong
 * khi bản Premium ghi "2,63 tỷ". Đổi thành "khoảng 2,93 tỷ" cho đều. Mọi chỗ
 * "khoảng 2,9 tỷ" trong 3 bài này đều nói bản Luxury (đã xem trên site thật).
 * Chỉ thay đúng chữ cũ trong đúng bài; chạy lại không đổi thêm.
 */
return new class extends Migration
{
    private const SLUGS = ['gia-xe-lexus-es350h', 'gia-lan-banh-lexus-es-500e', 'lexus-es2026-bang-gia-va-gia-lan-banh'];

    private const MAP = ['(khoảng 2,9 tỷ)' => '(khoảng 2,93 tỷ)', 'khoảng 2,9 tỷ đồng' => 'khoảng 2,93 tỷ đồng'];

    public function up(): void
    {
        $walk = function ($value) use (&$walk) {
            if (is_array($value)) {
                return array_map($walk, $value);
            }

            return is_string($value) ? strtr($value, self::MAP) : $value;
        };

        Post::withTrashed()->whereIn('slug', self::SLUGS)->get()->each(function (Post $post) use ($walk) {
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
        // Không hoàn tác: làm tròn đúng giá trên trang xe.
    }
};
