<?php

namespace App\Filament\Schemas;

use App\Support\Money;
use Filament\Forms\Components\TextInput;

/**
 * Ô nhập tiền. Giá xe là số 10 chữ số (5990000000) — gõ thiếu hay thừa một
 * số 0 thì mắt không bắt được, mà lỗi đó lộ ra ngoài trang bán hàng.
 *
 * Không dùng mask JS: bản Filament này không bundle plugin mask của Alpine,
 * nên `$money($input)` sẽ im lặng không chạy. PHP nhóm ba chữ số khi nạp
 * form và khi blur, đồng thời in cách đọc ngắn ngay bên dưới.
 */
class MoneyInput
{
    /** Giá cao hơn mốc này vẫn lưu được, nhưng phải nhắc kiểm tra số 0. */
    private const REVIEW_ABOVE = 100_000_000_000;

    public static function make(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            // KHÔNG dùng ->numeric(): input type=number coi dấu chấm là dấu
            // thập phân, nên "853.100.000" bị đọc thành 853,1 và lưu vào cột
            // decimal thành 853.10 — sai một tỷ lần mà không báo lỗi gì.
            ->inputMode('numeric')
            ->placeholder('Ví dụ: 853.000.000')
            ->suffix('₫')
            ->formatStateUsing(fn (mixed $state): ?string => static::formatForInput($state))
            ->live(onBlur: true)
            ->afterStateUpdated(function (TextInput $component, mixed $state): void {
                $component->state(static::formatForInput($state));
            })
            ->dehydrateStateUsing(fn (mixed $state) => static::toNumber($state))
            ->helperText(fn (mixed $state): ?string => static::hint($state));
    }

    /**
     * "853.100.000" · "853,100,000" · "853 100 000" · "853100000" → 853100000
     *
     * Giá xe tính bằng đồng, không có phần lẻ, nên mọi ký tự không phải chữ số
     * đều là dấu phân cách người nhập gõ cho dễ đọc.
     */
    public static function toNumber(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $value = trim((string) $state);

        // Cột decimal:2 trả "853000000.00" khi edit. Hai số 0 cuối
        // là scale của database, KHÔNG phải hai số 0 của giá.
        // Nếu bỏ mọi dấu ngay, mỗi lần lưu lại giá sẽ nhân 100.
        if (preg_match('/^\d+\.00$/', $value) === 1) {
            $value = substr($value, 0, -3);
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits === '' ? null : $digits;
    }

    /** "853000000.00" hoặc "853000000" → "853.000.000" trong ô nhập. */
    public static function formatForInput(mixed $state): ?string
    {
        $number = static::toNumber($state);

        if ($number === null) {
            return null;
        }

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', ltrim($number, '0') ?: '0');
    }

    protected static function hint(mixed $state): ?string
    {
        $number = static::toNumber($state);

        if ($number === null) {
            return null;
        }

        if ((float) $number > self::REVIEW_ABOVE) {
            return '⚠ Giá rất lớn: '.Money::format($number, '₫')
                .' — kiểm tra lại số 0 trước khi lưu.';
        }

        return 'Hiển thị: '.Money::format($number, '₫')
            .'  ·  '.Money::readable($number, '₫');
    }
}
