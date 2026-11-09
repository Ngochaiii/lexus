<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hành trình khách trên trang (09/11/2026): ghi thêm medium (cpc, organic…)
 * để tách khách từ quảng cáo khỏi khách tìm Google tự nhiên — trước đây chỉ
 * có source ("google") nên hai loại lẫn vào nhau. Xem EventController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_events', function (Blueprint $table) {
            $table->string('medium', 40)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('site_events', function (Blueprint $table) {
            $table->dropColumn('medium');
        });
    }
};
