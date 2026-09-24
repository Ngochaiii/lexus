<?php

namespace App\Support;

use App\Models\ProductVariant;

/**
 * So sánh chi phí nhiên liệu mỗi tháng giữa một biến thể xe điện và xe
 * xăng/dầu tương đương. Giá điện/nhiên liệu lấy từ config('catalog.fuel_calc')
 * — đổi theo thời điểm, không phải báo giá chính thức.
 */
class FuelCalculator
{
    /**
     * @return array{fuel_monthly: float, ev_monthly: float, save_monthly: float, save_yearly: float}
     */
    public static function compare(ProductVariant $variant, string $fuelType, float $litersPer100km, float $kmPerMonth): array
    {
        $prices = config('catalog.fuel_calc', []);
        $fuelPrice = $fuelType === 'dau'
            ? (float) ($prices['diesel_price'] ?? 0)
            : (float) ($prices['petrol_price'] ?? 0);

        $fuelMonthly = $kmPerMonth * ($litersPer100km / 100) * $fuelPrice;

        $evMonthly = ($variant->range_km ?? 0) > 0
            ? $kmPerMonth * ((float) $variant->battery_kwh / $variant->range_km) * (float) ($prices['electricity_price'] ?? 0)
            : 0.0;

        return [
            'fuel_monthly' => $fuelMonthly,
            'ev_monthly'   => $evMonthly,
            'save_monthly' => max(0.0, $fuelMonthly - $evMonthly),
            'save_yearly'  => max(0.0, ($fuelMonthly - $evMonthly) * 12),
        ];
    }

    /** Biến thể dùng để tính — mặc định, thiếu thì lấy biến thể đầu. */
    public static function variantFor(\App\Models\Product $product): ?ProductVariant
    {
        return $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
    }

    /** Biến thể có đủ dữ liệu để tính (battery_kwh + range_km) không. */
    public static function usable(?ProductVariant $variant): bool
    {
        return $variant && filled($variant->battery_kwh) && filled($variant->range_km);
    }
}
