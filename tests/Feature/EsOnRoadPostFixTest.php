<?php

namespace Tests\Feature;

use App\Models\Post;
use Tests\TestCase;

/**
 * Rà bài 11/11/2026: hai bài Gemini ghi sai lăn bánh so với trang xe —
 * ES 500e "2.994.000.000 đ" (đúng 2.954.000.000 đ = 2,94 tỷ + biển số 14 triệu,
 * miễn trước bạ) và ES 350h Luxury làm tròn "khoảng 2,9 tỷ" (đúng 2,93 tỷ).
 * Migration chỉ thay đúng chữ cũ trong đúng bài; chạy lại không đổi thêm.
 */
class EsOnRoadPostFixTest extends TestCase
{
    private function migrate(): void
    {
        (require database_path('migrations/2026_11_11_100000_fix_es_on_road_in_posts.php'))->up();
    }

    public function test_sua_lan_banh_es_500e_va_lam_tron_es_350h_luxury(): void
    {
        $es500 = Post::create(['slug' => 'gia-lan-banh-lexus-es-500e', 'title' => 'Giá lăn bánh ES 500e', 'status' => 'published',
            'excerpt' => 'Lăn bánh tạm tính 2.994.000.000 đ.',
            'seo' => ['description' => 'tạm tính 2.994.000.000 đ nhờ 0%'],
            'sections' => [['type' => 'rich_text', 'data' => ['body' => '<p>ở mức 2.994.000.000 đ (khoảng 2,95 tỷ)</p>']]]]);
        $es350 = Post::create(['slug' => 'gia-lan-banh-lexus-es-350h-tai-ha-noi', 'title' => 'Giá lăn bánh ES 350h', 'status' => 'published',
            'excerpt' => 'bản Luxury khoảng 2,9 tỷ đồng tại đại lý.',
            'sections' => [['type' => 'rich_text', 'data' => ['body' => '<p>2.926.000.000 đ (khoảng 2,9 tỷ) đối với bản Luxury</p>']]]]);
        $other = Post::create(['slug' => 'bai-khac', 'title' => 'Bài khác', 'status' => 'published', 'excerpt' => 'khoảng 2,9 tỷ đồng và 2.994.000.000 đ']);

        $this->migrate();
        $this->migrate();

        $es500->refresh();
        $this->assertSame('Lăn bánh tạm tính 2.954.000.000 đ.', $es500->excerpt);
        $this->assertSame('tạm tính 2.954.000.000 đ nhờ 0%', $es500->seo['description']);
        $this->assertSame('<p>ở mức 2.954.000.000 đ (khoảng 2,95 tỷ)</p>', $es500->sections[0]['data']['body']);

        $es350->refresh();
        $this->assertSame('bản Luxury khoảng 2,93 tỷ đồng tại đại lý.', $es350->excerpt);
        $this->assertSame('<p>2.926.000.000 đ (khoảng 2,93 tỷ) đối với bản Luxury</p>', $es350->sections[0]['data']['body']);

        $this->assertSame('khoảng 2,9 tỷ đồng và 2.994.000.000 đ', $other->refresh()->excerpt, 'bài khác không bị đụng');
    }
}
