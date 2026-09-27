<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Kênh TikTok của chuyên viên Thu Hà (Cài đặt → Mạng xã hội → TikTok). Chỉ điền
 * khi ô đang trống — đã nhập tay trong admin thì giữ.
 */
return new class extends Migration
{
    private const URL = 'https://www.tiktok.com/@thuhalexus28';

    public function up(): void
    {
        // Chỉ website Lexus này (đã có cài đặt site_name) — DB mới/test thì bỏ qua.
        if (! DB::table('settings')->where('key', 'site_name')->exists()) {
            return;
        }

        $row = DB::table('settings')->where('key', 'tiktok')->first();
        if ($row && filled(json_decode((string) $row->value, true))) {
            return;
        }

        $row
            ? DB::table('settings')->where('key', 'tiktok')->update(['value' => json_encode(self::URL), 'updated_at' => now()])
            : DB::table('settings')->insert(['key' => 'tiktok', 'value' => json_encode(self::URL), 'group' => 'social', 'created_at' => now(), 'updated_at' => now()]);
        Cache::forget('catalog.settings');
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'tiktok')->where('value', json_encode(self::URL))->delete();
        Cache::forget('catalog.settings');
    }
};
