<?php

namespace App\Rules;

use App\Support\Phone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Chặn "số" kiểu 7365435505: đúng 10 chữ số nhưng không phải số VN, gọi
 * không được. Giá trị vào đây đã qua Phone::normalize() ở StoreLead.
 */
class VietnamPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Phone::isValid(is_string($value) ? $value : null)) {
            $fail('Số điện thoại không đúng định dạng.');
        }
    }
}
