<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * "10 năm kinh nghiệm bán ô tô" → "Tư vấn Lexus tại Lexus Thăng Long từ 2017":
 * giấy xác nhận của đại lý ghi chị Thu Hà làm từ 01/03/2017, câu cũ phóng đại
 * (Google 02/10/2026 coi bịa kinh nghiệm tác giả là tín hiệu kém tin cậy).
 * Cài đặt chỉ đổi khi còn đúng câu cũ; bài viết thay đúng cụm đó.
 */
return new class extends Migration
{
    private const OLD = '10 năm kinh nghiệm bán ô tô';

    private const NEW = 'Tư vấn Lexus tại Lexus Thăng Long từ 2017';

    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'advisor_experience')->first();
        if ($row && json_decode((string) $row->value, true) === self::OLD) {
            DB::table('settings')->where('key', 'advisor_experience')->update(['value' => json_encode(self::NEW), 'updated_at' => now()]);
            Cache::forget('catalog.settings');
        }

        foreach ([Post::query(), Page::query(), Product::query()] as $query) {
            $query->get()->each(fn (Model $model) => $this->rewrite($model));
        }
    }

    public function down(): void
    {
        // Không hoàn tác: câu cũ không đúng sự thật.
    }

    private function rewrite(Model $model): void
    {
        $walk = function ($value) use (&$walk) {
            if (is_array($value)) {
                return array_map($walk, $value);
            }

            return is_string($value)
                ? str_replace([self::OLD, '10 Năm Kinh Nghiệm Bán Ô Tô'], [lcfirst(self::NEW), self::NEW], $value)
                : $value;
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
