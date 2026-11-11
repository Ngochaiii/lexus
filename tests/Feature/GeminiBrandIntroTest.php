<?php

namespace Tests\Feature;

use App\Media\MediaStore;
use App\Models\Setting;
use App\Services\GeminiArticleWriter;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Đoạn giới thiệu thương hiệu cá nhân cuối mỗi bài Gemini viết (11/11/2026,
 * người dùng chốt "cách A"). Bài vẫn tập trung trả lời khách để SEO; cuối bài
 * có một mục "Về <thương hiệu>" do Gemini viết riêng cho chủ đề bài, kèm một
 * ảnh ngẫu nhiên (giao xe, tư vấn) lấy từ thư mục ảnh thương hiệu trong
 * Admin → Cài đặt. Đội TikTok/Facebook của chị Hà đã làm thương hiệu, website
 * gom thêm tên thương hiệu để tìm kiếm theo tên nối về đây.
 */
class GeminiBrandIntroTest extends TestCase
{
    private string $cover = 'catalog/posts/cover.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.key' => 'k', 'services.gemini.model' => 'gemini-test', 'services.gemini.google_search' => false]);
        Setting::put('advisor_name', 'Thu Hà');
        Setting::put('advisor_experience', 'Tư vấn Lexus tại Lexus Thăng Long từ 2017');
        Setting::put('tiktok', 'https://www.tiktok.com/@thuhalexus28');

        $store = app(MediaStore::class);
        $store->write($this->cover, UploadedFile::fake()->image('c.jpg', 1200, 675)->getContent());
    }

    private function fakeGemini(string $brandIntro): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode([
            'primary_keyword' => 'giá lăn bánh lexus rx 350h',
            'search_intent' => 'Tìm giá.',
            'secondary_keywords' => ['a', 'b', 'c', 'd', 'e'],
            'excerpt' => 'Tóm tắt.',
            'article_html' => '<p>'.str_repeat('Giá lăn bánh Lexus RX 350h tại Hà Nội. ', 20).'</p><p>Đoạn kết.</p>',
            'brand_intro' => $brandIntro,
            'seo_title' => 'Giá lăn bánh Lexus RX 350h',
            'meta_description' => str_repeat('m', 150),
            'slug' => 'gia-lan-banh-lexus-rx-350h',
            'faq' => [['question' => 'Hỏi?', 'answer' => 'Đáp.']],
            'keywords' => ['giá lăn bánh lexus rx 350h', 'b', 'c', 'd', 'e'],
        ], JSON_UNESCAPED_UNICODE)]]]]]])]);
    }

    private function photos(int $n): array
    {
        $paths = [];
        for ($i = 1; $i <= $n; $i++) {
            $path = "catalog/brand/giao-xe-{$i}.jpg";
            app(MediaStore::class)->write($path, UploadedFile::fake()->image("g{$i}.jpg", 900, 1200)->getContent());
            $paths[] = $path;
        }

        return $paths;
    }

    public function test_cuoi_bai_co_muc_thuong_hieu_kem_mot_anh_ngau_nhien_trong_thu_muc(): void
    {
        Setting::put('advisor_brand', 'Chị Đại Lexus');
        $photos = $this->photos(3);
        Setting::put('brand_photos', $photos);
        $this->fakeGemini('Chị Đại Lexus là tên thương hiệu của Thu Hà, tư vấn Lexus RX tại Lexus Thăng Long từ 2017.');

        $html = app(GeminiArticleWriter::class)->generate('Giá lăn bánh Lexus RX 350h', $this->cover)['article_html'];

        // Bài vẫn trả lời trước, mục thương hiệu nằm cuối.
        $this->assertStringStartsWith('<p>Giá lăn bánh Lexus RX 350h', $html);
        $this->assertGreaterThan(strpos($html, 'Đoạn kết.'), strpos($html, '<h2>Về Chị Đại Lexus'));
        $this->assertStringContainsString('Chị Đại Lexus là tên thương hiệu của Thu Hà', $html);

        preg_match('#<img[^>]*src="([^"]+)"[^>]*>#', $html, $img);
        $this->assertNotEmpty($img, 'có ảnh thương hiệu');
        $this->assertTrue(collect($photos)->contains(fn ($p) => str_contains($img[1], basename($p))), 'ảnh lấy từ thư mục ảnh thương hiệu');
        $this->assertStringContainsString('alt="Thu Hà – Chị Đại Lexus', $img[0]);
        $this->assertStringContainsString('width="900"', $img[0]);   // có kích thước để trang không nhảy
    }

    public function test_cau_lenh_bao_gemini_viet_doan_thuong_hieu_chi_tu_thong_tin_that(): void
    {
        Setting::put('advisor_brand', 'Chị Đại Lexus');
        $this->fakeGemini('Đoạn thương hiệu.');

        app(GeminiArticleWriter::class)->generate('Giá lăn bánh Lexus RX 350h', $this->cover);

        Http::assertSent(function (Request $request) {
            $prompt = collect($request->data()['contents'][0]['parts'])->pluck('text')->filter()->implode("\n");

            return str_contains($prompt, 'Chị Đại Lexus')
                && str_contains($prompt, 'brand_intro')
                && str_contains($prompt, 'https://www.tiktok.com/@thuhalexus28')
                && str_contains($prompt, 'không bịa')
                && in_array('brand_intro', $request->data()['generationConfig']['responseFormat']['text']['schema']['required'], true);
        });
    }

    public function test_chua_co_anh_thi_van_co_doan_thuong_hieu_khong_anh(): void
    {
        Setting::put('advisor_brand', 'Chị Đại Lexus');
        $this->fakeGemini('Đoạn thương hiệu.');

        $html = app(GeminiArticleWriter::class)->generate('Giá lăn bánh Lexus RX 350h', $this->cover)['article_html'];

        $this->assertStringContainsString('<h2>Về Chị Đại Lexus', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_chua_dat_ten_thuong_hieu_thi_khong_them_gi(): void
    {
        Setting::put('brand_photos', $this->photos(1));
        $this->fakeGemini('');

        $html = app(GeminiArticleWriter::class)->generate('Giá lăn bánh Lexus RX 350h', $this->cover)['article_html'];

        $this->assertStringNotContainsString('<h2>Về', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_admin_co_o_ten_thuong_hieu_va_thu_muc_anh(): void
    {
        $fields = collect(config('catalog.settings'))->flatMap(fn ($g) => $g['fields'] ?? []);

        $this->assertSame('text', $fields['advisor_brand']['type']);
        $this->assertSame('images', $fields['brand_photos']['type']);
    }
}
