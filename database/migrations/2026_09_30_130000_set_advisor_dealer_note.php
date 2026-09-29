<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Quảng cáo Google ghi "Mua Tại Đại Lý 3S Chính Hãng", "Showroom 3S Lexus Thăng
 * Long" — khối chuyên viên trên trang đích (partials/advisor-card) cần câu
 * tương ứng. Chỉ điền khi ô đang trống; đã sửa trong Cài đặt thì giữ.
 */
return new class extends Migration
{
    private const NOTE = 'Lexus Thăng Long – đại lý 3S chính hãng: bán xe, bảo hành, dịch vụ, phụ tùng';

    public function up(): void
    {
        // Chỉ website Lexus này (đã có cài đặt site_name) — DB mới/test thì bỏ qua.
        if (! DB::table('settings')->where('key', 'site_name')->exists()) {
            return;
        }

        $row = DB::table('settings')->where('key', 'advisor_dealer_note')->first();
        if ($row && filled(json_decode((string) $row->value, true))) {
            return;
        }

        $row
            ? DB::table('settings')->where('key', 'advisor_dealer_note')->update(['value' => json_encode(self::NOTE), 'updated_at' => now()])
            : DB::table('settings')->insert(['key' => 'advisor_dealer_note', 'value' => json_encode(self::NOTE), 'group' => 'general', 'created_at' => now(), 'updated_at' => now()]);
        Cache::forget('catalog.settings');
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'advisor_dealer_note')->where('value', json_encode(self::NOTE))->delete();
        Cache::forget('catalog.settings');
    }
};
