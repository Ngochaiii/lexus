<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CRM + đo lường tự host + tình trạng xe.
 *
 *  - leads: nguồn khách (first touch), đường ống bán hàng, hẹn gọi lại, nhật
 *    ký cuộc gọi, giá trị hợp đồng & hoa hồng → tính tỷ lệ chốt, doanh thu.
 *  - site_events: lượt bấm Gọi/Zalo và số đo tốc độ của khách thật (LCP, INP,
 *    CLS…) — ẩn danh, không lưu IP.
 *  - product_variants: tình trạng xe (có sẵn / đặt trước / liên hệ).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('source', 40)->nullable()->after('referrer');
            $table->string('medium', 40)->nullable()->after('source');
            $table->string('campaign', 120)->nullable()->after('medium');
            $table->string('landing_page')->nullable()->after('campaign');
            $table->string('device', 12)->nullable()->after('landing_page');
            $table->dateTime('follow_up_at')->nullable()->after('status');
            $table->string('lost_reason', 60)->nullable()->after('follow_up_at');
            $table->decimal('deal_value', 15, 2)->nullable()->after('lost_reason');
            $table->decimal('commission', 15, 2)->nullable()->after('deal_value');
            $table->timestamp('closed_at')->nullable()->after('commission');
            $table->json('activities')->nullable()->after('note');

            $table->index('follow_up_at');
            $table->index('source');
        });

        // Trạng thái cũ → đường ống mới.
        DB::table('leads')->whereIn('status', ['contacted', 'done'])->update(['status' => 'called']);

        Schema::create('site_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16);            // call | zalo | vital
            $table->string('metric', 8)->nullable(); // LCP | INP | CLS | FCP | TTFB
            $table->double('value')->nullable();
            $table->string('path')->nullable();
            $table->string('device', 12)->nullable();
            $table->string('source', 40)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['type', 'created_at']);
            $table->index('path');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('availability', 20)->nullable()->after('price_original'); // in_stock | pre_order | contact
            $table->string('availability_note', 120)->nullable()->after('availability');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['availability', 'availability_note']);
        });

        Schema::dropIfExists('site_events');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['follow_up_at']);
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'medium', 'campaign', 'landing_page', 'device',
                'follow_up_at', 'lost_reason', 'deal_value', 'commission', 'closed_at', 'activities']);
        });
    }
};
