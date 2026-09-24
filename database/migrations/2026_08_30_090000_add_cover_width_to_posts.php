<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bề rộng ảnh bìa bài viết.
 *
 * Trang tin trước đây có ba bề rộng khác nhau chồng lên nhau: tiêu đề ở cột
 * chữ hẹp, ảnh bìa rộng bằng khung nội dung, còn phần thân lại lồng thêm một
 * khung nữa nên lệch tiếp — nhìn ba mép trái khác nhau. Giờ mọi khối thẳng
 * hàng theo cột chữ, khối nào cần rộng hoặc tràn màn hình thì tự chọn.
 *
 * Để trống = theo mặc định của trang (cột chữ).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('cover_width')->nullable()->after('cover');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('cover_width');
        });
    }
};
