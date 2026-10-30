<?php

use App\Models\Banner;
use App\Models\Form;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 30/10/2026: chị Thu Hà không nhận được cuộc gọi/Zalo qua 0934 846 666 và
 * 0989 345 989 (Ads ghi 4 lượt bấm 23–25/10, chị không nhận cuộc nào), đổi
 * sang một số 0977 573 366 cho cả gọi lẫn Zalo. Chỉ thay chỗ còn đúng số cũ
 * (cài đặt, bài, trang, xe, form, banner, menu); số đã sửa tay sang số khác
 * thì giữ. Chạy lại không đổi thêm.
 */
return new class extends Migration
{
    private const CALL = '0934[ .]?846[ .]?666';

    private const ZALO = '0989[ .]?345[ .]?989';

    /** Thứ tự quan trọng: gộp các câu "gọi … hoặc nhắn Zalo …" trước, thay số còn lại sau. */
    private const RULES = [
        '/([Gg])ọi( chuyên viên Thu Hà)?(:?) '.self::CALL.' hoặc nhắn Zalo '.self::ZALO.'/u' => '$1ọi hoặc nhắn Zalo$2$3 0977 573 366',
        '/số '.self::CALL.' \(gọi\) hoặc Zalo '.self::ZALO.'/u' => 'số 0977 573 366 (gọi hoặc Zalo)',
        '/'.self::CALL.' \(Thu Hà\), Zalo '.self::ZALO.'/u' => '0977 573 366 (Thu Hà, gọi và Zalo)',
        '/'.self::CALL.', Zalo '.self::ZALO.'/u' => '0977 573 366 (gọi và Zalo)',
        '/\+84(934846666|989345989)\b/' => '+84977573366',
        '/\b(0934846666|0989345989)\b/' => '0977573366',
        '/\b('.self::CALL.'|'.self::ZALO.')\b/' => '0977 573 366',
    ];

    public function up(): void
    {
        DB::table('settings')->get()->each(function ($row) {
            $value = json_decode((string) $row->value, true);
            if (is_string($value) && ($new = $this->replace($value)) !== $value) {
                DB::table('settings')->where('key', $row->key)->update(['value' => json_encode($new), 'updated_at' => now()]);
            }
        });
        Cache::forget('catalog.settings');

        $fields = [
            Post::class     => ['title', 'excerpt', 'sections', 'seo'],
            Page::class     => ['title', 'excerpt', 'sections', 'seo'],
            Product::class  => ['tagline', 'sections', 'seo'],
            Form::class     => ['description', 'success_message'],
            Banner::class   => ['title', 'subtitle', 'url', 'link'],
            MenuItem::class => ['label', 'url'],
        ];
        foreach ($fields as $class => $names) {
            $class::query()->get()->each(fn (Model $model) => $this->rewrite($model, $names));
        }
    }

    public function down(): void
    {
        // Không hoàn tác: chị Hà đã chuyển sang số mới.
    }

    private function replace(string $text): string
    {
        return preg_replace(array_keys(self::RULES), array_values(self::RULES), $text);
    }

    private function rewrite(Model $model, array $fields): void
    {
        $walk = function ($value) use (&$walk) {
            if (is_array($value)) {
                return array_map($walk, $value);
            }

            return is_string($value) ? $this->replace($value) : $value;
        };

        foreach ($fields as $field) {
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
