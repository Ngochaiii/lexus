<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Giá ES trong chữ viết sẵn theo giá phiên bản đã sửa trong admin (07/10/2026,
 * người dùng xác nhận 14/10): 350h Premium 2,36 → 2,34 · Luxury 2,58 → 2,60 ·
 * ES 500e 2,98 → 2,94 tỷ; lăn bánh Hà Nội 2,66 → 2,63 · 2,90 → 2,93 ·
 * 2,99 → 2,95 tỷ. Trang ES ghi hai bộ giá khác nhau → khách mất tin.
 *
 * Số thập phân chỉ đổi khi đứng trước "tỷ" hoặc dấu khoảng "–" (không đụng
 * "2,99%", "2,90 triệu"); số đầy đủ đổi đúng từng con số cũ. Giá từ của xe ES
 * chỉ đổi khi còn đúng 2.360.000.000. Chạy lại không đổi thêm.
 */
return new class extends Migration
{
    private const RULES = [
        '/(?<![\d.,])2,36(?=\s*(?:tỷ|–|-))/u' => '2,34',
        '/(?<![\d.,])2,58(?=\s*(?:tỷ|–|-))/u' => '2,60',
        '/(?<![\d.,])2,98(?=\s*(?:tỷ|–|-))/u' => '2,94',
        '/(?<![\d.,])2,66(?=\s*(?:tỷ|–|-))/u' => '2,63',
        '/(?<![\d.,])2,90(?=\s*(?:tỷ|–|-))/u' => '2,93',
        '/(?<![\d.,])2,99(?=\s*(?:tỷ|–|-))/u' => '2,95',
        '/(?<![\d.])2\.360\.000\.000(?![\d.])/u' => '2.340.000.000',
        '/(?<![\d.])2\.580\.000\.000(?![\d.])/u' => '2.600.000.000',
        '/(?<![\d.])2\.980\.000\.000(?![\d.])/u' => '2.940.000.000',
        '/(?<![\d.])283\.200\.000(?![\d.])/u'    => '280.800.000',
        '/(?<![\d.])309\.600\.000(?![\d.])/u'    => '312.000.000',
        '/(?<![\d.])2\.657\.200\.000(?![\d.])/u' => '2.634.800.000',
        '/(?<![\d.])2\.903\.600\.000(?![\d.])/u' => '2.926.000.000',
        '/(?<![\d.])2\.659\.240\.700(?![\d.])/u' => '2.636.840.700',
    ];

    private const FIELDS = ['title', 'seo_title', 'excerpt', 'tagline', 'keywords', 'sections', 'seo', 'highlights', 'specs'];

    public function up(): void
    {
        DB::table('settings')->get()->each(function ($row) {
            $old = json_decode((string) $row->value, true);
            if ($old !== null && ($new = $this->walk($old)) !== $old) {
                DB::table('settings')->where('key', $row->key)
                    ->update(['value' => json_encode($new, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
            }
        });
        Cache::forget('catalog.settings');

        Product::query()->where('slug', 'es')->where('price_from', 2_360_000_000)
            ->each(fn (Product $es) => $es->forceFill(['price_from' => 2_340_000_000])->saveQuietly());

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
        if (! is_string($value)) {
            return $value;
        }
        // Ô số riêng (giá từ "2,36" + đơn vị "tỷ" ở ô khác).
        if ($value === '2,36') {
            return '2,34';
        }

        return preg_replace(array_keys(self::RULES), array_values(self::RULES), $value);
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
