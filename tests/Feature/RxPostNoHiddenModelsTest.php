<?php

namespace Tests\Feature;

use App\Models\Post;
use Tests\TestCase;

/**
 * Bài "giá lăn bánh RX 350h Hà Nội" (Gemini viết trên máy chủ) có đoạn gợi ý
 * NX 350h và GX 550M — hai mẫu đại lý không bán (ẩn khỏi web). Bỏ đúng đoạn đó.
 */
class RxPostNoHiddenModelsTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_10_05_110000_remove_nx_gx_550m_from_rx_post.php';

    public function test_xoa_doan_gioi_thieu_nx_va_gx_550m(): void
    {
        $body = '<p>Xem thêm <a href="/tin-tuc/lexus-rx-350h-hay-rx-500h-chon-ban-nao">RX 350h hay RX 500h</a>.</p>'
            .'<p>Nếu quý khách muốn cân nhắc một dòng SUV nhỏ gọn hơn với mức ngân sách thấp hơn, phiên bản '
            .'<a href="/san-pham/nx">Lexus NX</a> 350h với giá lăn bánh 3.676.400.000 đ cũng là một gợi ý đáng giá. '
            .'Ngược lại, nếu ưa thích phong cách SUV khung gầm rời mạnh mẽ, quý khách có thể tham khảo dòng '
            .'<a href="/san-pham/gx">Lexus GX</a> 550M với chi phí lăn bánh tạm tính là 7.182.000.000 đ.</p>'
            .'<h2 id="muc-tra-gop">Trả góp</h2>';
        $post = Post::create(['title' => 'Giá lăn bánh RX', 'slug' => 'gia-lan-banh-lexus-rx-350h-ha-noi', 'status' => 'published',
            'published_at' => now(), 'sections' => [['type' => 'text', 'body' => $body]]]);

        $migration = require database_path(self::MIGRATION);
        $migration->up();
        $migration->up();

        $this->assertSame(
            '<p>Xem thêm <a href="/tin-tuc/lexus-rx-350h-hay-rx-500h-chon-ban-nao">RX 350h hay RX 500h</a>.</p>'
            .'<h2 id="muc-tra-gop">Trả góp</h2>',
            $post->fresh()->sections[0]['body'],
        );
    }

    public function test_doan_khac_giu_nguyen(): void
    {
        $body = '<p>Lexus RX 350h giá lăn bánh khoảng 3,7 tỷ.</p>';
        $post = Post::create(['title' => 'Bài', 'slug' => 'bai', 'status' => 'published', 'published_at' => now(),
            'sections' => [['type' => 'text', 'body' => $body]]]);

        (require database_path(self::MIGRATION))->up();

        $this->assertSame($body, $post->fresh()->sections[0]['body']);
    }
}
