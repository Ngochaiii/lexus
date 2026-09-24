<?php

namespace App\Support;

/**
 * Tiền tệ hiển thị. Gom về một chỗ vì cùng con số xuất hiện ở frontend,
 * mail lead và helperText trong admin — ba nơi phải đọc giống nhau.
 */
class Money
{
    /** 5990000000 → "5.990.000.000 đ" */
    public static function format(mixed $amount, string $suffix = 'đ'): ?string
    {
        if (blank($amount) || ! is_numeric($amount)) {
            return null;
        }

        return trim(number_format((float) $amount, 0, ',', '.').' '.$suffix);
    }

    /**
     * 5990000000 → "5,99 tỷ" · 890000000 → "890 triệu"
     *
     * Ô nhập giá của hãng xe toàn số 10 chữ số, gõ thiếu/thừa một số 0 thì
     * mắt không bắt được. Câu này in ngay dưới ô để đối chiếu.
     */
    public static function readable(mixed $amount, string $suffix = 'đ'): ?string
    {
        if (blank($amount) || ! is_numeric($amount)) {
            return null;
        }

        $amount = (float) $amount;

        [$divisor, $unit] = match (true) {
            abs($amount) >= 1_000_000_000 => [1_000_000_000, 'tỷ'],
            abs($amount) >= 1_000_000     => [1_000_000, 'triệu'],
            abs($amount) >= 1_000         => [1_000, 'nghìn'],
            default                       => [1, ''],
        };

        $value = $amount / $divisor;

        // Bỏ số 0 lẻ ở cuối: 5,90 → 5,9 · 6,00 → 6
        $number = rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');

        // implode chứ không nối chuỗi: số dưới 1.000 không có đơn vị nào,
        // nối tay là dính hai dấu cách liền nhau.
        return implode(' ', array_filter([$number, $unit, $suffix]));
    }
}
