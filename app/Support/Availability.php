<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Tình trạng xe của một phiên bản (admin → Dòng xe → Phiên bản):
 *   in_stock  — Có sẵn xe
 *   pre_order — Đặt trước (kèm ghi chú, vd "giao trong 2–4 tuần")
 *   contact   — Liên hệ để biết lịch xe
 * Để trống = không hiện nhãn. Dùng cho nhãn trên thẻ xe và schema.org Offer.
 */
class Availability
{
    public const OPTIONS = [
        'in_stock' => 'Có sẵn xe',
        'pre_order' => 'Đặt trước',
        'contact' => 'Liên hệ để biết lịch xe',
    ];

    private const SCHEMA = [
        'in_stock' => 'https://schema.org/InStock',
        'pre_order' => 'https://schema.org/PreOrder',
        'contact' => 'https://schema.org/LimitedAvailability',
    ];

    /** Nhãn hiển thị, vd "Đặt trước · giao trong 2–4 tuần". null = không hiện. */
    public static function label(?Model $variant): ?string
    {
        $key = $variant?->availability;
        if (! isset(self::OPTIONS[$key])) {
            return null;
        }

        return self::OPTIONS[$key].(filled($variant->availability_note) ? ' · '.$variant->availability_note : '');
    }

    public static function key(?Model $variant): ?string
    {
        return isset(self::OPTIONS[$variant?->availability]) ? $variant->availability : null;
    }

    /**
     * Tên các phiên bản có sẵn xe VÀ ghi chú có chữ "giao ngay" (18/11/2026) —
     * trang dòng xe hiện "<tên> có sẵn, giao ngay" ngay trên nút Gọi. Chỉ dựa
     * vào chữ admin gõ: hết xe thì sửa ghi chú là dòng tự mất.
     *
     * @param  iterable<Model>  $variants
     * @return list<string>
     */
    public static function readyNow(iterable $variants): array
    {
        $names = [];
        foreach ($variants as $variant) {
            if ($variant->availability === 'in_stock' && str_contains(mb_strtolower((string) $variant->availability_note), 'giao ngay')) {
                $names[] = $variant->name;
            }
        }

        return $names;
    }

    /** schema.org availability — trường admin thắng, không có thì đoán theo ghi chú "dự kiến". */
    public static function schema(Model $variant): string
    {
        return self::SCHEMA[$variant->availability] ?? (str_contains((string) $variant->note, 'dự kiến')
            ? 'https://schema.org/PreOrder'
            : 'https://schema.org/InStock');
    }
}
