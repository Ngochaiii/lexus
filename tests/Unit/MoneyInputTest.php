<?php

namespace Tests\Unit;

use App\Filament\Schemas\MoneyInput;
use PHPUnit\Framework\TestCase;

class MoneyInputTest extends TestCase
{
    public function test_decimal_tu_database_khong_bi_nhan_them_mot_tram(): void
    {
        $this->assertSame('853000000', MoneyInput::toNumber('853000000.00'));
        $this->assertSame('853.000.000', MoneyInput::formatForInput('853000000.00'));
    }

    public function test_nhan_duoc_cac_cach_go_gia_pho_bien(): void
    {
        foreach (['853.000.000', '853,000,000', '853 000 000', '853000000'] as $input) {
            $this->assertSame('853000000', MoneyInput::toNumber($input));
            $this->assertSame('853.000.000', MoneyInput::formatForInput($input));
        }
    }
}
