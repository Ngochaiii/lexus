<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use App\Support\PostToc;
use Tests\TestCase;

/** Cột phải trang bài viết: chuyên viên, giá dòng xe trong bài, mục lục. */
class PostAsideTest extends TestCase
{
    public function test_muc_luc_gan_neo_cho_h2_trong_than_bai_va_ten_muc(): void
    {
        [$sections, $toc] = PostToc::apply([
            ['type' => 'text', 'title' => 'Trả lời nhanh', 'intro' => 'Giá lăn bánh bao nhiêu?', 'body' => '<p>Khoảng 8,09 tỷ.</p>'],
            ['type' => 'text', 'body' => '<p>Mở đầu</p><h2>Bảng tính lăn bánh</h2><p>…</p><h2>Bảng tính lăn bánh</h2><h2 id="co-san">Giữ neo cũ</h2>'],
        ]);

        $this->assertSame([
            ['id' => 'tra-loi-nhanh', 'title' => 'Giá lăn bánh bao nhiêu?'],
            ['id' => 'muc-bang-tinh-lan-banh', 'title' => 'Bảng tính lăn bánh'],
            ['id' => 'muc-bang-tinh-lan-banh-2', 'title' => 'Bảng tính lăn bánh'],
        ], $toc);
        $this->assertStringContainsString('<h2 id="muc-bang-tinh-lan-banh">', $sections[1]['body']);
        $this->assertStringContainsString('<h2 id="co-san">', $sections[1]['body'], 'h2 đã có id thì giữ nguyên');
    }

    public function test_trang_bai_co_cot_phai_lien_he_gia_xe_va_muc_luc(): void
    {
        Setting::put('advisor_name', 'Thu Hà', 'home');
        Setting::put('hotline', '0989345989');
        Setting::put('zalo', 'https://zalo.me/0989345989', 'social');

        $lm = Product::create(['name' => 'Lexus LM', 'slug' => 'lm', 'status' => 'published', 'published_at' => now()]);
        $lm->variants()->create(['name' => 'LM 500h 6 chỗ', 'slug' => 'lm-500h-6-cho', 'price' => 7_210_000_000, 'sort' => 1]);

        Post::create([
            'title' => 'Giá lăn bánh Lexus LM 500h tại Hà Nội', 'slug' => 'gia-lan-banh-lm', 'status' => 'published',
            'sections' => [['type' => 'text', 'body' => '<p>Khoảng 8,09 tỷ.</p><h2>Bảng tính</h2><p>a</p><h2>Trả góp</h2><p>b</p><h2>Mua ở đâu</h2><p>c</p>']],
        ]);

        $html = $this->get('/tin-tuc/gia-lan-banh-lm')->assertOk()->getContent();

        $aside = substr($html, strpos($html, '<aside class="post-aside"'));
        $this->assertStringContainsString('href="tel:0989345989"', $aside);
        $this->assertStringContainsString('href="https://zalo.me/0989345989"', $aside);
        $this->assertMatchesRegularExpression('/data-quote\s+data-product="'.$lm->id.'"/', $aside, 'báo giá đúng dòng xe trong bài');
        $this->assertStringContainsString('href="/san-pham/lm/lm-500h-6-cho">LM 500h 6 chỗ</a>', $aside);
        $this->assertStringContainsString('7,21 tỷ', $aside);
        $this->assertStringContainsString('Lăn bánh khoảng 8,09 tỷ', $aside, 'cùng số với bảng giá (12% + 14 triệu)');
        $this->assertStringContainsString('href="#muc-tra-gop"', $aside);
        $this->assertStringContainsString('<h2 id="muc-tra-gop">', $html);
        $this->assertLessThan(strpos($html, '<aside class="post-aside"'), strpos($html, 'Khoảng 8,09 tỷ.'), 'thân bài đứng trước cột phải trong HTML');
    }

    public function test_bai_khong_noi_ve_dong_xe_nao_thi_khong_co_bang_gia(): void
    {
        Setting::put('hotline', '0989345989');
        Post::create(['title' => 'Showroom mở cửa Chủ nhật', 'slug' => 'showroom', 'status' => 'published',
            'sections' => [['type' => 'text', 'body' => '<p>Có.</p>']]]);

        $this->get('/tin-tuc/showroom')->assertOk()
            ->assertSee('Hỏi trực tiếp người viết')
            ->assertDontSee('aside-price', false)
            ->assertDontSee('Trong bài này');
    }
}
