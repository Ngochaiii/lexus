<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // "Thành phố, Tỉnh, VN" tra từ IP bằng job ResolveLeadLocation.
            // Null khi IP nội bộ hoặc API lỗi.
            $table->string('location')->nullable()->after('ip');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
