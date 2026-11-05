<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use Tests\TestCase;

/**
 * Người dùng xác nhận 05/11/2026: giá ES, RX, LX, LM, LS giữ nguyên trong tháng
 * 11. Bài do Gemini viết trên máy chủ còn ghi tháng 10/2026 (hoặc thời điểm tháng
 * 9/2026) trong khi bảng giá và quảng cáo ghi tháng 11.
 */
class PostMonthNovemberTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_11_05_120000_posts_price_month_november.php';

    private function migrate(): void
    {
        (require database_path(self::MIGRATION))->up();
    }

    private function article(array $attributes): Post
    {
        return Post::create($attributes + ['title' => 'Bài', 'slug' => 'bai-'.uniqid(), 'status' => 'published', 'published_at' => now()]);
    }

    public function test_doi_thang_o_tieu_de_seo_va_noi_dung(): void
    {
        $post = $this->article([
            'title' => 'Giá lăn bánh Lexus RX 350h tại Hà Nội tháng 10/2026',
            'slug' => 'gia-lan-banh-lexus-rx-350h-ha-noi',
            'excerpt' => 'Cập nhật giá tháng 10/2026.',
            'seo' => ['title' => 'Giá RX tháng 10/2026 chi tiết', 'description' => 'Cập nhật tháng 10/2026'],
            'sections' => [['type' => 'content', 'body' => '<p>Áp dụng cho tháng 10/2026.</p>',
                'faq' => [['q' => 'Tháng này?', 'a' => 'Giá tháng 10/2026 là 3,77 tỷ.']]]],
        ]);

        $this->migrate();
        $post->refresh();

        $this->assertSame('Giá lăn bánh Lexus RX 350h tại Hà Nội tháng 11/2026', $post->title);
        $this->assertSame('gia-lan-banh-lexus-rx-350h-ha-noi', $post->slug, 'đường dẫn giữ nguyên');
        $this->assertStringNotContainsString('tháng 10/2026', json_encode($post->only('title', 'excerpt', 'seo', 'sections'), JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('3,77 tỷ', $post->sections[0]['faq'][0]['a'], 'không đụng giá');
    }

    public function test_cau_thoi_diem_thang_9_thanh_thang_11(): void
    {
        $post = $this->article(['sections' => [['type' => 'content', 'body' => '<p>Phản ánh mức thuế hiện hành tại thời điểm tháng 9/2026 cho giai đoạn tháng 10/2026. '
            .'Theo bảng giá hiện hành tại thời điểm viết bài (tháng 9/2026) và có thể điều chỉnh. '
            .'Dựa trên biểu thuế phí hiện hành vào thời điểm tháng 9/2026.</p>']]]);

        $this->migrate();
        $body = $post->fresh()->sections[0]['body'];

        $this->assertStringNotContainsString('tháng 9/2026', $body);
        $this->assertStringNotContainsString('tháng 10/2026', $body);
        $this->assertStringContainsString('tại thời điểm tháng 11/2026.', $body);
        $this->assertStringContainsString('tại thời điểm cập nhật (tháng 11/2026)', $body);
    }

    public function test_doi_ngay_cap_nhat_bang_gia_trong_bai_es_500e_va_lx(): void
    {
        $post = $this->article(['sections' => [['type' => 'content', 'body' => '<p>Theo bảng giá hiện hành cập nhật ngày 02/10/2026. '
            .'Dựa trên bảng giá chính thức cập nhật ngày 26/10/2026. Quy định phí hiện hành cập nhật ngày 26/10/2026.</p>']]]);

        $this->migrate();
        $body = $post->fresh()->sections[0]['body'];

        $this->assertStringNotContainsString('/10/2026', $body);
        $this->assertSame(3, substr_count($body, 'cập nhật ngày 05/11/2026'));
    }

    public function test_khong_dung_ngay_dang_va_trang_khac(): void
    {
        $post = $this->article(['sections' => [['type' => 'content', 'body' => '<p>Đại lý bán ES 500e từ 10/2026. Xe ra mắt ngày 15/10/2026.</p>']]]);
        $page = Page::create(['slug' => 'dieu-khoan', 'title' => 'Điều khoản', 'status' => 'published',
            'sections' => [['type' => 'content', 'body' => 'Cập nhật lần cuối: 06/10/2026.']]]);

        $this->migrate();

        $this->assertStringContainsString('từ 10/2026', $post->fresh()->sections[0]['body']);
        $this->assertStringContainsString('15/10/2026', $post->fresh()->sections[0]['body']);
        $this->assertStringContainsString('06/10/2026', $page->fresh()->sections[0]['body']);
    }

    public function test_chay_lai_khong_doi_them(): void
    {
        $post = $this->article(['title' => 'Giá tháng 10/2026']);

        $this->migrate();
        $title = $post->fresh()->title;
        $this->migrate();

        $this->assertSame($title, $post->fresh()->title);
    }
}
