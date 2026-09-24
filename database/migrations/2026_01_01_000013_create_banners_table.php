<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Banner hero trang chủ.
 *
 * Không nhét vào bảng `settings`: settings là key/value phẳng nên banner
 * nhiều slide sẽ phải đẻ ra banner_1_title, banner_2_title… Và banner cần
 * thứ tự với lịch chạy — hai thứ key/value không diễn tả được.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('eyebrow')->nullable();       // dòng nhỏ chữ hoa phía trên
            $table->string('image')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);

            // Khuyến mãi theo đợt: để trống là chạy vô thời hạn.
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();
            $table->index(['is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
