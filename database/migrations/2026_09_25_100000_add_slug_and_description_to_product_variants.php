<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Trang riêng cho từng phiên bản: /san-pham/{xe}/{phien-ban}
 * (vd /san-pham/rx/rx-350h-premium). slug duy nhất trong một dòng xe;
 * description là đoạn giới thiệu riêng (tuỳ chọn) để trang không trùng
 * nội dung với trang dòng xe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->text('description')->nullable()->after('note');
            $table->unique(['product_id', 'slug']);
        });

        // Điền slug cho phiên bản đã có, tránh trùng trong cùng dòng xe.
        $used = [];
        foreach (DB::table('product_variants')->orderBy('id')->get(['id', 'product_id', 'name']) as $row) {
            $base = Str::slug($row->name) ?: 'phien-ban';
            $slug = $base;
            for ($i = 2; isset($used[$row->product_id][$slug]); $i++) {
                $slug = $base.'-'.$i;
            }
            $used[$row->product_id][$slug] = true;
            DB::table('product_variants')->where('id', $row->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'slug']);
            $table->dropColumn(['slug', 'description']);
        });
    }
};
