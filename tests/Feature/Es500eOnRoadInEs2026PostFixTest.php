<?php

namespace Tests\Feature;

use App\Models\Post;
use Tests\TestCase;

/**
 * Kiểm tra site thật 12/11/2026: bài lexus-es2026-bang-gia-va-gia-lan-banh vẫn
 * ghi lăn bánh ES 500e "2.994.000.000 đ (khoảng 2,95 tỷ)" — đúng là
 * 2.954.000.000 đ (2,94 tỷ + biển số 14 triệu, miễn trước bạ), như chính bài
 * đó viết "chỉ chênh lệch 14 triệu". Chỉ thay đúng chữ cũ trong đúng bài.
 */
class Es500eOnRoadInEs2026PostFixTest extends TestCase
{
    private function migrate(): void
    {
        (require database_path('migrations/2026_11_12_100000_fix_es_500e_on_road_in_es2026_post.php'))->up();
    }

    public function test_sua_lan_banh_es_500e_trong_bai_es_2026(): void
    {
        $post = Post::create(['slug' => 'lexus-es2026-bang-gia-va-gia-lan-banh', 'title' => 'ES 2026', 'status' => 'published',
            'excerpt' => 'ES 500e lăn bánh 2.994.000.000 đ',
            'sections' => [['type' => 'rich_text', 'data' => ['body' => '<td>2.994.000.000 đ (khoảng 2,95 tỷ)</td> chỉ là 2.994.000.000 đ (khoảng 2,95 tỷ đồng)']]]]);
        $other = Post::create(['slug' => 'bai-khac', 'title' => 'Bài khác', 'status' => 'published', 'excerpt' => '2.994.000.000 đ']);

        $this->migrate();
        $this->migrate();

        $post->refresh();
        $this->assertSame('ES 500e lăn bánh 2.954.000.000 đ', $post->excerpt);
        $this->assertSame('<td>2.954.000.000 đ (khoảng 2,95 tỷ)</td> chỉ là 2.954.000.000 đ (khoảng 2,95 tỷ đồng)', $post->sections[0]['data']['body']);
        $this->assertSame('2.994.000.000 đ', $other->refresh()->excerpt, 'bài khác không bị đụng');
    }
}
