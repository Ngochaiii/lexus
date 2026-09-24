<?php

namespace Tests\Unit;

use App\Support\SeoText;
use PHPUnit\Framework\TestCase;

class SeoTextTest extends TestCase
{
    public function test_ngan_thi_giu_nguyen(): void
    {
        $this->assertSame('Giá xe Lexus RX.', SeoText::description('Giá xe Lexus RX.'));
    }

    public function test_dai_thi_cat_o_cuoi_cau(): void
    {
        $text = str_repeat('Lexus RX hybrid sang trọng ', 4).'kết thúc câu. '.str_repeat('Câu thứ hai rất dài ', 6);
        $out = SeoText::description($text);

        $this->assertLessThanOrEqual(160, mb_strlen($out));
        $this->assertStringEndsWith('kết thúc câu.', $out);
    }

    public function test_khong_co_cuoi_cau_thi_cat_o_ranh_gioi_tu(): void
    {
        $out = SeoText::description(str_repeat('phiên bản ', 40));

        $this->assertLessThanOrEqual(160, mb_strlen($out));
        $this->assertMatchesRegularExpression('/(phiên|bản)…$/u', $out); // không cắt ngang chữ
    }
}
