<?php

use App\Models\ProductVariant;
use Illuminate\Database\Migrations\Migration;

/**
 * Chị Hà xác nhận 23/11/2026: GX 550 có sẵn xe (tháng 11 tập trung RX 350h +
 * GX 550). Ghi chú "giao ngay" cho cả hai bản → trang GX hiện "Cả 2 bản Lexus
 * GX có sẵn, giao ngay" và nút "Hỏi màu còn xe" (Availability::readyLabel).
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
            ->whereIn('slug', ['gx-550-cua-troi-don', 'gx-550'])
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
