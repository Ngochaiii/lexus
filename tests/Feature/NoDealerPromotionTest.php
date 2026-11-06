<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Tests\TestCase;

/**
 * Người dùng xác nhận 06/11/2026: đại lý của chị Hà chỉ có giao xe sớm, không bao
 * giờ có ưu đãi. Bỏ các câu hứa "ưu đãi" của đại lý. Giữ ưu đãi lệ phí trước bạ
 * của Nhà nước và thời gian ưu đãi lãi suất của ngân hàng vì đó không phải của đại lý.
 */
class NoDealerPromotionTest extends TestCase
{
    private function migrate(): void
    {
        (require database_path('migrations/2026_11_06_100000_remove_dealer_promotion_wording.php'))->up();
    }

    public function test_bo_cau_uu_dai_trong_xe_trang_va_bai(): void
    {
        $product = Product::create(['name' => 'Xe', 'slug' => 'xe-'.uniqid(), 'sections' => [['type' => 'faq', 'items' => [[
            'label' => 'Giá lăn bánh?',
            'value' => 'Để nhận bảng tính chi tiết theo phiên bản và ưu đãi đang áp dụng, bấm "Nhận báo giá".',
        ]]]]]);
        $page = Page::create(['title' => 'Trang', 'slug' => 'trang-'.uniqid(), 'sections' => [['type' => 'faq', 'items' => [[
            'label' => 'Giá xe có thay đổi không?',
            'value' => 'Giá do hãng công bố và có thể điều chỉnh theo thời điểm, cùng các chương trình ưu đãi riêng. Giá chốt là giá trên hợp đồng.',
        ]]]]]);
        $post = Post::create(['title' => 'Bài', 'slug' => 'bai-'.uniqid(), 'status' => 'published', 'published_at' => now(), 'sections' => [['type' => 'content',
            'body' => '<p>Bảng tính chính xác theo phiên bản và ưu đãi hiện hành, chuyên viên gửi riêng.</p>',
        ]]]);

        $this->migrate();

        $this->assertSame('Để nhận bảng tính chi tiết theo phiên bản, bấm "Nhận báo giá".', $product->fresh()->sections[0]['items'][0]['value']);
        $this->assertSame('Giá do hãng công bố và có thể điều chỉnh theo thời điểm. Giá chốt là giá trên hợp đồng.', $page->fresh()->sections[0]['items'][0]['value']);
        $this->assertSame('<p>Bảng tính chính xác theo phiên bản, chuyên viên gửi riêng.</p>', $post->fresh()->sections[0]['body']);
    }

    public function test_giu_uu_dai_cua_nha_nuoc_va_ngan_hang_chay_lai_khong_doi(): void
    {
        $tax = '<p>Nhờ chính sách ưu đãi lệ phí trước bạ 0% dành cho ô tô điện.</p>';
        $bank = '<p>Tỷ lệ vay, lãi suất và thời gian ưu đãi khác nhau giữa các ngân hàng.</p>';
        $post = Post::create(['title' => 'Bài', 'slug' => 'bai-'.uniqid(), 'status' => 'published', 'published_at' => now(), 'sections' => [['type' => 'content', 'body' => $tax]]]);
        $page = Page::create(['title' => 'Tài chính', 'slug' => 'tc-'.uniqid(), 'sections' => [['type' => 'content', 'body' => $bank]]]);

        $this->migrate();
        $this->migrate();

        $this->assertSame($tax, $post->fresh()->sections[0]['body']);
        $this->assertSame($bank, $page->fresh()->sections[0]['body']);
    }

    public function test_seeder_khong_con_hua_uu_dai_cua_dai_ly(): void
    {
        $this->assertStringNotContainsString('ưu đãi đang áp dụng', file_get_contents(database_path('seeders/Brands/LexusSeeder.php')));
        $site = file_get_contents(database_path('seeders/LexusSiteSeeder.php'));
        $this->assertStringNotContainsString('chương trình ưu đãi riêng', $site);
        $this->assertStringNotContainsString('ưu đãi hiện hành', $site);
    }
}
