<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

/**
 * 05/11/2026: ngay dưới ghi chú RX đã có khối người tư vấn mời nhắn Zalo nơi
 * đăng ký xe (partials/advisor-strip) nên bỏ đoạn Zalo trùng trong ghi chú.
 * Chỉ thay khi còn đúng bản cũ; chạy lại không đổi thêm.
 */
return new class extends Migration
{
    private const OLD = 'Bạn đang tìm Lexus RX 350? Bản RX đang bán tại Lexus Thăng Long là RX 350h hybrid 2.5L '
        .'(Premium và Luxury) và RX 500h F SPORT Performance. Giá niêm yết và lăn bánh từng bản ngay bên dưới; '
        .'nhắn Zalo Thu Hà để nhận báo giá theo nơi đăng ký xe.';

    private const NEW = 'Bạn đang tìm Lexus RX 350? Bản RX đang bán tại Lexus Thăng Long là RX 350h hybrid 2.5L '
        .'(Premium và Luxury) và RX 500h F SPORT Performance. Giá niêm yết và lăn bánh từng bản ngay bên dưới.';

    public function up(): void
    {
        Product::query()->where('slug', 'rx')->where('notice', self::OLD)->get()
            ->each(fn (Product $rx) => $rx->forceFill(['notice' => self::NEW])->saveQuietly());
    }

    public function down(): void
    {
        // Không đổi lại: ghi chú có thể đã sửa trong admin.
    }
};
