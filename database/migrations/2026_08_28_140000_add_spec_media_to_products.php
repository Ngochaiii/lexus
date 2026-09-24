<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ảnh và tài liệu PDF cho phần thông số kỹ thuật.
 *
 * Hãng thường phát hành bảng thông số dưới dạng ảnh dựng sẵn hoặc file PDF.
 * Bắt đại lý gõ lại từng dòng vào lưới thông số là việc thừa và dễ sai — cho
 * họ tải thẳng bản gốc lên, đặt cạnh lưới thông số đang có chứ không thay thế.
 *
 * `spec_images` là mảng: bảng thông số dài thường bị cắt thành nhiều trang ảnh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('spec_images')->nullable()->after('spec_notes');
            $table->string('spec_pdf')->nullable()->after('spec_images');
            $table->string('spec_pdf_label')->nullable()->after('spec_pdf');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['spec_images', 'spec_pdf', 'spec_pdf_label']);
        });
    }
};
