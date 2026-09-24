<?php

namespace App\Console\Commands;

use App\Media\ImageVariantBuilder;
use App\Media\MediaStore;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Sinh các bản ảnh nhỏ hơn (WebP) cho mọi ảnh trong disk public để trang
 * không còn tải ảnh 5760px xuống khung 1440px.
 *
 *     php artisan catalog:images          # chỉ làm ảnh chưa có biến thể
 *     php artisan catalog:images --force  # làm lại tất cả
 *
 * Ảnh gốc KHÔNG bị đụng tới. Biến thể nằm trong `catalog/_v/…` theo đúng cây
 * thư mục gốc, kèm hậu tố chiều rộng:
 *
 *     catalog/vinfast/vinfast-vf-3/hero.jpg
 *  → catalog/_v/vinfast/vinfast-vf-3/hero-800.webp
 *
 * Kích thước thật của ảnh gốc được ghi vào manifest `catalog/_v/manifest.json`
 * để Blade đặt được width/height mà không phải mở file ở mỗi request —
 * thiếu width/height là nguyên nhân trang bị giật khi ảnh tải xong.
 */
class BuildImageVariants extends Command
{
    protected $signature = 'catalog:images {--force : Sinh lại cả những biến thể đã có}';

    protected $description = 'Sinh biến thể WebP nhiều kích cỡ cho ảnh trong disk public';

    /** Các chiều rộng cần sinh. Bỏ qua cỡ lớn hơn ảnh gốc — phóng to chỉ tổ nặng file. */
    public const WIDTHS = ImageVariantBuilder::WIDTHS;

    /** Thư mục chứa biến thể, nằm trong disk public. */
    public const DIR = ImageVariantBuilder::DIR;

    public const MANIFEST = ImageVariantBuilder::MANIFEST;

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('GD không có WebP. Cài lại PHP với --with-webp rồi chạy lại.');

            return self::FAILURE;
        }

        // Ảnh gốc tới 5760×3240 ngốn ~75 MB khi bung ra bitmap.
        ini_set('memory_limit', '512M');

        $media = app(MediaStore::class);
        $builder = app(ImageVariantBuilder::class);
        $force = (bool) $this->option('force');

        $sources = collect($media->allFiles('catalog'))
            ->reject(fn (string $p) => Str::startsWith($p, self::DIR.'/'))
            ->filter(fn (string $p) => in_array(Str::lower(pathinfo($p, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true))
            ->values();

        if ($sources->isEmpty()) {
            $this->warn('Không tìm thấy ảnh nào trong catalog/.');

            return self::SUCCESS;
        }

        $entries = [];
        $bar = $this->output->createProgressBar($sources->count());
        $bar->start();

        $made = 0;
        $kept = 0;
        $failed = [];

        foreach ($sources as $path) {
            try {
                [$n, $entry] = $builder->build($path, $force);
                $made += $n;
                $kept += $n === 0 ? 1 : 0;
                $entries[$path] = $entry;
            } catch (\Throwable $e) {
                $failed[] = $path.' — '.$e->getMessage();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $manifest = $builder->mergeManifest($entries);

        $this->info("Ảnh gốc: {$sources->count()} · biến thể mới: {$made} · đã có sẵn: {$kept}");
        $this->line('Manifest: '.self::MANIFEST.' ('.count($manifest).' mục)');

        foreach ($failed as $f) {
            $this->warn('Bỏ qua: '.$f);
        }

        return self::SUCCESS;
    }

    /** catalog/a/b.jpg + 800 → catalog/_v/a/b-800.webp */
    public static function variantPath(string $path, int $width): string
    {
        return ImageVariantBuilder::variantPath($path, $width);
    }
}
