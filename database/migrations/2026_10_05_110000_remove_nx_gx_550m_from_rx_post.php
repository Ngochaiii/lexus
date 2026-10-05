<?php

use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;

/**
 * Bài "giá lăn bánh RX 350h Hà Nội" (Gemini viết trên máy chủ) có đoạn gợi ý
 * NX 350h và GX 550M — hai mẫu đại lý không bán, đã ẩn khỏi web. Bỏ đúng đoạn
 * đó; bài không còn đoạn này thì không đổi gì.
 */
return new class extends Migration
{
    private const PATTERN = '#<p>Nếu quý khách muốn cân nhắc một dòng SUV nhỏ gọn hơn(?:(?!</p>).)*?/san-pham/nx(?:(?!</p>).)*?550M(?:(?!</p>).)*</p>#su';

    public function up(): void
    {
        Post::query()->get()->each(fn (Model $post) => $this->rewrite($post));
    }

    public function down(): void
    {
        // Không hoàn tác: NX và GX 550M đại lý không bán.
    }

    private function rewrite(Model $post): void
    {
        $walk = function ($value) use (&$walk) {
            if (is_array($value)) {
                return array_map($walk, $value);
            }

            return is_string($value) ? preg_replace(self::PATTERN, '', $value) : $value;
        };

        $old = $post->getAttribute('sections');
        if ($old !== null && ($new = $walk($old)) !== $old) {
            $post->setAttribute('sections', $new);
            $post->saveQuietly();
        }
    }
};
