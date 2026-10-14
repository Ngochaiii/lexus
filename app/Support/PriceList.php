<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Mô tả trang /bang-gia sinh từ giá phiên bản (trang sản phẩm là chuẩn): sửa
 * giá trong admin là mô tả đổi theo, không còn câu viết tay "GX 550 6,2 tỷ"
 * lệch với bảng ngay bên dưới.
 */
class PriceList
{
    public static function description(): ?string
    {
        $items = self::products()->map(function ($product) {
            $prices = $product->variants->pluck('price')->filter(fn ($p) => is_numeric($p) && $p > 0);
            if ($prices->isEmpty()) {
                return null;
            }
            $name = trim(preg_replace('/^Lexus\s+/u', '', $product->name));

            return $name.($prices->count() > 1 ? ' từ ' : ' ').catalog_money_short($prices->min());
        })->filter()->values();

        if ($items->isEmpty()) {
            return null;
        }

        return 'Bảng giá xe Lexus '.now()->year.': '.$items->implode(', ').'. '
            .'Giá lăn bánh '.config('catalog.on_road.region').' tạm tính từng phiên bản; nhắn Zalo chuyên viên để nhận báo giá chi tiết.';
    }

    /** Cùng danh sách với bảng giá (partials/price-table): xe đang bán có phiên bản, rẻ trước. */
    private static function products(): Collection
    {
        return Catalog::query('product')->published()
            ->with(['variants' => fn ($q) => $q->orderBy('sort')])
            ->get()
            ->filter(fn ($p) => $p->variants->isNotEmpty())
            ->sortBy(fn ($p) => $p->price_from ?? PHP_INT_MAX)
            ->values();
    }
}
