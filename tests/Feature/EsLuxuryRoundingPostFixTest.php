<?php

namespace Tests\Feature;

use App\Models\Post;
use Tests\TestCase;

/**
 * Kiểm tra site thật 11/11/2026 sau khi sửa: ES 350h Luxury (lăn bánh
 * 2.926.000.000 đ) còn ghi "khoảng 2,9 tỷ" ở 3 bài khác, trong khi bản Premium
 * ghi "2,63 tỷ" — làm tròn không đều. Đổi thành "khoảng 2,93 tỷ" giống bài
 * gia-lan-banh-lexus-es-350h-tai-ha-noi; chỉ đúng chữ cũ trong đúng bài.
 */
class EsLuxuryRoundingPostFixTest extends TestCase
{
    private function migrate(): void
    {
        (require database_path('migrations/2026_11_11_120000_fix_es_luxury_rounding_in_posts.php'))->up();
    }

    public function test_doi_khoang_2_9_ty_thanh_2_93_ty_o_ba_bai_es(): void
    {
        $posts = collect(['gia-xe-lexus-es350h', 'gia-lan-banh-lexus-es-500e', 'lexus-es2026-bang-gia-va-gia-lan-banh'])
            ->map(fn ($slug) => Post::create(['slug' => $slug, 'title' => $slug, 'status' => 'published',
                'excerpt' => 'khoảng 2,63 tỷ đến khoảng 2,9 tỷ đồng',
                'seo' => ['description' => 'khoảng 2,9 tỷ đồng cho bản ES 350h Luxury'],
                'sections' => [['type' => 'rich_text', 'data' => ['body' => '<td>2.926.000.000 đ (khoảng 2,9 tỷ)</td> (lăn bánh tạm tính khoảng 2,9 tỷ đồng) và ES 500e khoảng 2,95 tỷ']]]]));
        $other = Post::create(['slug' => 'bai-khac', 'title' => 'Bài khác', 'status' => 'published', 'excerpt' => 'khoảng 2,9 tỷ đồng']);

        $this->migrate();
        $this->migrate();

        foreach ($posts as $post) {
            $post->refresh();
            $this->assertSame('khoảng 2,63 tỷ đến khoảng 2,93 tỷ đồng', $post->excerpt);
            $this->assertSame('khoảng 2,93 tỷ đồng cho bản ES 350h Luxury', $post->seo['description']);
            $this->assertSame('<td>2.926.000.000 đ (khoảng 2,93 tỷ)</td> (lăn bánh tạm tính khoảng 2,93 tỷ đồng) và ES 500e khoảng 2,95 tỷ',
                $post->sections[0]['data']['body']);
        }
        $this->assertSame('khoảng 2,9 tỷ đồng', $other->refresh()->excerpt, 'bài khác không bị đụng');
    }
}
