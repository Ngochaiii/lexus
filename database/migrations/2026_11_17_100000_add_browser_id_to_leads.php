<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mã ngẫu nhiên của trình duyệt gửi form (insight.js, localStorage) — không
 * chứa thông tin cá nhân. Để nhận ra một trình duyệt gửi nhiều số khác nhau
 * dù đổi mạng (17/11/2026: 4 form liền đều là số của người khác).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('leads', 'browser_id')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->string('browser_id', 40)->nullable()->after('ip')->index();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['browser_id']);
            $table->dropColumn('browser_id');
        });
    }
};
