<?php

use App\Media\ImageVariantBuilder;
use App\Media\MediaStore;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Dựng lại bản thu nhỏ còn thiếu của mọi ảnh trong manifest (12/10/2026).
 * Migration 04/10 thay ảnh đầu trang LM và xoá bản thu nhỏ cũ nhưng không dựng
 * lại — srcset trỏ vào hero-400/800/1280.webp trả 404. build() chỉ làm bản
 * thiếu hoặc cũ hơn ảnh gốc, nên chạy lại không làm gì thêm.
 */
return new class extends Migration
{
    public function up(): void
    {
        $media = app(MediaStore::class);
        $builder = app(ImageVariantBuilder::class);

        if (! $media->exists(ImageVariantBuilder::MANIFEST)) {
            return;
        }

        $manifest = json_decode((string) $media->read(ImageVariantBuilder::MANIFEST), true) ?: [];
        $entries = [];

        foreach ($manifest as $path => $entry) {
            $missing = array_filter((array) ($entry['v'] ?? []),
                fn (int $w) => ! $media->exists(ImageVariantBuilder::variantPath($path, $w)));
            if ($missing === [] || ! $media->exists($path)) {
                continue;
            }

            try {
                $entries[$path] = $builder->build($path)[1];
            } catch (\Throwable $e) {
                Log::warning('Dựng lại bản thu nhỏ: lỗi', ['path' => $path, 'error' => $e->getMessage()]);
            }
        }

        if ($entries !== []) {
            $builder->mergeManifest($entries);
        }
    }

    public function down(): void
    {
        // Không hoàn tác: chỉ tạo thêm bản thu nhỏ còn thiếu.
    }
};
