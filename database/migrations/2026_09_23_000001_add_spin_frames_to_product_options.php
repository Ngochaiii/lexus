<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_options', function (Blueprint $table) {
            $table->json('spin_frames')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('product_options', fn (Blueprint $table) => $table->dropColumn('spin_frames'));
    }
};
