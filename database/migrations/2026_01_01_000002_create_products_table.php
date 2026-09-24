<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cột cố định chỉ giữ những gì MỌI mặt hàng đều có.
     * Mọi thứ biến thiên nằm trong `sections`.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->decimal('price_from', 15, 2)->nullable();

            $table->json('hero')->nullable();        // {type:image|video, src, poster}
            $table->json('highlights')->nullable();  // [{value,unit,label}]
            $table->json('sections')->nullable();    // ← toàn bộ phần biến thiên
            $table->json('specs')->nullable();       // [{group,rows:[{label,value}]}]
            $table->json('seo')->nullable();

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
