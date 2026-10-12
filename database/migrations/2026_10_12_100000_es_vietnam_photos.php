<?php

use App\Media\ImageVariantBuilder;
use App\Media\MediaStore;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Ảnh ES chụp tại showroom Việt Nam (12/10/2026): bộ góc màu đồng, ảnh màu
 * đồng, ngoại thất, nội thất, thư viện và chi tiết — lấy từ Car-project commit
 * b15d486 qua database/seeders/media/import_*.py es. Ghi đè cùng đường dẫn
 * trong kho media khi ảnh trên site còn đúng bản cũ (md5 bản seed trước đó),
 * rồi làm lại bản thu nhỏ và ảnh chia sẻ. Ảnh đã thay tay thì giữ; chạy lại
 * không đổi thêm. Bộ ảnh trắng/bạc/xám/xanh dương/đen mới là ảnh báo chí nước
 * ngoài — không nhập (database/seeders/media/not_vn_sources.py).
 */
return new class extends Migration
{
    /** Tên ảnh trong catalog/lexus/es → md5 bản cũ. */
    private const OLD_MD5 = [
        'chi-tiet/dau-xe' => 'cdc8cc77963ff1f7454748750929b6ce',
        'chi-tiet/dong-ho-123' => '1e044bf4a6412dca9cd86227ad869bf8',
        'chi-tiet/duoi-xe' => '66a714c6dc613f30a02f3d5542f15063',
        'chi-tiet/hang-ghe-sau' => 'd1eb0e21bad335d456932bf0fb0bf360',
        'chi-tiet/hong-xe' => 'f2fb06d57ee197c1b92728c42d9a00ce',
        'chi-tiet/man-hinh-14' => '8b2189cca054d657ebf515d8a9a26326',
        'goc/dong-1' => '771cd002f9ae44356beaf15a576012e6',
        'goc/dong-2' => 'f412959c9dd0ebf686c245d292ff5971',
        'goc/dong-3' => 'f92e98a61ae3ed9d5cec506610457be2',
        'goc/dong-4' => '0d601ab11e600a9c753eba366fc5cfb5',
        'mau-dong' => 'ad83cb47c518a2b913ea9b8d77b0e6e9',
        'ngoai-that' => '8e228cf804355686aae6a22639b7b383',
        'noi-that' => 'f6c8c8ab0e0a03a3b5787f8732a80eed',
        'thu-vien-1' => '33dbf4f5b4b2ea4af7b8e099de7526fe',
        'thu-vien-2' => 'b787584eddc7f27c965ce123370247e2',
        'thu-vien-3' => 'd20c68a15ddc2693d0e8b73b22b8597b',
        'thu-vien-4' => '66a57e2b9b939764d29fcbb47718954e',
        'thu-vien-7' => 'dc3d2e2f10a005457471fd9142af2cb7',
    ];

    public function up(): void
    {
        $this->replace(self::OLD_MD5);
    }

    public function down(): void
    {
        // Không hoàn tác: ảnh cũ là ảnh mẫu cỡ nhỏ.
    }

    /** @param array<string, string> $oldMd5 */
    public function replace(array $oldMd5): void
    {
        $media = app(MediaStore::class);
        $builder = app(ImageVariantBuilder::class);
        $entries = [];

        foreach ($oldMd5 as $name => $md5) {
            $path = "catalog/lexus/es/{$name}.webp";
            $source = database_path("seeders/media/lexus/es/{$name}.webp");
            if (! is_file($source) || ! $media->exists($path) || md5((string) $media->read($path)) !== $md5) {
                continue;
            }

            $media->write($path, (string) file_get_contents($source));
            $share = ImageVariantBuilder::sharePath($path);
            if ($media->exists($share)) {
                $media->delete($share);
            }

            try {
                $entries[$path] = $builder->build($path, true)[1];
            } catch (\Throwable $e) {
                // Thiếu GD/WebP: ảnh gốc đã thay, bản thu nhỏ sẽ làm lại bằng catalog:images.
                Log::warning('Ảnh ES Việt Nam: chưa làm lại bản thu nhỏ', ['path' => $path, 'error' => $e->getMessage()]);
            }
        }

        if ($entries !== []) {
            $builder->mergeManifest($entries);
        }
    }
};
