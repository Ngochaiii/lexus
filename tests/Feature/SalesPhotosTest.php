<?php

namespace Tests\Feature;

use App\Media\MediaStore;
use App\Models\Page;
use App\Models\Product;
use Tests\TestCase;

/**
 * Site đang chạy: thay bộ xoay 360° tải từ lexus.com (xe bản nước ngoài) bằng
 * bộ ảnh góc từ Car-project (ảnh sale, xe bản Việt Nam) rồi xoá ảnh 360° khỏi
 * kho media — để trên site không còn ảnh nào lấy từ lexus.com.
 */
class SalesPhotosTest extends TestCase
{
    private function migrate(): void
    {
        (require database_path('migrations/2026_10_04_120000_sales_photos_instead_of_lexus_com.php'))->up();
    }

    public function test_doi_bo_360_sang_anh_goc_cua_sale_va_xoa_anh_360(): void
    {
        $media = app(MediaStore::class);
        $media->write('catalog/lexus/lm/hero.webp', 'ảnh mẫu template');
        $spin = array_map(fn ($n) => sprintf('catalog/lexus/es/360/trang/%02d.webp', $n), range(1, 18));
        foreach ([...$spin, 'catalog/lexus/es/360/xam/04.webp'] as $path) {
            $media->write($path, 'lexus.com');
        }

        $es = Product::create(['name' => 'Lexus ES', 'slug' => 'es', 'status' => 'published']);
        $white = $es->options()->create(['name' => 'Trắng', 'spin_frames' => $spin, 'image' => 'catalog/lexus/es/mau-trang.webp']);
        $variant = $es->variants()->create(['name' => 'ES 500e', 'price' => 2_980_000_000, 'image' => 'catalog/lexus/es/360/trang/04.webp']);
        $page = Page::create(['slug' => 'bai', 'title' => 'Bài', 'status' => 'published', 'sections' => [
            ['type' => 'cards', 'items' => [['image' => 'catalog/lexus/es/360/xam/04.webp', 'label' => 'ES 350h Luxury']]],
        ]]);

        $this->migrate();
        $this->migrate(); // chạy lại không lỗi

        $frames = $white->fresh()->spin_frames;
        $this->assertSame(['catalog/lexus/es/goc/trang-1.webp', 'catalog/lexus/es/goc/trang-2.webp',
            'catalog/lexus/es/goc/trang-3.webp', 'catalog/lexus/es/goc/trang-4.webp'], $frames);
        foreach ($frames as $frame) {
            $this->assertTrue($media->exists($frame), "đã chép {$frame} vào kho");
        }
        $this->assertSame('catalog/lexus/es/goc/trang-1.webp', $variant->fresh()->image);
        $this->assertSame('catalog/lexus/es/goc/xam-1.webp', $page->fresh()->sections[0]['items'][0]['image']);

        $this->assertFalse($media->exists('catalog/lexus/es/360/trang/04.webp'), 'ảnh lexus.com đã xoá khỏi kho');
        $this->assertSame(file_get_contents(database_path('seeders/media/lexus/lm/hero.webp')), $media->read('catalog/lexus/lm/hero.webp'), 'ảnh đầu trang LM là ảnh sale');
        $this->assertSame([], array_values(array_filter($media->allFiles('catalog/lexus'), fn ($f) => str_contains($f, '/360/'))));
    }
}
