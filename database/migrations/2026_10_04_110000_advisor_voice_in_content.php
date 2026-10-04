<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;

/**
 * Nội dung nói bằng lời chuyên viên Thu Hà, không bằng lời đại lý.
 *
 * Rà toàn site 04/10/2026: bài viết (nhiều bài do Gemini viết trên máy chủ)
 * xưng "chúng tôi" như đại lý và gọi số di động của chị Hà là "Hotline" —
 * Google Ads coi là ngụ ý website của đại lý. Thay đúng các cụm đó trong
 * tiêu đề, tóm tắt, SEO và mọi khối nội dung; chạy lại không đổi thêm.
 */
return new class extends Migration
{
    /** Thứ tự quan trọng: cụm dài trước, cụm ngắn sau. */
    private const RULES = [
        '/Hotline tư vấn:\s*([\d][\d .]*\d)\s*\([Cc]huyên viên ([^)]+)\)/u' => 'Điện thoại chuyên viên $2: $1',
        '/Hotline chuyên viên tư vấn:/u' => 'Điện thoại chuyên viên tư vấn:',
        '/Hotline tư vấn:/u' => 'Điện thoại chuyên viên:',
        '/Hotline ([\d][\d .]*\d) \((?![Cc]huyên viên)([^)]+)\)/u' => 'Điện thoại $1 (chuyên viên $2)',
        '/\b(gọi|qua|liên hệ) hotline (?=\d)/iu' => '$1 số ',
        '/qua hotline hoặc/iu' => 'qua điện thoại, Zalo hoặc',
        '/Hotline (?=\d)/u' => 'Điện thoại ',
        '/hotline (?=\d)/u' => 'số ',
        '/ghé thăm showroom chính hãng của chúng tôi/u' => 'hẹn chuyên viên Thu Hà để ghé xem xe tại showroom Lexus Thăng Long',
        '/(nhận báo giá lăn bánh(?:<\/a>)?)\s+của chúng tôi/u' => '$1 trên website',
        '/nhận phản hồi nhanh chóng từ chúng tôi/u' => 'được Thu Hà phản hồi nhanh chóng',
        '/cách chúng tôi mang chúng đến với bạn/u' => 'cách chuyên viên Thu Hà mang chúng đến với bạn',
        '/Thông tin liên hệ đại lý chính hãng:/u' => 'Liên hệ chuyên viên Thu Hà tại Lexus Thăng Long:',
    ];

    public function up(): void
    {
        foreach ([Post::query(), Page::query(), Product::query()] as $query) {
            $query->get()->each(fn (Model $model) => $this->rewrite($model));
        }
    }

    public function down(): void
    {
        // Không hoàn tác: câu cũ là lỗi nội dung.
    }

    private function rewrite(Model $model): void
    {
        $walk = function ($value) use (&$walk) {
            if (is_array($value)) {
                return array_map($walk, $value);
            }
            if (! is_string($value)) {
                return $value;
            }
            // Nhãn ô liên hệ trong bảng/hỏi đáp: "Hotline" → số của chuyên viên.
            if ($value === 'Hotline') {
                return 'Điện thoại chuyên viên';
            }

            return preg_replace(array_keys(self::RULES), array_values(self::RULES), $value);
        };

        foreach (['title', 'excerpt', 'tagline', 'sections', 'seo'] as $field) {
            if (! array_key_exists($field, $model->getAttributes())) {
                continue;
            }
            $old = $model->getAttribute($field);
            if ($old !== null && ($new = $walk($old)) !== $old) {
                $model->setAttribute($field, $new);
            }
        }

        if ($model->isDirty()) {
            $model->saveQuietly();
        }
    }
};
