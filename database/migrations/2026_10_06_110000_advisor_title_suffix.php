<?php

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tiêu đề SEO đã lưu: đuôi "| Lexus Thăng Long" → "| Thu Hà tư vấn Lexus"
 * (đọc như website của đại lý — lý do Google Ads tạm ngưng). Tiêu đề trang
 * showroom đổi cả câu. Chỉ đổi tiêu đề còn đúng bản cũ; chạy lại không đổi.
 */
return new class extends Migration
{
    private const OLD_SUFFIX = ' | Lexus Thăng Long';

    private const NEW_SUFFIX = ' | Thu Hà tư vấn Lexus';

    private const EXACT = [
        'Showroom Lexus Thăng Long — Phạm Hùng, Cầu Giấy, Hà Nội' => 'Xem xe tại showroom Lexus Thăng Long, Cầu Giấy | Thu Hà tư vấn Lexus',
    ];

    public function up(): void
    {
        // Chỉ website của chị Thu Hà — DB khác thì bỏ qua.
        if (json_decode((string) DB::table('settings')->where('key', 'advisor_name')->value('value'), true) !== 'Thu Hà') {
            return;
        }

        foreach ([Page::query(), Product::query(), Post::query(), Category::query()] as $query) {
            $query->get()->each(fn (Model $model) => $this->rewrite($model));
        }
    }

    public function down(): void
    {
        // Không hoàn tác: đuôi cũ ngụ ý website của đại lý.
    }

    private function rewrite(Model $model): void
    {
        $seo = $model->seo;
        $title = is_array($seo) ? ($seo['title'] ?? null) : null;
        if (! is_string($title)) {
            return;
        }

        $new = self::EXACT[$title] ?? (str_ends_with($title, self::OLD_SUFFIX)
            ? substr($title, 0, -strlen(self::OLD_SUFFIX)).self::NEW_SUFFIX
            : $title);

        if ($new !== $title) {
            $seo['title'] = $new;
            $model->seo = $seo;
            $model->saveQuietly();
        }
    }
};
