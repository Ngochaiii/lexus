<?php

namespace App\Support;

use App\Media\ImageVariantBuilder;
use App\Media\MediaStore;
use Illuminate\Support\Str;

/**
 * Một chỗ duy nhất đổi giá trị người nhập gõ vào (ảnh, link video) thành
 * thứ nhúng được vào Blade. Frontend không đụng Flysystem/fileinfo.
 */
class Media
{
    /** @var array<string,array>|null */
    private static ?array $manifest = null;

    /**
     * URL của một ảnh. Nhận cả:
     *   - đường dẫn trên disk public: `catalog/sections/a.webp`
     *   - link ngoài: `https://…`
     *   - MẢNG đường dẫn — state của FileUpload là mảng, không phải chuỗi
     */
    public static function url(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = reset($path) ?: null;
        }

        if (blank($path) || ! is_string($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:', '/'])) {
            return $path;
        }

        return static::store()->url($path);
    }

    /**
     * srcset cho một ảnh, dựng từ các biến thể `php artisan catalog:images`
     * đã sinh. Trả null khi ảnh chưa có biến thể (link ngoài, ảnh vừa upload
     * chưa chạy lệnh) — lúc đó Blade chỉ đặt src như cũ, không vỡ gì.
     */
    public static function srcset(mixed $path): ?string
    {
        $entry = self::manifestEntry($path);

        if (! $entry || empty($entry['v'])) {
            return null;
        }

        $rel = self::normalise($path);

        $parts = array_map(
            fn (int $w) => static::store()->url(ImageVariantBuilder::variantPath($rel, $w)).' '.$w.'w',
            $entry['v'],
        );

        // Chỉ đưa bản gốc vào khi nó không lớn hơn bậc lớn nhất. Ảnh gốc 5760px
        // mà nằm trong srcset thì màn Retina sẽ chọn đúng nó (1440 logical =
        // 2880 device px), và ta lại tải về đúng tấm 867 KB muốn tránh.
        if ($entry['w'] <= max(ImageVariantBuilder::WIDTHS)) {
            $parts[] = static::store()->url($rel).' '.$entry['w'].'w';
        }

        return implode(', ', $parts);
    }

    /**
     * Kích thước thật của ảnh: `['w' => 1920, 'h' => 1080]`, hoặc null.
     * Đặt được width/height lên <img> thì trình duyệt chừa sẵn chỗ và trang
     * không bị giật khi ảnh tải xong.
     */
    public static function dimensions(mixed $path): ?array
    {
        $entry = self::manifestEntry($path);

        return $entry ? ['w' => $entry['w'], 'h' => $entry['h']] : null;
    }

    /** Đường dẫn tương đối trên disk public, hoặc null nếu là link ngoài. */
    private static function normalise(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = reset($path) ?: null;
        }

        if (blank($path) || ! is_string($path)) {
            return null;
        }

        // Blade phần lớn đã gọi catalog_image() trước, nên thứ truyền vào đây
        // thường là URL đầy đủ. Gỡ ngược về đường dẫn trên disk để tra manifest.
        $base = rtrim((string) config('media.url', '/storage'), '/').'/';

        if (Str::startsWith($path, $base)) {
            return Str::after($path, $base);
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return null;
        }

        if (Str::contains($path, $base)) {
            return Str::after($path, $base);
        }

        if (Str::contains($path, '/storage/')) {
            return Str::after($path, '/storage/');
        }

        return Str::startsWith($path, '/') ? null : $path;
    }

    private static function manifestEntry(mixed $path): ?array
    {
        $rel = self::normalise($path);

        return $rel ? (self::manifest()[$rel] ?? null) : null;
    }

    /** Manifest đọc một lần cho mỗi request. */
    private static function manifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $store = static::store();

        return self::$manifest = $store->exists(ImageVariantBuilder::MANIFEST)
            ? (json_decode((string) $store->read(ImageVariantBuilder::MANIFEST), true) ?: [])
            : [];
    }

    private static function store(): MediaStore
    {
        return app(MediaStore::class);
    }

    /**
     * Link YouTube/Vimeo người nhập dán vào → link nhúng iframe được.
     * Link lạ trả nguyên, để nhúng thẳng file mp4 hoặc player khác vẫn chạy.
     */
    public static function embed(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (preg_match('~youtu\.be/([\w-]+)~', $url, $m)
            || preg_match('~youtube\.com/(?:watch\?v=|embed/|shorts/)([\w-]+)~', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return $url;
    }

    /** File video tự host (mp4/webm) thì dùng thẻ <video>, còn lại là <iframe>. */
    public static function isFile(?string $url): bool
    {
        return filled($url) && Str::endsWith(Str::lower(parse_url($url, PHP_URL_PATH) ?? ''), ['.mp4', '.webm', '.ogv']);
    }
}
