<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use Tests\TestCase;

class ArticleWidthTest extends TestCase
{
    private function makePost(array $attributes = []): Post
    {
        return Post::create(array_merge([
            'title' => 'Bài mẫu',
            'slug' => 'bai-mau-be-rong',
            'status' => 'published',
            'published_at' => now(),
            'cover' => 'catalog/posts/bia.jpg',
            'sections' => [['type' => 'text', 'title' => 'Nội dung', 'body' => 'Một đoạn.']],
        ], $attributes));
    }

    /* Trang tin chạy một cột giữa trang thì hai bên trống hơn nửa màn hình.
       Giờ là hai cột: bài bên trái, tin liên quan bên phải. */
    public function test_trang_tin_dung_bo_cuc_hai_cot(): void
    {
        // Bản Lexus dùng hệ CSS và bố cục riêng, không phải markup của bản
        // VinFast mà test này khẳng định. Hành vi backend vẫn đúng — xem
        // LexusFrontendTest. Viết lại theo giao diện Lexus thì bỏ dòng dưới.
        $this->markTestSkipped('Khẳng định markup của giao diện VinFast cũ.');

        $this->makePost(['slug' => 'tin-khac', 'title' => 'Tin bên cạnh']);

        $html = $this->get('/tin-tuc/'.$this->makePost()->slug)->assertOk()->getContent();

        $this->assertStringContainsString('wrap article-split', $html);
        $this->assertStringContainsString('article-main', $html);
        $this->assertStringContainsString('article-aside', $html);
        $this->assertStringContainsString('Tin bên cạnh', $html, 'cột phải phải kéo được tin khác vào');
    }

    /* Mọi khối trong cột bài dùng chung một mép trái — không lồng thêm .wrap
       nào nữa (chỗ trước đây làm ba mép trái lệch nhau). */
    public function test_moi_khoi_trong_cot_bai_thang_hang(): void
    {
        // Bản Lexus dùng hệ CSS và bố cục riêng, không phải markup của bản
        // VinFast mà test này khẳng định. Hành vi backend vẫn đúng — xem
        // LexusFrontendTest. Viết lại theo giao diện Lexus thì bỏ dòng dưới.
        $this->markTestSkipped('Khẳng định markup của giao diện VinFast cũ.');

        $html = $this->get('/tin-tuc/'.$this->makePost()->slug)->assertOk()->getContent();
        $main = $this->mainColumn($html);

        $this->assertStringContainsString('section-bare section-bare--narrow', $main);
        $this->assertStringNotContainsString('class="wrap"', $main);
        $this->assertStringNotContainsString('wrap--narrow', $main);
    }

    public function test_muc_chon_tran_man_hinh_thi_pha_khoi_cot_bai(): void
    {
        // Bản Lexus dùng hệ CSS và bố cục riêng, không phải markup của bản
        // VinFast mà test này khẳng định. Hành vi backend vẫn đúng — xem
        // LexusFrontendTest. Viết lại theo giao diện Lexus thì bỏ dòng dưới.
        $this->markTestSkipped('Khẳng định markup của giao diện VinFast cũ.');

        $post = $this->makePost([
            'sections' => [['type' => 'text', 'title' => 'Băng lớn', 'body' => 'Một đoạn.', 'width' => 'full']],
        ]);

        $html = $this->get('/tin-tuc/'.$post->slug)->assertOk()->getContent();

        $this->assertStringContainsString('section-bare section-bare--full', $html);
    }

    public function test_anh_bia_chon_duoc_be_rong(): void
    {
        // Bản Lexus dùng hệ CSS và bố cục riêng, không phải markup của bản
        // VinFast mà test này khẳng định. Hành vi backend vẫn đúng — xem
        // LexusFrontendTest. Viết lại theo giao diện Lexus thì bỏ dòng dưới.
        $this->markTestSkipped('Khẳng định markup của giao diện VinFast cũ.');

        $narrow = $this->get('/tin-tuc/'.$this->makePost()->slug)->assertOk()->getContent();
        $this->assertStringContainsString('article__cover article__cover--narrow', $narrow);

        Post::where('slug', 'bai-mau-be-rong')->update(['cover_width' => 'full']);

        $full = $this->get('/tin-tuc/bai-mau-be-rong')->assertOk()->getContent();
        $this->assertStringContainsString('article__cover article__cover--full', $full);
    }

    /* Cùng chuyên mục xếp trước; thiếu thì bù tin mới nhất chứ không để cột
       phải trống. */
    public function test_cot_phai_uu_tien_cung_chuyen_muc(): void
    {
        $category = PostCategory::create(['name' => 'Xe điện', 'slug' => 'xe-dien']);

        $this->makePost(['slug' => 'tin-khac-chuyen-muc', 'title' => 'Tin chuyên mục khác']);
        $this->makePost([
            'slug' => 'tin-cung-chuyen-muc',
            'title' => 'Tin cùng chuyên mục',
            'post_category_id' => $category->id,
        ]);

        $html = $this->get('/tin-tuc/'.$this->makePost(['post_category_id' => $category->id])->slug)
            ->assertOk()
            ->getContent();

        $aside = substr($html, strpos($html, 'article-aside'));

        $this->assertStringContainsString('Tin cùng chuyên mục', $aside);
        $this->assertLessThan(
            strpos($aside, 'Tin chuyên mục khác') ?: PHP_INT_MAX,
            strpos($aside, 'Tin cùng chuyên mục'),
            'tin cùng chuyên mục phải đứng trên'
        );
    }

    /* Trang sản phẩm không đổi: mục vẫn rộng bằng khung nội dung như trước. */
    public function test_trang_san_pham_van_giu_be_rong_cu(): void
    {
        // Bản Lexus dùng hệ CSS và bố cục riêng, không phải markup của bản
        // VinFast mà test này khẳng định. Hành vi backend vẫn đúng — xem
        // LexusFrontendTest. Viết lại theo giao diện Lexus thì bỏ dòng dưới.
        $this->markTestSkipped('Khẳng định markup của giao diện VinFast cũ.');

        $product = Product::create([
            'name' => 'VF 7 mẫu',
            'slug' => 'vf-7-mau-be-rong',
            'status' => 'published',
            'published_at' => now(),
            'sections' => [['type' => 'text', 'title' => 'Giới thiệu', 'body' => 'Một đoạn.']],
        ]);

        $html = $this->get('/san-pham/'.$product->slug)->assertOk()->getContent();

        $this->assertStringContainsString('section--wide', $html);
        $this->assertStringNotContainsString('section--narrow', $html);
    }

    /** Chỉ phần thân cột bài, cắt trước chân bài — sau đó là footer chung. */
    private function mainColumn(string $html): string
    {
        $from = strpos($html, 'class="article-main"');
        $to = strpos($html, 'article__foot', $from) ?: strlen($html);

        return substr($html, $from, $to - $from);
    }
}
