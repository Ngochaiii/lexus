<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 03/11/2026: tháng 11 chị Hà tập trung RX 350h; nhóm quảng cáo "RX350 tên cũ"
 * đưa khách gõ "lexus rx350" về /san-pham/rx. Thêm ô ghi chú đầu bảng giá
 * (sửa trong admin) và đặt ghi chú cho RX; tagline ở hero và số nổi bật đưa
 * RX 350h lên trước RX 500h. Chỉ thay khi còn trống / còn đúng bản cũ; chạy lại không đổi thêm.
 */
return new class extends Migration
{
    private const OLD_HIGHLIGHTS = [
        ['value' => '371', 'unit' => 'HP', 'label' => 'Công suất · RX 500h'],
        ['value' => '5,9', 'unit' => 'giây', 'label' => '0–100 km/h · RX 500h'],
        ['value' => 'AWD', 'unit' => '', 'label' => 'Dẫn động bốn bánh'],
        ['value' => '612', 'unit' => 'lít', 'label' => 'Khoang hành lý'],
    ];

    private const OLD_TAGLINE = 'SUV hạng sang cỡ trung, thuần hybrid';

    public const TAGLINE = 'SUV hybrid cỡ trung — RX 350h và RX 500h';

    public const HIGHLIGHTS = [
        ['value' => '2.5L', 'unit' => '', 'label' => 'Hybrid · RX 350h'],
        ['value' => 'AWD', 'unit' => '', 'label' => 'E-Four · RX 350h'],
        ['value' => '612', 'unit' => 'lít', 'label' => 'Khoang hành lý'],
        ['value' => '371', 'unit' => 'HP', 'label' => 'Công suất · RX 500h'],
    ];

    public const NOTICE = 'Bạn đang tìm Lexus RX 350? Bản RX đang bán tại Lexus Thăng Long là RX 350h hybrid 2.5L '
        .'(Premium và Luxury) và RX 500h F SPORT Performance. Giá niêm yết và lăn bánh từng bản ngay bên dưới; '
        .'nhắn Zalo Thu Hà để nhận báo giá theo nơi đăng ký xe.';

    public function up(): void
    {
        if (! Schema::hasColumn('products', 'notice')) {
            Schema::table('products', function (Blueprint $table) {
                $table->text('notice')->nullable()->after('tagline');
            });
        }

        $rx = Product::query()->where('slug', 'rx')->first();
        if (! $rx) {
            return;
        }

        if (blank($rx->notice)) {
            $rx->notice = self::NOTICE;
        }
        if ($rx->tagline === self::OLD_TAGLINE) {
            $rx->tagline = self::TAGLINE;
        }
        if ($rx->highlights === self::OLD_HIGHLIGHTS) {
            $rx->highlights = self::HIGHLIGHTS;
        }
        if ($rx->isDirty()) {
            $rx->saveQuietly();
        }
    }

    public function down(): void
    {
        // Không xoá cột: ghi chú có thể đã sửa trong admin.
    }
};
