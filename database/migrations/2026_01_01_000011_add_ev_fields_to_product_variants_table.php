<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đặc thù xe điện — chỉ có ý nghĩa khi config('catalog.features.fuel_calc')
 * bật, giống cách `provinces`/`dealers` chỉ lộ ra khi feature tương ứng bật.
 * Cột vẫn tạo cho mọi fork (không migration khi đổi hãng), hãng không phải
 * xe điện thì để trống, calculator tự ẩn (xem FuelCalculator + product page).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('battery_kwh', 6, 2)->nullable()->after('note');
            $table->unsignedSmallInteger('range_km')->nullable()->after('battery_kwh');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['battery_kwh', 'range_km']);
        });
    }
};
