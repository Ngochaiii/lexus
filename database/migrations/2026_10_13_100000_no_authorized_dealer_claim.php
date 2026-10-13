<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Bỏ các câu xưng/nhấn "đại lý Lexus chính hãng" (13/10/2026): mô tả trang
 * (JSON-LD mọi trang), dòng dưới khối chuyên viên ("đại lý 3S chính hãng"),
 * hỏi đáp "Mua … chính hãng ở đâu" ở trang xe — kế hoạch Ads bản 11 coi là dễ
 * bị hiểu là trình bày sai. Đổi sang "showroom Lexus Thăng Long". Chỉ thay
 * đúng các cụm cũ; giữ "phụ tùng chính hãng", "bảo dưỡng chính hãng".
 * Chạy lại không đổi thêm.
 */
return new class extends Migration
{
    private const RULES = [
        '/chuyên viên tư vấn tại Lexus Thăng Long – đại lý Lexus chính hãng ở Hà Nội/u' => 'chuyên viên tư vấn tại showroom Lexus Thăng Long, Hà Nội',
        '/Lexus Thăng Long – đại lý 3S chính hãng: bán xe, bảo hành, dịch vụ, phụ tùng/u' => 'Showroom Lexus Thăng Long: bán xe, bảo hành, dịch vụ, phụ tùng tại một nơi',
        '/Lexus Thăng Long là đại lý Lexus chính hãng tại /u' => 'Showroom Lexus Thăng Long ở ',
        '/Mua (.+?) chính hãng ở đâu tại Hà Nội\?/u' => 'Mua $1 ở đâu tại Hà Nội?',
        '/xưởng dịch vụ chính hãng (?:ngay )?tại đại lý/u' => 'xưởng dịch vụ ngay tại showroom',
        '/xưởng dịch vụ chính hãng\./u' => 'xưởng dịch vụ.',
        '/Showroom và xưởng dịch vụ Lexus chính hãng tại/u' => 'Showroom và xưởng dịch vụ Lexus Thăng Long tại',
    ];

    public function up(): void
    {
        foreach (['site_description', 'advisor_dealer_note'] as $key) {
            $row = DB::table('settings')->where('key', $key)->first();
            $old = $row ? json_decode((string) $row->value, true) : null;
            if (is_string($old) && ($new = $this->apply($old)) !== $old) {
                DB::table('settings')->where('key', $key)->update(['value' => json_encode($new), 'updated_at' => now()]);
            }
        }
        Cache::forget('catalog.settings');

        foreach ([Product::query(), Page::query(), Post::query()] as $query) {
            $query->get()->each(fn (Model $model) => $this->rewrite($model));
        }
    }

    public function down(): void
    {
        // Không hoàn tác.
    }

    private function apply(string $text): string
    {
        return preg_replace(array_keys(self::RULES), array_values(self::RULES), $text);
    }

    private function rewrite(Model $model): void
    {
        $walk = function ($value) use (&$walk) {
            if (is_array($value)) {
                return array_map($walk, $value);
            }

            return is_string($value) ? $this->apply($value) : $value;
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
