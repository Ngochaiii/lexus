<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Site là trang tư vấn cá nhân của chị Thu Hà, không phải web của đại lý
 * (Google Ads tạm ngưng tài khoản vì ngụ ý là đại lý):
 *   - họ tên đầy đủ hiện dưới logo và ở chân trang — chỉ điền khi ô trống;
 *   - banner trang chủ nói bằng lời chuyên viên thay vì "Đại lý Lexus chính
 *     hãng…" — chỉ đổi khi chữ còn đúng bản seeder, đã sửa trong admin thì giữ.
 */
return new class extends Migration
{
    private const NAME = 'Nguyễn Thị Thu Hà';

    public const BANNER_OLD = [
        'eyebrow' => 'LEXUS THĂNG LONG · CẦU GIẤY, HÀ NỘI',
        'subtitle' => "Đại lý Lexus chính hãng tại ngã tư Phạm Hùng – Dương Đình Nghệ.\nĐón tiếp bạn mỗi ngày, 8:00 – 18:00.",
    ];

    public const BANNER_NEW = [
        'eyebrow' => 'THU HÀ · TƯ VẤN LEXUS THĂNG LONG',
        'subtitle' => "Thu Hà – chuyên viên tư vấn tại Lexus Thăng Long, ngã tư Phạm Hùng – Dương Đình Nghệ.\nHẹn xem xe, lái thử mỗi ngày, 8:00 – 18:00.",
    ];

    public function up(): void
    {
        // Chỉ website của chị Thu Hà — DB mới/test thì bỏ qua.
        if (json_decode((string) DB::table('settings')->where('key', 'advisor_name')->value('value'), true) !== 'Thu Hà') {
            return;
        }

        foreach (self::BANNER_OLD as $field => $old) {
            DB::table('banners')->where($field, $old)->update([$field => self::BANNER_NEW[$field], 'updated_at' => now()]);
        }

        $row = DB::table('settings')->where('key', 'advisor_full_name')->first();
        if ($row && filled(json_decode((string) $row->value, true))) {
            return;
        }

        $row
            ? DB::table('settings')->where('key', 'advisor_full_name')->update(['value' => json_encode(self::NAME), 'updated_at' => now()])
            : DB::table('settings')->insert(['key' => 'advisor_full_name', 'value' => json_encode(self::NAME), 'group' => 'home', 'created_at' => now(), 'updated_at' => now()]);
        Cache::forget('catalog.settings');
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'advisor_full_name')->where('value', json_encode(self::NAME))->delete();
        Cache::forget('catalog.settings');
    }
};
