<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lượt bấm Gọi/Zalo (26/10/2026): ghi nút nằm ở đâu (bar, float, hero,
 * table, sheet…) và khách ở trang bao nhiêu giây trước khi bấm — tách chạm
 * nhầm khỏi khách thật. Xem EventController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_events', function (Blueprint $table) {
            $table->string('place', 16)->nullable()->after('path');
            $table->unsignedInteger('secs')->nullable()->after('place');
        });
    }

    public function down(): void
    {
        Schema::table('site_events', function (Blueprint $table) {
            $table->dropColumn(['place', 'secs']);
        });
    }
};
