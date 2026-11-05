<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;

/**
 * Người dùng xác nhận 05/11/2026: giá ES, RX, LX, LM, LS giữ nguyên trong tháng
 * 11 (giá GX đổi ở migration 2026_11_05_100000). Bài do Gemini viết trên máy chủ
 * còn ghi tháng 10/2026 hoặc thời điểm tháng 9/2026: đổi sang tháng 11, không
 * đụng số giá, đường dẫn, ngày đăng. Chạy lại không đổi thêm.
 */
return new class extends Migration
{
    // Câu dài trước: câu "thời điểm" chứa cả tháng 9 lẫn tháng 10.
    private const TEXT = [
        'tại thời điểm tháng 9/2026 cho giai đoạn tháng 10/2026' => 'tại thời điểm tháng 11/2026',
        'tại thời điểm viết bài (tháng 9/2026)' => 'tại thời điểm cập nhật (tháng 11/2026)',
        'vào thời điểm tháng 9/2026'            => 'vào thời điểm tháng 11/2026',
        'hiện hành cập nhật ngày 02/10/2026'    => 'hiện hành cập nhật ngày 05/11/2026',
        'hiện hành cập nhật ngày 26/10/2026'    => 'hiện hành cập nhật ngày 05/11/2026',
        'chính thức cập nhật ngày 26/10/2026'   => 'chính thức cập nhật ngày 05/11/2026',
        'tháng 10/2026'                         => 'tháng 11/2026',
    ];

    private const FIELDS = ['title', 'excerpt', 'sections', 'seo', 'share_kit'];

    public function up(): void
    {
        Post::query()->get()->each(function (Post $post) {
            foreach (self::FIELDS as $field) {
                if (! array_key_exists($field, $post->getAttributes())) {
                    continue;
                }
                $old = $post->getAttribute($field);
                if ($old !== null && ($new = $this->walk($old)) !== $old) {
                    $post->setAttribute($field, $new);
                }
            }

            if ($post->isDirty()) {
                $post->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        // Không hoàn tác: giá tháng 11 do người dùng xác nhận.
    }

    private function walk(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->walk($v), $value);
        }

        return is_string($value) ? strtr($value, self::TEXT) : $value;
    }
};
