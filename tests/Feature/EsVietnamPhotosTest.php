<?php

namespace Tests\Feature;

use App\Media\ImageVariantBuilder;
use App\Media\MediaStore;
use Tests\TestCase;

/**
 * 12/10/2026: ảnh ES chụp tại showroom Việt Nam (màu đồng, ảnh chi tiết, nội
 * thất) thay ảnh cũ cùng đường dẫn trên site đang chạy. Bộ ảnh màu trắng,
 * bạc, xám, xanh dương, đen mới trong Car-project là ảnh báo chí nước ngoài
 * (biển số Nhật, San Diego) — không nhập.
 */
class EsVietnamPhotosTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_10_12_100000_es_vietnam_photos.php';

    private function seedFile(string $name): string
    {
        return (string) file_get_contents(database_path("seeders/media/lexus/es/{$name}.webp"));
    }

    public function test_thay_anh_con_dung_ban_cu_va_lam_lai_ban_thu_nho(): void
    {
        $media = app(MediaStore::class);
        $migration = require database_path(self::MIGRATION);
        $old = (new \ReflectionClassConstant($migration, 'OLD_MD5'))->getValue();
        $this->assertCount(18, $old);

        // Không tái tạo được ảnh cũ có md5 thật → truyền md5 của nội dung giả.
        $media->write('catalog/lexus/es/goc/dong-1.webp', 'cu');
        $media->write('catalog/lexus/es/thu-vien-3.webp', 'anh admin tu thay');
        $media->write(ImageVariantBuilder::variantPath('catalog/lexus/es/goc/dong-1.webp', 400), 'thu nho cu');

        $migration->replace(['goc/dong-1' => md5('cu'), 'thu-vien-3' => md5('khac')]);
        $migration->replace(['goc/dong-1' => md5('cu'), 'thu-vien-3' => md5('khac')]); // chạy lại không đổi thêm

        $this->assertSame($this->seedFile('goc/dong-1'), $media->read('catalog/lexus/es/goc/dong-1.webp'));
        $this->assertNotSame('thu nho cu', $media->read(ImageVariantBuilder::variantPath('catalog/lexus/es/goc/dong-1.webp', 400)));
        $this->assertSame('anh admin tu thay', $media->read('catalog/lexus/es/thu-vien-3.webp'), 'ảnh đã thay tay thì giữ');
    }

    public function test_khong_nhap_anh_nuoc_ngoai(): void
    {
        $manifest = json_decode((string) file_get_contents(database_path('seeders/media/lexus/goc-manifest.json')), true);
        $this->assertSame(['es/dong-3.jpg', 'es/dong-1.jpg', 'es/dong-2.jpg', 'es/dong-4.jpg'],
            collect($manifest['es']['dong'])->pluck('source')->all());
        $notVn = file_get_contents(database_path('seeders/media/not_vn_sources.py'));
        foreach (['trang', 'bac', 'xam', 'xanhduong', 'den'] as $colour) {
            $this->assertStringContainsString("'{$colour}'", $notVn);
        }
    }
}
