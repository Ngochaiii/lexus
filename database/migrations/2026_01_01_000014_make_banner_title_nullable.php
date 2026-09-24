<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cho phép banner KHÔNG có tiêu đề.
 *
 * Nhiều chiến dịch chỉ cần đúng một tấm ảnh đã thiết kế sẵn chữ trong ảnh —
 * đè thêm tiêu đề và nút của site lên là hỏng bố cục nhà thiết kế. Banner
 * kiểu đó chỉ hiện ảnh, bấm vào ảnh là đi tới link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('title')->nullable(false)->change();
        });
    }
};
