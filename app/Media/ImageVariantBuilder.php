<?php

namespace App\Media;

use Illuminate\Support\Str;

/**
 * Tạo các bản WebP responsive và ghi manifest dùng bởi component <x-img>.
 *
 * Bộ tạo ảnh chỉ dùng GD/getimagesize, không gọi fileinfo hay Flysystem.
 */
class ImageVariantBuilder
{
    /** @var array<int, int> */
    public const WIDTHS = [400, 800, 1280, 1920, 2560];

    public const DIR = 'catalog/_v';

    public const MANIFEST = self::DIR.'/manifest.json';

    /** Cỡ ảnh chia sẻ Facebook/Zalo khuyên dùng (1,91:1). */
    public const SHARE_SIZE = [1200, 630];

    public function __construct(private readonly MediaStore $media) {}

    /**
     * @return array{0:int,1:array{w:int,h:int,v:array<int,int>}}
     */
    public function build(string $path, bool $force = false): array
    {
        if (! function_exists('imagewebp')) {
            throw new \RuntimeException('PHP-GD chưa hỗ trợ WebP.');
        }

        // Trình duyệt mới gửi ảnh tối đa 1920px. Mức này vẫn giữ đường lui an
        // toàn khi một trình duyệt cũ gửi thẳng ảnh gốc có độ phân giải lớn.
        ini_set('memory_limit', '512M');

        $full = $this->media->absolutePath($path);
        $info = @getimagesize($full);

        if (! $info) {
            throw new \RuntimeException('Không đọc được kích thước ảnh.');
        }

        [$sourceWidth, $sourceHeight] = $info;
        $widths = array_values(array_filter(
            self::WIDTHS,
            fn (int $width): bool => $width < $sourceWidth,
        ));

        $entry = ['w' => $sourceWidth, 'h' => $sourceHeight, 'v' => $widths];
        // Làm lại biến thể còn thiếu HOẶC cũ hơn ảnh gốc. Lỗi từng gặp: tải lại
        // bộ ảnh 360° (cắt lại khung) nhưng srcset vẫn phục vụ bản cũ vì chỉ
        // kiểm tra "file đã tồn tại". Thay ảnh cùng tên trong admin/seeder
        // cũng rơi vào đúng trường hợp này.
        $sourceTime = @filemtime($full) ?: 0;
        $needed = $force ? $widths : array_values(array_filter(
            $widths,
            function (int $width) use ($path, $sourceTime): bool {
                $variant = self::variantPath($path, $width);

                return ! $this->media->exists($variant)
                    || (@filemtime($this->media->absolutePath($variant)) ?: 0) < $sourceTime;
            },
        ));

        if ($needed === []) {
            return [0, $entry];
        }

        $contents = @file_get_contents($full);
        $source = is_string($contents) ? @imagecreatefromstring($contents) : false;

        if (! $source) {
            throw new \RuntimeException('PHP-GD không giải mã được ảnh.');
        }

        $count = 0;

        try {
            foreach ($needed as $width) {
                $height = (int) round($sourceHeight * ($width / $sourceWidth));
                $target = imagecreatetruecolor($width, $height);

                if (! $target) {
                    throw new \RuntimeException("Không cấp phát được ảnh {$width}px.");
                }

                try {
                    imagealphablending($target, false);
                    imagesavealpha($target, true);
                    imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
                    imagecopyresampled(
                        $target,
                        $source,
                        0,
                        0,
                        0,
                        0,
                        $width,
                        $height,
                        $sourceWidth,
                        $sourceHeight,
                    );

                    ob_start();
                    $encoded = imagewebp($target, null, $this->quality());
                    $webp = ob_get_clean();

                    if (! $encoded || ! is_string($webp) || $webp === '') {
                        throw new \RuntimeException("Không mã hoá được bản WebP {$width}px.");
                    }

                    $this->media->write(self::variantPath($path, $width), $webp);
                    $count++;
                } finally {
                    imagedestroy($target);
                }
            }
        } finally {
            imagedestroy($source);
        }

        return [$count, $entry];
    }

