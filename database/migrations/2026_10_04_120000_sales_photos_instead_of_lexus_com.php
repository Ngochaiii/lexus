<?php

use App\Media\MediaStore;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;

/**
 * Bỏ hẳn ảnh lexus.com khỏi site đang chạy (04/10/2026).
 *
 * Bộ xoay 360° của khung chọn màu tải từ lexus.com / Lexus Anh / Lexus Nhật —
 * xe bản nước ngoài, không có quyền dùng. Thay bằng bộ ảnh góc từ Car-project
 * (ảnh sale cung cấp, xe bản Việt Nam; database/seeders/media/lexus/goc,
 * goc-manifest.json), đổi mọi chỗ còn trỏ tới một khung 360° (ảnh phiên bản,
 * thẻ trong bài viết) sang ảnh góc đầu tiên cùng màu, rồi xoá ảnh 360° khỏi
 * kho media. Ảnh đầu trang LM (ảnh mẫu của template) thay bằng ảnh sale cùng
 * đường dẫn. Chạy lại không đổi thêm.
 */
return new class extends Migration
{
    private const SPIN = '#catalog/lexus/([a-z]+)/360/([a-z-]+)/\d+\.webp#';

    /** @var array<string, array<string, array<int, array{file: string}>>> */
    private array $manifest = [];

    public function up(): void
    {
        $this->manifest = json_decode((string) @file_get_contents(database_path('seeders/media/lexus/goc-manifest.json')), true) ?: [];

        foreach (ProductOption::query()->get() as $option) {
            $frames = (array) ($option->spin_frames ?? []);
            if (! $frames || ! preg_match(self::SPIN, (string) $frames[0], $m)) {
                continue;
            }
            $option->spin_frames = $this->angles($m[1], $m[2]) ?: null;
            if (is_string($option->image) && preg_match(self::SPIN, $option->image)) {
                $option->image = $this->replace($option->image);
            }
            $option->saveQuietly();
        }

        foreach (ProductVariant::query()->where('image', 'like', '%/360/%')->get() as $variant) {
            $variant->image = $this->replace($variant->image);
            $variant->saveQuietly();
        }

        foreach ([Product::query(), Post::query(), Page::query()] as $query) {
            $query->get()->each(fn (Model $model) => $this->rewrite($model));
        }

        $media = app(MediaStore::class);

        // Ảnh đầu trang LM: cùng đường dẫn, nội dung mới (LM500h6cho.webp).
        $hero = database_path('seeders/media/lexus/lm/hero.webp');
        if (is_file($hero) && $media->exists('catalog/lexus/lm/hero.webp')) {
            $media->write('catalog/lexus/lm/hero.webp', (string) file_get_contents($hero));
            foreach ($media->allFiles('catalog/_v/lexus/lm') as $path) {
                if (str_starts_with(basename($path), 'hero')) {
                    $media->delete($path);
                }
            }
        }

        // Bộ 360° và bộ RX demo 23/09/2026 (catalog/options/360/rx-demo, cũng
        // từ lexus.com) cùng các bản thu nhỏ của chúng.
        foreach (['catalog/lexus', 'catalog/_v/lexus', 'catalog/options/360/rx-demo', 'catalog/_v/options/360/rx-demo'] as $dir) {
            foreach ($media->allFiles($dir) as $path) {
                if (str_contains($path, '/360/')) {
                    $media->delete($path);
                }
            }
        }
    }

    public function down(): void
    {
        // Không hoàn tác: ảnh lexus.com không được dùng lại.
    }

    /** @return array<int, string> đường dẫn trong kho, đã chép file nếu thiếu */
    private function angles(string $slug, string $key): array
    {
        return collect($this->manifest[$slug][$key] ?? [])
            ->map(fn (array $frame) => $this->copy($slug, $frame['file']))
            ->filter()->values()->all();
    }

    /** Một khung 360° → ảnh góc đầu tiên cùng màu (không có thì null). */
    private function replace(?string $value): ?string
    {
        return preg_replace_callback(self::SPIN, fn ($m) => $this->angles($m[1], $m[2])[0] ?? $m[0], (string) $value);
    }

    private function copy(string $slug, string $name): ?string
    {
        $source = database_path("seeders/media/lexus/{$slug}/{$name}.webp");
        if (! is_file($source)) {
            return null;
        }

        $path = "catalog/lexus/{$slug}/{$name}.webp";
        $media = app(MediaStore::class);
        if (! $media->exists($path)) {
            $media->write($path, (string) file_get_contents($source));
        }

        return $path;
    }

    private function rewrite(Model $model): void
    {
        $walk = function ($value) use (&$walk) {
            if (is_array($value)) {
                return array_map($walk, $value);
            }

            return is_string($value) && str_contains($value, '/360/') ? $this->replace($value) : $value;
        };

        foreach (['hero', 'cover', 'sections', 'seo', 'gallery'] as $field) {
            if (! array_key_exists($field, $model->getAttributes())) {
                continue;
            }
            $old = $model->getAttribute($field);
            if ($old !== null && ($new = $walk($old)) !== $old) {
                $model->setAttribute($field, $new);
            }
        }

        if ($model->isDirty()) {
            $model->saveQuietly();
        }
    }
};
