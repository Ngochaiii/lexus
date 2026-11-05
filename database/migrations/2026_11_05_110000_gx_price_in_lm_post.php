<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;

/**
 * Bài giá LM viết "Lexus GX 550:" trong thẻ <strong> nên lọt khỏi migration
 * 2026_11_05_100000: còn ghi GX 6,45 tỷ là giá duy nhất. Chỉ thay khi còn đúng
 * bản cũ; chạy lại không đổi thêm.
 */
return new class extends Migration
{
    private const OLD = 'Lexus GX 550:</strong> Giá niêm yết 6.450.000.000 đ (lăn bánh Hà Nội tạm tính khoảng 7,24 tỷ đồng)';

    private const NEW = 'Lexus GX 550:</strong> Giá niêm yết từ 6.400.000.000 đ (lăn bánh Hà Nội tạm tính từ khoảng 7,18 tỷ đồng)';

    public function up(): void
    {
        $walk = function ($value) use (&$walk) {
            if (is_array($value)) {
                return array_map($walk, $value);
            }

            return is_string($value) ? str_replace(self::OLD, self::NEW, $value) : $value;
        };

        Post::query()->get()->each(function (Post $post) use ($walk) {
            if ($post->sections && ($new = $walk($post->sections)) !== $post->sections) {
                $post->forceFill(['sections' => $new])->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        // Không hoàn tác: giá do người dùng xác nhận.
    }
};
