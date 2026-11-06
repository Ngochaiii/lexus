<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

/**
 * Người dùng xác nhận 06/11/2026: đại lý của chị Hà chỉ có giao xe sớm, không bao
 * giờ có ưu đãi. Bỏ đúng các câu hứa ưu đãi của đại lý; giữ ưu đãi lệ phí trước bạ
 * của Nhà nước và thời gian ưu đãi lãi suất của ngân hàng. Chạy lại không đổi thêm.
 */
return new class extends Migration
{
    private const TEXT = [
        'theo phiên bản và ưu đãi đang áp dụng' => 'theo phiên bản',
        'theo phiên bản và ưu đãi hiện hành' => 'theo phiên bản',
        'theo thời điểm, cùng các chương trình ưu đãi riêng.' => 'theo thời điểm.',
    ];

    public function up(): void
    {
        foreach ([Product::class, Page::class, Post::class] as $model) {
            $model::query()->get()->each(function ($row) {
                $old = $row->sections;
                if ($old !== null && ($new = $this->walk($old)) !== $old) {
                    $row->sections = $new;
                    $row->saveQuietly();
                }
            });
        }
    }

    public function down(): void
    {
        // Không hoàn tác: câu cũ hứa điều đại lý không có.
    }

    private function walk(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->walk($v), $value);
        }

        return is_string($value) ? strtr($value, self::TEXT) : $value;
    }
};
