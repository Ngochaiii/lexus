<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Sửa theo kết quả kiểm tra claude-seo (27/09/2026):
 *  - Mô tả trang chủ ~200 ký tự bị cắt còn câu đầu (mất phần giá) → bản ≤ 160
 *    ký tự. Chỉ thay khi còn đúng bản cũ do seeder đặt — đã sửa tay thì giữ.
 *  - Bài đã đăng nhưng trống "Đăng lúc" (bài Gemini) → lấy ngày tạo, để Google
 *    có datePublished và trang hiện ngày đăng.
 */
return new class extends Migration
{
    private const OLD = 'Lexus Thăng Long — đại lý Lexus chính hãng tại ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy, Hà Nội. '
        .'Bảng giá 6 dòng xe Lexus 2026 từ 2,36 tỷ, lái thử và báo giá lăn bánh cùng chuyên viên Thu Hà.';

    private const NEW = 'Đại lý Lexus chính hãng tại Hà Nội: bảng giá 6 dòng xe Lexus 2026 từ 2,36 tỷ, lái thử miễn phí và báo giá lăn bánh chi tiết cùng chuyên viên Thu Hà.';

    public function up(): void
    {
        $this->swap(self::OLD, self::NEW);

        DB::table('posts')->where('status', 'published')->whereNull('published_at')->whereNull('deleted_at')
            ->update(['published_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        $this->swap(self::NEW, self::OLD);
    }

    /** Cột value lưu JSON (cast của Setting) — so sánh sau khi giải mã. */
    private function swap(string $from, string $to): void
    {
        $row = DB::table('settings')->where('key', 'site_description')->first();
        if ($row && json_decode((string) $row->value, true) === $from) {
            DB::table('settings')->where('key', 'site_description')->update(['value' => json_encode($to)]);
            Cache::forget('catalog.settings');
        }
    }
};
