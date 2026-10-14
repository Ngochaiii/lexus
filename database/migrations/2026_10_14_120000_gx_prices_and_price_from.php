<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Giá phiên bản trên trang sản phẩm là chuẩn (người dùng 14/10/2026).
 *
 * 1. Giá từ của mọi xe = giá phiên bản rẻ nhất (từ nay ProductVariant tự
 *    đồng bộ khi sửa/xoá; đây là lần đồng bộ cho dữ liệu đang có).
 * 2. GX 550 đã sửa trong admin thành 6,45 tỷ nhưng chữ viết sẵn còn 6,2 tỷ:
 *    6,2/6,20 → 6,45 tỷ · lăn bánh 6,96 → 7,24 tỷ · 6.200.000.000 →
 *    6.450.000.000 · "rẻ hơn LX 600 khoảng 2,4 tỷ" → 2,1 tỷ. Chỉ đổi số đứng
 *    trước "tỷ" (không đụng "6,24 L/100 km"). Chạy lại không đổi thêm.
 */
return new class extends Migration
{
    private const RULES = [
        '/(?<![\d.,])6,20?(?=\s*tỷ)/u'                  => '6,45',
        '/(?<![\d.,])6,96(?=\s*tỷ)/u'                   => '7,24',
        '/(?<![\d.])6\.200\.000\.000(?![\d.])/u'        => '6.450.000.000',
        '/rẻ hơn LX 600 khoảng 2,4 tỷ/u'                => 'rẻ hơn LX 600 khoảng 2,1 tỷ',
    ];

    private const FIELDS = ['title', 'seo_title', 'excerpt', 'tagline', 'keywords', 'sections', 'seo', 'highlights', 'specs'];

    public function up(): void
    {
        Product::query()->get()->each(function (Product $product) {
            $min = ProductVariant::query()->where('product_id', $product->id)->where('price', '>', 0)->min('price');
            if ($min !== null && (float) $product->price_from !== (float) $min) {
                Product::query()->whereKey($product->id)->update(['price_from' => $min]);
            }
        });

        DB::table('settings')->get()->each(function ($row) {
            $old = json_decode((string) $row->value, true);
            if ($old !== null && ($new = $this->walk($old)) !== $old) {
                DB::table('settings')->where('key', $row->key)
                    ->update(['value' => json_encode($new, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
            }
        });
        Cache::forget('catalog.settings');

        foreach ([Product::query(), Page::query(), Post::query()] as $query) {
            $query->get()->each(fn (Model $model) => $this->rewrite($model));
        }
    }

    public function down(): void
    {
        // Không hoàn tác: giá cũ là sai.
    }

    private function walk(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->walk($v), $value);
        }

        return is_string($value) ? preg_replace(array_keys(self::RULES), array_values(self::RULES), $value) : $value;
    }

    private function rewrite(Model $model): void
    {
        foreach (self::FIELDS as $field) {
            if (! array_key_exists($field, $model->getAttributes())) {
                continue;
            }
            $old = $model->getAttribute($field);
            if ($old !== null && ($new = $this->walk($old)) !== $old) {
                $model->setAttribute($field, $new);
            }
        }

        if ($model->isDirty()) {
            $model->saveQuietly();
        }
    }
};
