<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 09/10/2026: chị Thu Hà nghe điện thoại bằng số 0934 846 666; Zalo giữ
 * 0989 345 989. Cài đặt số gọi chỉ đổi khi còn đúng số cũ; số cũ trong bài
 * viết, trang, xe đổi sang số mới trừ chỗ là số Zalo (sau chữ "Zalo",
 * trong link zalo.me). Chạy lại không đổi thêm.
 */
return new class extends Migration
{
    private const OLD = '0989345989';

    private const NEW = '0934846666';

    private const OLD_ANY = '0989[ .]?345[ .]?989';

    /** Thứ tự quan trọng: tách "gọi hoặc nhắn Zalo <số>" trước, thay số còn lại sau. */
    private const RULES = [
        '/([Gg])ọi hoặc nhắn Zalo( chuyên viên Thu Hà)?(:?) '.self::OLD_ANY.'/u' => '$1ọi$2$3 0934 846 666 hoặc nhắn Zalo 0989 345 989',
        '/số '.self::OLD_ANY.' \(gọi hoặc Zalo\)/u' => 'số 0934 846 666 (gọi) hoặc Zalo 0989 345 989',
        '/\+84989345989/' => '+84934846666',
        '/(?<!zalo\.me\/)\b0989345989\b/' => self::NEW,
        '/(?<!Zalo )(?<!Zalo: )\b0989[ .]345[ .]989\b/u' => '0934 846 666',
    ];

    public function up(): void
    {
        foreach (['advisor_phone', 'hotline'] as $key) {
            $row = DB::table('settings')->where('key', $key)->first();
            if ($row && json_decode((string) $row->value, true) === self::OLD) {
                DB::table('settings')->where('key', $key)->update(['value' => json_encode(self::NEW), 'updated_at' => now()]);
            }
        }
        Cache::forget('catalog.settings');

        foreach ([Post::query(), Page::query(), Product::query()] as $query) {
            $query->get()->each(fn (Model $model) => $this->rewrite($model));
        }
    }

    public function down(): void
    {
        // Không hoàn tác: chị Hà đã chuyển sang nghe máy số mới.
    }

    private function rewrite(Model $model): void
    {
        $walk = function ($value) use (&$walk) {
            if (is_array($value)) {
                return array_map($walk, $value);
            }

            return is_string($value) ? preg_replace(array_keys(self::RULES), array_values(self::RULES), $value) : $value;
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
