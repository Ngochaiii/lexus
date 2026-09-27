<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kế hoạch bài viết do Gemini đề xuất (content_ideas) và bộ bài chia sẻ
 * Facebook/Zalo/diễn đàn của từng bài (posts.share_kit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_ideas', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('primary_keyword');
            $table->json('secondary_keywords')->nullable();
            $table->string('search_intent', 500)->nullable();
            $table->text('angle')->nullable();          // bài cần trả lời những ý gì
            $table->string('target_url')->nullable();   // trang cần đẩy (link nội bộ bắt buộc)
            $table->string('cluster', 40)->nullable();  // gia | so-sanh | tra-gop | kinh-nghiem | dia-phuong
            $table->unsignedTinyInteger('priority')->default(2); // 1 cao · 2 vừa · 3 thấp
            $table->string('status', 20)->default('idea');       // idea | writing | drafted | dismissed
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->json('share_kit')->nullable()->after('seo');
        });
    }

    public function down(): void
    {
        Schema::table('posts', fn (Blueprint $table) => $table->dropColumn('share_kit'));
        Schema::dropIfExists('content_ideas');
    }
};
