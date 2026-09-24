<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Product;
use App\Support\ArticleContext;
use App\Support\PostContent;
use App\Support\SeoCheck;
use Tests\TestCase;

/** Dữ liệu thật gửi kèm Gemini + FAQ bài viết + chấm SEO. */
class ArticleContextTest extends TestCase
{
    public function test_ho_so_co_gia_that_lan_banh_tinh_san_va_chi_tiet_dong_xe_duoc_nhac(): void
    {
        $rx = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now(),
            'specs' => [['group' => 'Động cơ', 'rows' => [['label' => 'Công suất', 'value' => '371 HP']]]]]);
        $rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000]);
        $es = Product::create(['name' => 'Lexus ES', 'slug' => 'es', 'status' => 'published', 'published_at' => now(),
            'specs' => [['group' => 'Động cơ', 'rows' => [['label' => 'Pin', 'value' => '74 kWh']]]]]);
        $es->variants()->create(['name' => 'ES 500e', 'price' => null, 'note' => 'Thuần điện']);

        $ctx = ArticleContext::build('Giá lăn bánh Lexus RX 350h tháng 10/2026');

        $this->assertStringContainsString('| Lexus RX | RX 350h Premium | 3.350.000.000 đ | 3.766.000.000 đ', $ctx);
        $this->assertStringContainsString('ES 500e | Đang cập nhật (KHÔNG được tự đoán giá)', $ctx);
        $this->assertStringContainsString('## CHI TIẾT LEXUS RX', $ctx);
        $this->assertStringContainsString('371 HP', $ctx);
        $this->assertStringNotContainsString('74 kWh', $ctx, 'chỉ gửi thông số dòng xe được nhắc trong tiêu đề');
        $this->assertStringContainsString('- Lexus RX: /san-pham/rx', $ctx);
        $this->assertStringContainsString('14.000.000 đ', $ctx);
    }

    public function test_faq_chu_thuong_chuyen_thanh_muc_hoi_dap_va_nguoc_lai(): void
    {
        $text = "Hỏi: Lăn bánh RX bao nhiêu?\nĐáp: Khoảng 3,77 tỷ\nđồng tại Hà Nội.\n\nHỏi: Có trả góp không?\nĐáp: Có, vay tới 70–80%.";
        $sections = PostContent::withFaq([['type' => 'text', 'body' => '<p>x</p>'], ['type' => 'faq', 'rows' => [['label' => 'cũ', 'value' => 'cũ']]]], $text);

        $this->assertCount(2, $sections);
        $this->assertSame('faq', $sections[1]['type']);
        $this->assertSame('Khoảng 3,77 tỷ đồng tại Hà Nội.', $sections[1]['rows'][0]['value']);
        $this->assertSame("Hỏi: Lăn bánh RX bao nhiêu?\nĐáp: Khoảng 3,77 tỷ đồng tại Hà Nội.\n\nHỏi: Có trả góp không?\nĐáp: Có, vay tới 70–80%.", PostContent::faqToText($sections));
        $this->assertCount(1, PostContent::withFaq($sections, ''), 'ô trống thì bỏ mục hỏi đáp');
    }

    public function test_bai_luu_faq_thi_trang_bai_co_schema_faqpage(): void
    {
        $post = Post::create(['title' => 'Giá lăn bánh RX', 'slug' => 'gia-lan-banh-rx', 'status' => 'published', 'published_at' => now(),
            'sections' => PostContent::withFaq([['type' => 'text', 'body' => '<p>Nội dung</p>']], "Hỏi: Lăn bánh RX bao nhiêu?\nĐáp: Khoảng 3,77 tỷ đồng.")]);

        $this->get('/tin-tuc/'.$post->slug)->assertOk()
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('Lăn bánh RX bao nhiêu?');
    }

    public function test_cham_seo_bai_gemini(): void
    {
        $checks = SeoCheck::article([
            'primary_keyword' => 'giá lăn bánh lexus rx',
            'article_html' => '<p>Giá lăn bánh Lexus RX từ 3,77 tỷ.</p><h2>a</h2><h2>b</h2><h2>c</h2><table></table>'
                .'<p><a href="/san-pham/rx">x</a><a href="/bang-gia">y</a><a href="/bao-gia">z</a> giá lăn bánh lexus rx</p>',
            'seo_title' => 'Giá lăn bánh Lexus RX 350h tại Hà Nội tháng 10/2026',
            'meta_description' => str_repeat('a', 150),
            'faq' => [1, 2, 3],
        ]);

        $ok = collect($checks)->pluck('ok', 'label');
        $this->assertTrue($ok->first(fn ($v, $k) => str_contains($k, 'đoạn mở đầu')));
        $this->assertTrue($ok->first(fn ($v, $k) => str_contains($k, 'link nội bộ')));
        $this->assertFalse($ok->first(fn ($v, $k) => str_contains($k, 'Độ dài')), 'bài ngắn bị cảnh báo');
    }
}
