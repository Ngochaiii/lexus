<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Câu mở đầu trang /bang-gia (14/10/2026): bảng đã hiện lăn bánh tạm tính và
 * nút Zalo, nên câu cũ "Bấm Nhận báo giá để có giá lăn bánh" không còn đúng.
 * Chỉ thay khi còn đúng câu cũ; câu đã sửa trong admin thì giữ.
 */
return new class extends Migration
{
    private const OLD = 'Giá niêm yết từng phiên bản, cập nhật theo bảng giá của đại lý. Bấm "Nhận báo giá" để có giá lăn bánh chi tiết.';

    private const NEW = 'Giá niêm yết và giá lăn bánh Hà Nội tạm tính của từng phiên bản. Cần báo giá chi tiết theo nơi đăng ký xe, nhắn Zalo cho Thu Hà.';

    public function up(): void
    {
        $page = Page::query()->where('slug', 'bang-gia')->first();
        $seo  = $page?->seo;

        if (is_array($seo) && ($seo['excerpt'] ?? null) === self::OLD) {
            $seo['excerpt'] = self::NEW;
            $page->seo      = $seo;
            $page->saveQuietly();
        }
    }

    public function down(): void
    {
        // Không hoàn tác.
    }
};