    /**
     * Gộp manifest dưới lock để hai upload đồng thời không ghi đè dữ liệu nhau.
     *
     * @param  array<string,array{w:int,h:int,v:array<int,int>}>  $entries
     * @return array<string,array{w:int,h:int,v:array<int,int>}>
     */
    public function mergeManifest(array $entries): array
    {
        $lockPath = $this->media->absolutePath(self::DIR.'/.manifest.lock');
        $lockDirectory = dirname($lockPath);

        if (! is_dir($lockDirectory) && ! @mkdir($lockDirectory, 0775, true) && ! is_dir($lockDirectory)) {
            throw new \RuntimeException('Không tạo được thư mục manifest ảnh.');
        }

        $lock = @fopen($lockPath, 'c+');

        if (! is_resource($lock) || ! flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }

            throw new \RuntimeException('Không khoá được manifest ảnh.');
        }

        try {
            $manifest = $this->readManifest();

            foreach ($entries as $path => $entry) {
                $manifest[$path] = $entry;
            }

            ksort($manifest);

            $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if (! is_string($json)) {
                throw new \RuntimeException('Không mã hoá được manifest ảnh.');
            }

            $this->media->write(self::MANIFEST, $json);

            return $manifest;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Ảnh chia sẻ (og:image) JPG 1200×630, cắt giữa. Zalo không hiện ảnh xem
     * trước khi og:image là WebP, nên ảnh chia sẻ luôn là JPG. Sinh lần đầu
     * trang cần tới (hoặc khi ảnh gốc mới hơn), sau đó chỉ kiểm tra file.
     */
    public function share(string $path): string
    {
        $target = self::sharePath($path);
        $full = $this->media->absolutePath($path);
        $sourceTime = @filemtime($full) ?: 0;

        if ($this->media->exists($target)
            && (@filemtime($this->media->absolutePath($target)) ?: 0) >= $sourceTime) {
            return $target;
        }

        ini_set('memory_limit', '512M');

        $contents = @file_get_contents($full);
        $source = is_string($contents) ? @imagecreatefromstring($contents) : false;

        if (! $source) {
            throw new \RuntimeException('PHP-GD không giải mã được ảnh.');
        }

        [$w, $h] = self::SHARE_SIZE;
        $canvas = imagecreatetruecolor($w, $h);

        try {
            // JPG không có nền trong suốt: PNG trong suốt (logo) nằm trên nền trắng.
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            $sw = imagesx($source);
            $sh = imagesy($source);
            $scale = max($w / $sw, $h / $sh);
            $cropW = (int) round($w / $scale);
            $cropH = (int) round($h / $scale);
            imagecopyresampled($canvas, $source, 0, 0, (int) (($sw - $cropW) / 2), (int) (($sh - $cropH) / 2), $w, $h, $cropW, $cropH);

            ob_start();
            $encoded = imagejpeg($canvas, null, 85);
            $jpg = ob_get_clean();

            if (! $encoded || ! is_string($jpg) || $jpg === '') {
                throw new \RuntimeException('Không mã hoá được ảnh chia sẻ JPG.');
            }

            $this->media->write($target, $jpg);
        } finally {
            imagedestroy($canvas);
            imagedestroy($source);
        }

        return $target;
    }

    /** catalog/a/b.webp → catalog/_v/a/b-share.jpg */
    public static function sharePath(string $path): string
    {
        $relative = Str::after($path, 'catalog/');
        $directory = trim(pathinfo($relative, PATHINFO_DIRNAME), '.');

        return self::DIR.'/'.($directory !== '' ? $directory.'/' : '').pathinfo($relative, PATHINFO_FILENAME).'-share.jpg';
    }

    public static function variantPath(string $path, int $width): string
    {
        $relative = Str::after($path, 'catalog/');
        $directory = trim(pathinfo($relative, PATHINFO_DIRNAME), '.');
        $base = pathinfo($relative, PATHINFO_FILENAME);

        return self::DIR.'/'.($directory !== '' ? $directory.'/' : '').$base.'-'.$width.'.webp';
    }

    /** @return array<string,array{w:int,h:int,v:array<int,int>}> */
    private function readManifest(): array
    {
        if (! $this->media->exists(self::MANIFEST)) {
            return [];
        }

        $manifest = json_decode((string) $this->media->read(self::MANIFEST), true);

        return is_array($manifest) ? $manifest : [];
    }

    private function quality(): int
    {
        return min(100, max(40, (int) config('media.client_image_quality', 82)));
    }
}
