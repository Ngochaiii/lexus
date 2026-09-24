<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // dat-lich-lai-thu | lien-he
            $table->string('name');
            $table->json('notify_emails')->nullable();
            $table->string('webhook_url')->nullable();
            $table->string('success_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('type')->default('text');  // text|email|tel|textarea|select|radio|checkbox|date|hidden
            $table->json('options')->nullable();
            $table->json('rules')->nullable();
            $table->string('placeholder')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->string('width')->default('full'); // full | half
            $table->timestamps();

            $table->unique(['form_id', 'key']);
            $table->index(['form_id', 'sort']);
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->nullable()->constrained('forms')->nullOnDelete();
            $table->json('data')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->json('utm')->nullable();
            $table->string('referrer')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('status')->default('new');  // new | contacted | done | spam
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('forms');
    }
};
