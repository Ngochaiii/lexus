<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GEO: chủ đề gắn giai đoạn hành trình khách (tim-hieu / lua-chon /
 * quyet-dinh) và các câu khách hỏi ChatGPT, Gemini mà bài phải trả lời.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_ideas', function (Blueprint $table) {
            $table->string('stage', 20)->nullable()->after('cluster');
            $table->json('ai_prompts')->nullable()->after('secondary_keywords');
        });
    }

    public function down(): void
    {
        Schema::table('content_ideas', function (Blueprint $table) {
            $table->dropColumn(['stage', 'ai_prompts']);
        });
    }
};
