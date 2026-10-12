<?php

namespace Tests\Feature;

use App\Media\ImageVariantBuilder;
use App\Media\MediaStore;
use App\Support\Media;
use Tests\TestCase;

/**
 * URL ảnh có phiên bản (?v=<thời điểm sửa file>): thay ảnh cùng tên thì URL
 * đổi, Cloudflare (cache ảnh 30 ngày) tự lấy ảnh mới — lỗi gặp 04/10 (ảnh đầu
 * trang LM) và 12/10 (ảnh ES chụp tại Việt Nam).
 */
class MediaUrlVersionTest extends TestCase
{
    public function test_url_anh_co_phien_ban_theo_thoi_diem_sua_file(): void
    {
        $media = app(MediaStore::class);
        $media->write('catalog/test/xe.webp', 'cu');
        touch($media->absolutePath('catalog/test/xe.webp'), 1_700_000_000);
        Media::flushVersions();

        $old = Media::url('catalog/test/xe.webp');
        $this->assertSame('/storage/catalog/test/xe.webp?v='.base_convert('1700000000', 10, 36), $old);

        $media->write('catalog/test/xe.webp', 'moi');
        touch($media->absolutePath('catalog/test/xe.webp'), 1_760_000_000);
        Media::flushVersions();

        $this->assertNotSame($old, Media::url('catalog/test/xe.webp'), 'thay ảnh cùng tên thì URL đổi');
    }

    public function test_link_ngoai_va_file_khong_co_giu_nguyen(): void
    {
        $this->assertSame('https://example.com/a.jpg', Media::url('https://example.com/a.jpg'));
        $this->assertSame('/storage/catalog/khong-co.webp', Media::url('catalog/khong-co.webp'));
    }

    public function test_srcset_co_phien_ban(): void
    {
        $media = app(MediaStore::class);
        $path = 'catalog/test/rong.webp';
        $image = imagecreatetruecolor(1000, 600);
        ob_start();
        imagewebp($image);
        $media->write($path, (string) ob_get_clean());
        $builder = app(ImageVariantBuilder::class);
        $builder->mergeManifest([$path => $builder->build($path, true)[1]]);
        Media::flushVersions();

        $srcset = (string) Media::srcset($path);

        $this->assertMatchesRegularExpression('#/storage/catalog/_v/test/rong-400\.webp\?v=[0-9a-z]+ 400w#', $srcset);
        $this->assertMatchesRegularExpression('#/storage/catalog/test/rong\.webp\?v=[0-9a-z]+ 1000w#', $srcset);
    }

    // 12/10/2026: migration 04/10 xoá bản thu nhỏ ảnh đầu trang LM nhưng manifest
    // vẫn ghi → srcset trỏ vào file 404. Không liệt kê bản thu nhỏ không có file,
    // và migration dựng lại bản còn thiếu.
    public function test_srcset_bo_ban_thu_nho_khong_co_file_va_migration_dung_lai(): void
    {
        $media = app(MediaStore::class);
        $path = 'catalog/test/hero.webp';
        $image = imagecreatetruecolor(1000, 600);
        ob_start();
        imagewebp($image);
        $media->write($path, (string) ob_get_clean());
        $builder = app(ImageVariantBuilder::class);
        $builder->mergeManifest([$path => $builder->build($path, true)[1]]);
        $media->delete(ImageVariantBuilder::variantPath($path, 400));
        Media::flushVersions();

        $srcset = (string) Media::srcset($path);
        $this->assertStringNotContainsString('hero-400.webp', $srcset);
        $this->assertStringContainsString('hero-800.webp', $srcset);

        (require database_path('migrations/2026_10_12_110000_rebuild_missing_image_variants.php'))->up();
        Media::flushVersions();

        $this->assertTrue($media->exists(ImageVariantBuilder::variantPath($path, 400)));
        $this->assertStringContainsString('hero-400.webp', (string) Media::srcset($path));
    }
}
