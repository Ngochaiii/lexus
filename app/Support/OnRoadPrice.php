<?php

namespace App\Support;

use App\Models\ProductVariant;

/**
 * Giá lăn bánh TẠM TÍNH của một phiên bản: giá niêm yết + lệ phí trước bạ +
 * phí biển số (config catalog.on_road). Sau giá niêm yết, câu khách hỏi tiếp
 * luôn là "tổng cộng bao nhiêu" — trả lời ngay trên thẻ bớt được nỗi lo "phí
 * ẩn". Các khoản nhỏ (đăng kiểm, bảo trì đường bộ, bảo hiểm) không cộng và
 * trang ghi rõ, để con số không bao giờ thấp hơn thực tế một cách gây hiểu lầm.
 */
class OnRoadPrice
{
    /** null khi phiên bản chưa có giá. */
    public static function for(ProductVariant $variant): ?array
    {
        if (blank($variant->price) || ! is_numeric($variant->price)) {
            return null;
        }

        $config = config('catalog.on_road');
        $rate   = self::isBattery($variant) ? $config['ev_tax_rate'] : $config['tax_rate'];
        $tax    = (int) round($variant->price * $rate);

        return [
            'total'  => (int) $variant->price + $tax + (int) $config['plate_fee'],
            'tax'    => $tax,
            'rate'   => $rate,
            'plate'  => (int) $config['plate_fee'],
            'region' => $config['region'],
        ];
    }

    /** Xe điện chạy pin: "ES 500e", hoặc ghi chú "Thuần điện" (không phải hybrid). */
    public static function isBattery(ProductVariant $variant): bool
    {
        return (bool) preg_match('/\d{3}e\b/i', $variant->name)
            || (str_contains(mb_strtolower((string) $variant->note), 'điện')
                && ! str_contains(mb_strtolower($variant->name.' '.$variant->note), 'hybrid'));
    }
}
