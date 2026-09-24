<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Media\ImageVariantBuilder;
use App\Media\InvalidMedia;
use App\Media\MediaStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaUploadController extends Controller
{
    public function __invoke(
        Request $request,
        MediaStore $media,
        ImageVariantBuilder $variantBuilder,
    ): JsonResponse {
        $file = $request->file('file');
        $directory = $request->input('directory');
        $kind = $request->input('kind', 'image');

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'file' => 'Chưa nhận được file tải lên hoặc file vượt giới hạn của PHP/Nginx.',
            ]);
        }

        if (! is_string($directory) || ! is_string($kind)) {
            throw ValidationException::withMessages([
                'file' => 'Thông tin nơi lưu file không hợp lệ.',
            ]);
        }

        try {
            $stored = $media->storeUploadedFile($file, $directory, $kind);
        } catch (InvalidMedia $exception) {
            throw ValidationException::withMessages([
                'file' => $exception->getMessage(),
            ]);
        }

        $data = $stored->toArray();

        if ($kind === 'image') {
            try {
                [, $entry] = $variantBuilder->build($stored->path);
                $variantBuilder->mergeManifest([$stored->path => $entry]);
                $data['responsive_widths'] = $entry['v'];
            } catch (\Throwable $exception) {
                $media->delete($stored->path);

                foreach (ImageVariantBuilder::WIDTHS as $width) {
                    $media->delete(ImageVariantBuilder::variantPath($stored->path, $width));
                }

                report($exception);

                throw ValidationException::withMessages([
                    'file' => 'Không thể tạo ảnh tối ưu trên máy chủ. Kiểm tra PHP-GD có hỗ trợ WebP.',
                ]);
            }
        }

        return response()->json(['data' => $data], 201);
    }
}
