<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đoạn giới thiệu ngắn hiện trên đầu form ở frontend — mỗi form một câu
 * riêng (VD "Đặt cọc" khác "Đăng ký lái thử") thay vì một câu hardcode
 * dùng chung cho mọi form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
