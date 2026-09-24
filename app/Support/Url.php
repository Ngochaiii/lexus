<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Một chỗ duy nhất dựng đường dẫn frontend từ config('catalog.routes').
 * Đổi hình dạng URL của một hãng chỉ sửa config, không đụng code.
 */
class Url
{
    /** Tiền tố của một loại, VD 'product' → '/san-pham'. */
    public static function prefix(string $type): string
    {
        return rtrim((string) config("catalog.routes.{$type}", ''), '/');
    }

    /** Đường dẫn tương đối tới một bản ghi theo slug. */
    public static function to(string $type, string $slug): string
    {
        $prefix = static::prefix($type);

        return $prefix === '' ? '/'.$slug : $prefix.'/'.$slug;
    }

    /** Đường dẫn tuyệt đối (kèm domain) — dùng cho sitemap, canonical, JSON-LD. */
    public static function absolute(string $type, string $slug): string
    {
        return rtrim(config('app.url'), '/').static::to($type, $slug);
    }

    /** URL tuyệt đối của named route, luôn dùng domain APP_URL cho SEO. */
    public static function route(string $name, array $parameters = []): string
    {
        $path = route($name, $parameters, false);

        return rtrim(config('app.url'), '/').'/'.ltrim($path, '/');
    }

    /** Canonical tự trỏ cho trang phân trang, không mang theo filter/UTM. */
    public static function paginated(string $base, int $page): string
    {
        $base = strtok($base, '?') ?: $base;

        return $page > 1 ? $base.'?page='.$page : $base;
    }

    /** URL ảnh tuyệt đối dùng cho canonical metadata và dữ liệu có cấu trúc. */
    public static function asset(mixed $path): ?string
    {
        $path = Media::url($path);

        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return $path;
        }

        $base = rtrim((string) config('app.url'), '/');

        if (Str::startsWith($path, '/')) {
            return $base.$path;
        }

        return $base.'/'.ltrim($path, '/');
    }
}
