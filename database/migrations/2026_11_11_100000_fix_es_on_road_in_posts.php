<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;

/**
 * Rà bài 11/11/2026: hai bài Gemini ghi lăn bánh lệch trang xe.
 *   - ES 500e: "2.994.000.000" → "2.954.000.000" (2,94 tỷ + biển số 14 triệu,
 *     miễn trước bạ; trang xe và bài so sánh đều ghi 2,95 tỷ).
 *   - ES 350h Luxury: làm tròn "khoảng 2,9 tỷ" → "khoảng 2,93 tỷ" (2.926.000.000 đ).
 * Chỉ thay đúng chữ cũ trong đúng bài (tiêu đề, tóm tắt, SEO, các mục, bộ
 * chia sẻ); chạy lại không đổi thêm. Seeder không có hai bài này (Gemini viết
 * trên máy chủ) nên không cần sửa song song.
 */
return new class extends Migration
{
    private const FIXES = [
        'gia-lan-banh-lexus-es-500e' => ['2.994.000.000' => '2.954.000.000'],
        'gia-lan-banh-lexus-es-350h-tai-ha-noi' => ['(khoảng 2,9 tỷ)' => '(khoảng 2,93 tỷ)', 'khoảng 2,9 tỷ đồng' => 'khoảng 2,93 tỷ đồng'],
    ];

    public function up(): void
    {
        foreach (self::FIXES as $slug => $map) {
            $walk = function ($value) use (&$walk, $map) {
                if (is_array($value)) {
                    return array_map($walk, $value);
                }

                return is_string($value) ? strtr($value, $map) : $value;
            };

            Post::withTrashed()->where('slug', $slug)->get()->each(function (Post $post) use ($walk) {
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
    }

    public function down(): void
    {
        // Không hoàn tác: sửa số sai về đúng giá trên trang xe.
    }
};
