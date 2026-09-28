<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Trang chủ tự giới thiệu "Lexus Thăng Long — Đại lý Lexus chính hãng" trong
 * khi tài khoản Google Ads đứng tên chuyên viên Thu Hà → rủi ro chính sách
 * "Trình bày sai sự thật" (làm như thể là tổ chức khác, có thể bị khoá tài
 * khoản). Tiêu đề và mô tả giờ mở đầu bằng tên chuyên viên, vẫn giữ từ khoá
 * "Lexus Thăng Long", "đại lý Lexus chính hãng".
 *
 * Chỉ thay khi còn đúng bản seeder cũ — đã sửa tay trong Cài đặt thì giữ.
 */
return new class extends Migration
{
    private const VALUES = [
        'seo_home_title' => [
            'Lexus Thăng Long — Đại lý Lexus chính hãng tại Cầu Giấy, Hà Nội',
            'Thu Hà – Tư vấn Lexus Thăng Long | Bảng giá Lexus 2026',
        ],
        'site_description' => [
            'Đại lý Lexus chính hãng tại Hà Nội: bảng giá 6 dòng xe Lexus 2026 từ 2,36 tỷ, lái thử miễn phí và báo giá lăn bánh chi tiết cùng chuyên viên Thu Hà.',
            'Thu Hà, chuyên viên tư vấn tại Lexus Thăng Long – đại lý Lexus chính hãng ở Hà Nội. Bảng giá 6 dòng xe 2026 từ 2,36 tỷ, lái thử, báo giá lăn bánh chi tiết.',
        ],
    ];

    public function up(): void
    {
        foreach (self::VALUES as $key => [$old, $new]) {
            $this->swap($key, $old, $new);
        }
    }

    public function down(): void
    {
        foreach (self::VALUES as $key => [$old, $new]) {
            $this->swap($key, $new, $old);
        }
    }

    /** Cột value lưu JSON (cast của Setting) — so sánh sau khi giải mã. */
    private function swap(string $key, string $from, string $to): void
    {
        $row = DB::table('settings')->where('key', $key)->first();
        if ($row && json_decode((string) $row->value, true) === $from) {
            DB::table('settings')->where('key', $key)->update(['value' => json_encode($to)]);
            Cache::forget('catalog.settings');
        }
    }
};
