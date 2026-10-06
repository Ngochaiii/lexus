<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Viết lại Chính sách quyền riêng tư và Điều khoản sử dụng (06/10/2026):
 * người chịu trách nhiệm là chị Nguyễn Thị Thu Hà, không xưng "chúng tôi",
 * không nói đang chạy Google Ads (tài khoản đã huỷ, chỉ còn GA4), ảnh xe là
 * ảnh xe bán tại Việt Nam, website không nhận thanh toán.
 * Chỉ thay khi trang còn đúng bản cũ (ngày cập nhật cũ); chạy lại không đổi.
 */
return new class extends Migration
{
    private const OLD_DATES = [
        'quyen-rieng-tu' => 'Cập nhật lần cuối: 28/09/2026',
        'dieu-khoan'     => 'Cập nhật lần cuối: 24/09/2026',
    ];

    public function up(): void
    {
        $content = require database_path('content/legal-pages.php');

        foreach (self::OLD_DATES as $slug => $marker) {
            $page = Page::query()->where('slug', $slug)->first();
            if ($page && str_contains(json_encode($page->sections, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $marker)) {
                $page->update($content[$slug]);
            }
        }
    }

    public function down(): void
    {
        // Không hoàn tác: bản cũ nói sai tình trạng quảng cáo và xưng như đại lý.
    }
};
