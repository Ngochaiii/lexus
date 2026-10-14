<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;

/**
 * Hỏi đáp trang GX "GX … giá thấp hơn khoảng 2,4 tỷ" (so với LX 600) tính theo
 * giá GX cũ 6,2 tỷ; GX 550 nay 6,45 tỷ → chênh LX 600 Urban 8,59 tỷ khoảng
 * 2,1 tỷ. Câu này lọt khỏi migration 2026_10_14_120000 vì viết khác. Chạy lại
 * không đổi thêm.
 */
return new class extends Migration
{
    private const OLD = 'giá thấp hơn khoảng 2,4 tỷ';

    private const NEW = 'giá thấp hơn khoảng 2,1 tỷ';

    public function up(): void
    {
        foreach ([Product::query(), Page::query(), Post::query()] as $query) {
            $query->get()->each(fn (Model $model) => $this->rewrite($model));
        }
    }

    public function down(): void
    {
        // Không hoàn tác.
    }

    private function rewrite(Model $model): void
    {
        $walk = function ($value) use (&$walk) {
            if (is_array($value)) {
                return array_map($walk, $value);
            }

            return is_string($value) ? str_replace(self::OLD, self::NEW, $value) : $value;
        };

        foreach (['title', 'excerpt', 'sections', 'seo'] as $field) {
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
