<?php

use App\Models\ProductVariant;
use Illuminate\Database\Migrations\Migration;

/**
 * Chị Hà xác nhận 18/11/2026: RX 350h Premium có sẵn, giao ngay. Ghi chú
 * "giao ngay" làm trang dòng xe hiện dòng "RX 350h Premium có sẵn, giao ngay"
 * ngay trên nút Gọi và trong khối người tư vấn (Availability::readyNow).
 *
 * Chỉ đổi khi còn đúng bản cũ (05/11: có sẵn · "nhiều màu, thay đổi theo
 * ngày") hoặc chưa đặt; admin đã đổi khác thì để nguyên. Chạy lại không đổi
 * thêm. Hết xe: sửa trong Admin → Dòng xe → Phiên bản.
 */
return new class extends Migration
{
    public function up(): void
    {
        ProductVariant::query()
            ->where('slug', 'rx-350h-premium')
            ->where(fn ($q) => $q->whereNull('availability')
                ->orWhere(fn ($q) => $q->where('availability', 'in_stock')
                    ->where(fn ($q) => $q->whereNull('availability_note')->orWhere('availability_note', 'nhiều màu, thay đổi theo ngày'))))
            ->get()
            ->each(fn (ProductVariant $variant) => $variant->forceFill(['availability' => 'in_stock', 'availability_note' => 'giao ngay'])->saveQuietly());
    }

    public function down(): void
    {
        // Không hoàn tác: tình trạng xe do admin quản lý từ đây.
    }
};
