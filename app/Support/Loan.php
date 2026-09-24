<?php

namespace App\Support;

/**
 * Trả góp mua xe — dư nợ giảm dần.
 *
 * Gốc chia đều theo kỳ, lãi tính trên dư nợ CÒN LẠI, nên tiền trả tháng đầu
 * cao nhất rồi giảm dần. Đây là cách hầu hết ngân hàng Việt Nam áp dụng cho
 * vay mua ô tô — khác với trả đều hằng tháng (annuity) của thẻ tín dụng.
 *
 * Chỉ tính khoản vay và lãi. Lệ phí lăn bánh KHÔNG thuộc phạm vi ở đây.
 */
class Loan
{
    /**
     * @param  float  $price  giá xe
     * @param  float  $downPayment  số tiền trả trước
     * @param  float  $annualRate  lãi suất năm, đơn vị phần trăm (9 = 9%/năm)
     * @param  int  $months  số kỳ trả, tính theo tháng
     * @return array<string, float|int>
     */
    public static function schedule(float $price, float $downPayment, float $annualRate, int $months): array
    {
        $months = max(1, $months);

        // Trả trước bằng hoặc vượt giá xe thì không còn gì để vay.
        $principal = max(0.0, $price - $downPayment);

        $monthlyRate = $annualRate / 100 / 12;
        $monthlyPrincipal = $principal / $months;

        // Lãi kỳ đầu tính trên toàn bộ dư nợ; kỳ cuối chỉ còn đúng một phần gốc.
        $firstInterest = $principal * $monthlyRate;
        $lastInterest = $monthlyPrincipal * $monthlyRate;

        // Tổng lãi = P × r × (n+1) / 2
        $totalInterest = $principal * $monthlyRate * ($months + 1) / 2;

        return [
            'principal' => round($principal, 2),
            'months' => $months,
            'monthly_principal' => round($monthlyPrincipal, 2),
            'first_payment' => round($monthlyPrincipal + $firstInterest, 2),
            'last_payment' => round($monthlyPrincipal + $lastInterest, 2),
            'total_interest' => round($totalInterest, 2),
            'total_paid' => round($principal + $totalInterest, 2),
        ];
    }
}
