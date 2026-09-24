<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hai đoạn ghi chú xếp cạnh nhau ngay dưới bảng thông số (bản thiết kế:
 * "An toàn & an ninh" và "Hỗ trợ lái nâng cao ADAS").
 *
 * Trước nhét vào `specs` dưới một nhóm tên `__notes` để khỏi thêm cột — nhưng
 * nhóm đó hiện lên repeater thông số trong admin như một nhóm bình thường,
 * người nhập sửa nhầm là hỏng. Tách hẳn ra cột riêng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('spec_notes')->nullable()->after('specs');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('spec_notes');
        });
    }
};
