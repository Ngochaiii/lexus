<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_dinh_dang_co_dau_phan_cach_nghin(): void
    {
        $this->assertSame('5.990.000.000 đ', Money::format(5_990_000_000));
        $this->assertSame('42.000.000 đ', Money::format('42000000'));
    }

    public function test_doc_duoc_bang_mat_de_bat_loi_thieu_mot_so_0(): void
    {
        $this->assertSame('5,99 tỷ đ', Money::readable(5_990_000_000));
        $this->assertSame('599 triệu đ', Money::readable(599_000_000));
        $this->assertSame('6 tỷ đ', Money::readable(6_000_000_000));
        $this->assertSame('42 triệu đ', Money::readable(42_000_000));
        $this->assertSame('900 đ', Money::readable(900));
    }

    public function test_khong_co_gia_thi_khong_in_gi(): void
    {
        $this->assertNull(Money::format(null));
        $this->assertNull(Money::format(''));
        $this->assertNull(Money::readable(null));
        $this->assertNull(Money::readable('chưa có giá'));
    }
}
