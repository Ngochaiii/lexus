<?php

namespace Tests\Unit;

use App\Support\Loan;
use PHPUnit\Framework\TestCase;

/**
 * Trả góp dư nợ giảm dần — chuẩn phổ biến của ngân hàng Việt Nam:
 * gốc chia đều theo kỳ, lãi tính trên dư nợ CÒN LẠI.
 */
class LoanTest extends TestCase
{
    public function test_tinh_dung_khoan_tra_dau_tien_va_cuoi_cung(): void
    {
        // Vay 800 triệu (xe 1 tỷ, trả trước 200 triệu), 12 tháng, 9%/năm.
        $r = Loan::schedule(1_000_000_000, 200_000_000, 9, 12);

        $this->assertSame(800_000_000.0, $r['principal']);
        $this->assertSame(12, $r['months']);
        $this->assertEqualsWithDelta(66_666_666.67, $r['monthly_principal'], 1);

        // Tháng đầu: gốc + 800tr × 9% / 12 = 66.666.667 + 6.000.000
        $this->assertEqualsWithDelta(72_666_666.67, $r['first_payment'], 1);

        // Tháng cuối: gốc + (800tr/12) × 9% / 12
        $this->assertEqualsWithDelta(67_166_666.67, $r['last_payment'], 1);
    }

    public function test_tong_lai_bang_cong_thuc_du_no_giam_dan(): void
    {
        $r = Loan::schedule(1_000_000_000, 200_000_000, 9, 12);

        // Tổng lãi = P × r/12 × (n+1)/2 = 800tr × 0,0075 × 6,5
        $this->assertEqualsWithDelta(39_000_000, $r['total_interest'], 1);
        $this->assertEqualsWithDelta(839_000_000, $r['total_paid'], 1);
    }

    public function test_lai_suat_khong_thi_chi_tra_goc(): void
    {
        $r = Loan::schedule(600_000_000, 100_000_000, 0, 10);

        $this->assertSame(0.0, $r['total_interest']);
        $this->assertEqualsWithDelta(50_000_000, $r['first_payment'], 1);
    }

    public function test_tra_truoc_vuot_gia_thi_khong_con_khoan_vay(): void
    {
        $r = Loan::schedule(500_000_000, 900_000_000, 9, 12);

        $this->assertSame(0.0, $r['principal']);
        $this->assertSame(0.0, $r['first_payment']);
        $this->assertSame(0.0, $r['total_interest']);
    }

    public function test_so_ky_bang_khong_thi_khong_chia_cho_khong(): void
    {
        $r = Loan::schedule(500_000_000, 0, 9, 0);

        $this->assertSame(1, $r['months']);
        $this->assertEqualsWithDelta(503_750_000, $r['first_payment'], 1);
    }
}
