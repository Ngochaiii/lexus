<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lead ghi nhận đúng PHIÊN BẢN khách quan tâm (RX 350h Luxury, LX 600 VIP…),
 * không chỉ dòng xe. Khách bấm "Nhận báo giá" trên thẻ phiên bản ở trang chủ
 * hay trang xe → admin biết chính xác mẫu nào được hỏi nhiều.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
        });
    }
};
