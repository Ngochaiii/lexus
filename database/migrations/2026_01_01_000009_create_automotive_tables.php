<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đặc thù ô tô. Bảng vẫn tạo, nhưng admin/API chỉ lộ ra khi
 * config('catalog.features.dealers') / .fee_calc bật.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->decimal('registration_fee_rate', 5, 4)->default(0);  // 0.1000 = 10%
            $table->decimal('plate_fee', 15, 2)->default(0);
            $table->decimal('inspection_fee', 15, 2)->default(0);
            $table->decimal('road_fee', 15, 2)->default(0);
            $table->decimal('insurance_fee', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('phone')->nullable();
            $table->json('opening_hours')->nullable();
            $table->timestamps();

            $table->index('province_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealers');
        Schema::dropIfExists('provinces');
    }
};
