<?php

namespace Tests\Feature;

use App\Models\Post;
use Tests\TestCase;

class RichTextTest extends TestCase
{
    private function render(string $body): string
    {
        $post = Post::create([
            'title' => 'Bài mẫu',
            'slug' => 'bai-mau-rich-text',
            'status' => 'published',
            'published_at' => now(),
            'sections' => [['type' => 'text', 'title' => 'Nội dung', 'body' => $body]],
        ]);

        return $this->get('/tin-tuc/'.$post->slug)->assertOk()->getContent();
    }

    public function test_giu_dinh_dang_khi_dan_tu_tai_lieu(): void
    {
        $html = $this->render(
            '<h2>Ưu đãi tháng 9</h2>'
            .'<p>Giảm <strong>80 triệu</strong> cho khách <em>đặt cọc sớm</em>.</p>'
            .'<ul><li>Tặng sạc</li><li>Miễn phí trước bạ</li></ul>'
        );

        $this->assertStringContainsString('<h2>Ưu đãi tháng 9</h2>', $html);
        $this->assertStringContainsString('<strong>80 triệu</strong>', $html);
        $this->assertStringContainsString('<li>Tặng sạc</li>', $html);
    }

    /* Word/Docs dán vào là kèm cả rừng span có style. Giữ chữ, bỏ vỏ. */
    public function test_bo_rac_dinh_dang_cua_word_nhung_giu_chu(): void
    {
        $html = $this->render('<p><span style="font-family:Calibri;mso-fareast-language:EN-US">Nội dung thật</span></p>');

        $this->assertStringContainsString('Nội dung thật', $html);
        $this->assertStringNotContainsString('mso-fareast-language', $html);
        $this->assertStringNotContainsString('font-family:Calibri', $html);
    }

    public function test_chan_ma_chay_duoc_va_link_javascript(): void
    {
        $html = $this->render(
            '<p onclick="steal()">Đoạn văn</p>'
            .'<script>alert(1)</script>'
            .'<a href="javascript:alert(1)">bấm đi</a>'
            .'<a href="https://vinfast.vn">trang hãng</a>'
        );

        $this->assertStringContainsString('Đoạn văn', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringContainsString('href="https://vinfast.vn"', $html);
        $this->assertStringContainsString('rel="noopener nofollow"', $html);
    }

    /* Bài nhập từ trước khi có ô soạn thảo là văn bản thuần — không được mất
       dấu xuống dòng. */
    public function test_noi_dung_cu_van_giu_dau_xuong_dong(): void
    {
        $html = $this->render("Dòng một\nDòng hai");

        $this->assertStringContainsString('Dòng một<br />', $html);
        $this->assertStringContainsString('Dòng hai', $html);
    }

    /* Kiểu mục "Thông báo": tên mục thành nhãn nhỏ BÊN TRONG hộp, không dựng
       thêm <h2> ở ngoài như các mục khác. */
    public function test_muc_thong_bao_dung_hop_rieng(): void
    {
        $post = Post::create([
            'title' => 'Bài có thông báo',
            'slug' => 'bai-co-thong-bao',
            'status' => 'published',
            'published_at' => now(),
            'sections' => [[
                'type' => 'notice',
                'title' => 'Thông báo dịch vụ',
                'body' => '<p>Kể từ ngày 01/03/2025, VinFast dừng cho thuê pin.</p>',
            ]],
        ]);

        $html = $this->get('/tin-tuc/'.$post->slug)->assertOk()->getContent();

        $this->assertStringContainsString('class="notice"', $html);
        $this->assertStringContainsString('notice__label">Thông báo dịch vụ', $html);
        $this->assertStringContainsString('VinFast dừng cho thuê pin.', $html);
        $this->assertStringNotContainsString('<h2>Thông báo dịch vụ</h2>', $html, 'tên mục chỉ được hiện một lần, trong hộp');
    }
}
