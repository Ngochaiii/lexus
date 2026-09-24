<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ảnh riêng cho từng phiên bản.
 *
 * Trang mẫu xe của hãng cho mỗi phiên bản một tấm ảnh — khách nhìn ảnh phân
 * biệt Eco với Plus nhanh hơn đọc bảng thông số. Thẻ phiên bản trước đây chỉ
 * có chữ và số.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
