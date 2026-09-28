<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nhập chuyển đổi ngoại tuyến cho Google Ads (Admin → Liên hệ → "Xuất cho
 * Google Ads"): báo Google lượt bấm nào thành khách hẹn lái thử thật.
 *
 *  - gclid / gbraid / wbraid: mã click Google gắn vào URL quảng cáo (gbraid,
 *    wbraid thay gclid trên một số lượt từ iOS). Chỉ lấy được lúc khách vào.
 *  - qualified_at: lần ĐẦU lead lên "Hẹn lái thử" trở lên — thời điểm chuyển đổi.
 *
 * Sửa dữ liệu: insight.js cũ gửi cờ gclid = 0 và Attribution coi 0 là "có
 * gclid", nên lead nào gửi qua trình duyệt cũng bị ghi google/cpc. Tới lúc
 * này chưa chạy Google Ads, nên mọi google/cpc trước ngày 30/09/2026 đều sai
 * → đưa về "Chưa rõ" (không đoán lại được nguồn thật). Lượt bấm Gọi/Zalo
 * (site_events) cũng vậy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('gclid')->nullable()->after('campaign');
            $table->string('gbraid')->nullable()->after('gclid');
            $table->string('wbraid')->nullable()->after('gbraid');
            $table->timestamp('qualified_at')->nullable()->after('closed_at');
        });

        DB::table('leads')->whereIn('status', ['appointment', 'test_drive', 'deposit', 'won'])
            ->update(['qualified_at' => DB::raw('updated_at')]);

        DB::table('leads')->where('source', 'google')->where('medium', 'cpc')
            ->where('created_at', '<', '2026-09-30 00:00:00')
            ->update(['source' => null, 'medium' => null]);

        // Lượt bấm Gọi/Zalo cùng lỗi (nhánh gclid chạy trước nhánh referrer nên
        // cả khách Google tự nhiên lẫn khách vào thẳng đều thành "google").
        DB::table('site_events')->where('source', 'google')
            ->where('created_at', '<', '2026-09-30 00:00:00')
            ->update(['source' => null]);
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['gclid', 'gbraid', 'wbraid', 'qualified_at']);
        });
    }
};
