<?php

namespace Tests\Unit;

use App\Support\SpecTableParser;
use PHPUnit\Framework\TestCase;

class SpecTableParserTest extends TestCase
{
    public function test_doc_duoc_bang_html_co_dong_tieu_de_nhom(): void
    {
        $html = <<<'HTML'
        <table>
          <tr><td colspan="2">Động Cơ &amp; Hiệu Năng</td></tr>
          <tr><td>Loại động cơ</td><td>V35A-FTS, 6 xi lanh chữ V</td></tr>
          <tr><td>Dung tích</td><td>3,445 cm³</td></tr>
          <tr><th colspan="2">Kích Thước</th></tr>
          <tr><td>Chiều dài</td><td>4.950 mm</td></tr>
        </table>
        HTML;

        $specs = (new SpecTableParser)->parse($html);

        $this->assertCount(2, $specs);
        $this->assertSame('Động Cơ & Hiệu Năng', $specs[0]['group']);
        $this->assertCount(2, $specs[0]['rows']);
        $this->assertSame(['label' => 'Dung tích', 'value' => '3,445 cm³'], $specs[0]['rows'][1]);
        $this->assertSame('Kích Thước', $specs[1]['group']);
    }

    public function test_bang_khong_co_tieu_de_nhom_thi_gom_vao_nhom_mac_dinh(): void
    {
        $specs = (new SpecTableParser('Thông số'))->parse('<table><tr><td>Pin</td><td>100 kWh</td></tr></table>');

        $this->assertSame('Thông số', $specs[0]['group']);
        $this->assertSame('100 kWh', $specs[0]['rows'][0]['value']);
    }

    public function test_khong_co_table_thi_doc_dang_nhan_hai_cham_gia_tri(): void
    {
        $specs = (new SpecTableParser)->parse("Dung tích: 3.4L\nCông suất: 349 mã lực");

        $this->assertCount(2, $specs[0]['rows']);
        $this->assertSame('349 mã lực', $specs[0]['rows'][1]['value']);
    }

    public function test_bo_khoang_trang_thua_va_nbsp(): void
    {
        $specs = (new SpecTableParser)->parse(
            "<table><tr><td>  Dung\u{a0}tích </td><td>3,445\n  cm³</td></tr></table>"
        );

        $this->assertSame('Dung tích', $specs[0]['rows'][0]['label']);
        $this->assertSame('3,445 cm³', $specs[0]['rows'][0]['value']);
    }

    public function test_input_rong_tra_ve_mang_rong(): void
    {
        $this->assertSame([], (new SpecTableParser)->parse('   '));
    }
}
