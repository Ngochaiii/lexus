<?php

namespace App\Media;

use FilesystemIterator;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LocalMediaStore implements MediaStore
{
    /** @var array<int, string> */
    /** Existing SVG/GIF assets remain editable; new uploads stay JPEG/PNG/WebP only. */
    private const STORED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];

    public function storeUploadedFile(UploadedFile $file, string $directory, string $kind): StoredMedia
    {
        $directory = $this->normalizeRelativePath($directory);
        $allowedKind = config("media.upload_directories.{$directory}");

        if (! is_string($allowedKind) || ! hash_equals($allowedKind, $kind)) {
            throw new InvalidMedia('Thư mục upload không được phép.');
        }

        if (! $file->isValid()) {
            throw new InvalidMedia($this->uploadErrorMessage($file->getError()));
        }

        $source = $file->getPathname();
        $size = filesize($source);

        if ($size === false || $size < 1) {
            throw new InvalidMedia('File tải lên đang trống hoặc không đọc được.');
        }

        $metadata = match ($kind) {
            'image' => $this->inspectImage($source, $size),
            'pdf' => $this->inspectPdf($source, $size),
            default => throw new InvalidMedia('Loại file tải lên không được hỗ trợ.'),
        };

        $path = $directory.'/'.Str::ulid().'.'.$metadata['extension'];
        $target = $this->absolutePath($path);
        $pending = $target.'.uploading';

        $this->ensureDirectory(dirname($target));

        $moved = is_uploaded_file($source)
            ? move_uploaded_file($source, $pending)
            : (app()->runningUnitTests() && @rename($source, $pending));

        if (! $moved) {
            throw new InvalidMedia('Không thể chuyển file vào thư mục media. Kiểm tra quyền ghi MEDIA_ROOT.');
        }

        @chmod($pending, 0644);

        if (! @rename($pending, $target)) {
            @unlink($pending);

            throw new InvalidMedia('Không thể hoàn tất việc lưu file media.');
        }

        return new StoredMedia(
            path: $path,
            url: $this->url($path),
            type: $metadata['type'],
            size: $size,
            width: $metadata['width'] ?? null,
            height: $metadata['height'] ?? null,
        );
    }

    public function url(string $path): string
    {
        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:', '/'])) {
            return $path;
        }

        if (Str::startsWith($path, 'storage/')) {
            return '/'.ltrim($path, '/');
        }

        $path = $this->normalizeRelativePath($path);
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $path)));

        return rtrim((string) config('media.url', '/storage'), '/').'/'.$encodedPath;
    }

    public function absolutePath(string $path): string
    {
        return $this->root().'/'.$this->normalizeRelativePath($path);
    }

    public function exists(string $path): bool
    {
        return is_file($this->absolutePath($path));
    }

    public function size(string $path): ?int
    {
        $size = @filesize($this->absolutePath($path));

        return $size === false ? null : $size;
    }

    public function read(string $path): ?string
    {
        if (! $this->exists($path)) {
            return null;
        }

        $contents = @file_get_contents($this->absolutePath($path));

        return $contents === false ? null : $contents;
    }

    public function write(string $path, string $contents): void
    {
        $target = $this->absolutePath($path);
        $pending = $target.'.'.Str::random(10).'.tmp';

        $this->ensureDirectory(dirname($target));

        if (file_put_contents($pending, $contents, LOCK_EX) === false || ! @rename($pending, $target)) {
            @unlink($pending);

            throw new InvalidMedia("Không thể ghi media [{$path}].");
        }

        @chmod($target, 0644);
    }

    public function delete(string $path): bool
    {
        $target = $this->absolutePath($path);

        return ! is_file($target) || @unlink($target);
    }

    public function allFiles(string $directory = ''): array
    {
        $directory = filled($directory) ? $this->normalizeRelativePath($directory) : '';
        $root = $directory === '' ? $this->root() : $this->absolutePath($directory);

        if (! is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($this->root()))), '/');
            $files[] = $relative;
        }

        sort($files);

        return $files;
    }

    public static function isAllowedStoredValue(mixed $value, string $kind = 'image'): bool
    {
        if (! is_string($value) || blank($value)) {
            return false;
        }

        if (Str::startsWith($value, ['http://', 'https://', '//'])) {
            return true;
        }

        if (! static::isSafeRelativePath($value)) {
            return false;
        }

        $extension = Str::lower(pathinfo($value, PATHINFO_EXTENSION));

        return $kind === 'pdf'
            ? $extension === 'pdf'
            : in_array($extension, static::STORED_IMAGE_EXTENSIONS, true);
    }

    public static function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\')) {
            return false;
        }

        if (str_starts_with($path, '/') || preg_match('~(^|/)\.\.?(/|$)~', $path)) {
            return false;
        }

        return preg_match('~\A[a-zA-Z0-9][a-zA-Z0-9_./-]*\z~', $path) === 1;
    }

    /** @return array{extension:string,type:string,width:int,height:int} */
    private function inspectImage(string $path, int $size): array
    {
        $maxBytes = max(1, (int) config('media.max_image_size_kb', 8192)) * 1024;

        if ($size > $maxBytes) {
            throw new InvalidMedia('Ảnh vượt quá '.number_format($maxBytes / 1048576, 0).' MB.');
        }

        $head = file_get_contents($path, false, null, 0, 32);
        $info = @getimagesize($path);

        if (! is_string($head) || ! is_array($info)) {
            throw new InvalidMedia('File tải lên không phải ảnh JPEG, PNG hoặc WebP hợp lệ.');
        }

        [$width, $height, $imageType] = $info;

        $format = match (true) {
            $imageType === IMAGETYPE_JPEG && str_starts_with($head, "\xFF\xD8\xFF") => ['jpg', 'image/jpeg'],
            $imageType === IMAGETYPE_PNG && str_starts_with($head, "\x89PNG\r\n\x1A\n") => ['png', 'image/png'],
            $imageType === IMAGETYPE_WEBP
                && substr($head, 0, 4) === 'RIFF'
                && substr($head, 8, 4) === 'WEBP' => ['webp', 'image/webp'],
            default => null,
        };

        if ($format === null) {
            throw new InvalidMedia('Chỉ chấp nhận ảnh JPEG, PNG hoặc WebP.');
        }

        $maxWidth = max(1, (int) config('media.max_width', 8000));
        $maxHeight = max(1, (int) config('media.max_height', 8000));
        $maxPixels = max(1, (int) config('media.max_pixels', 40_000_000));

        if ($width < 1 || $height < 1 || $width > $maxWidth || $height > $maxHeight || ($width * $height) > $maxPixels) {
            throw new InvalidMedia("Kích thước ảnh không hợp lệ. Tối đa {$maxWidth}×{$maxHeight} px và ".number_format($maxPixels).' pixels.');
        }

        return [
            'extension' => $format[0],
            'type' => $format[1],
            'width' => $width,
            'height' => $height,
        ];
    }

    /** @return array{extension:string,type:string} */
    private function inspectPdf(string $path, int $size): array
    {
        $maxBytes = max(1, (int) config('media.max_pdf_size_kb', 20480)) * 1024;

        if ($size > $maxBytes) {
            throw new InvalidMedia('PDF vượt quá '.number_format($maxBytes / 1048576, 0).' MB.');
        }

        $stream = @fopen($path, 'rb');

        if (! is_resource($stream)) {
            throw new InvalidMedia('Không đọc được file PDF tải lên.');
        }

        $head = fread($stream, 5);
        fseek($stream, max(0, $size - 4096));
        $tail = stream_get_contents($stream);
        fclose($stream);

        if ($head !== '%PDF-' || ! is_string($tail) || ! str_contains($tail, '%%EOF')) {
            throw new InvalidMedia('File tải lên không phải PDF hợp lệ.');
        }

        return ['extension' => 'pdf', 'type' => 'application/pdf'];
    }

    private function normalizeRelativePath(string $path): string
    {
        $path = trim($path, '/');

        if (! static::isSafeRelativePath($path)) {
            throw new InvalidMedia('Đường dẫn media không hợp lệ.');
        }

        return $path;
    }

    private function root(): string
    {
        $root = rtrim((string) config('media.root', storage_path('app/public')), '/');

        if (! str_starts_with($root, DIRECTORY_SEPARATOR)) {
            $root = base_path($root);
        }

        return $root;
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new InvalidMedia("Không thể tạo thư mục media [{$directory}].");
        }
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File vượt quá giới hạn upload của máy chủ.',
            UPLOAD_ERR_PARTIAL => 'File chỉ được tải lên một phần, vui lòng thử lại.',
            UPLOAD_ERR_NO_FILE => 'Chưa chọn file để tải lên.',
            UPLOAD_ERR_NO_TMP_DIR => 'Máy chủ thiếu thư mục upload tạm.',
            UPLOAD_ERR_CANT_WRITE => 'Máy chủ không thể ghi file upload tạm.',
            UPLOAD_ERR_EXTENSION => 'PHP đã chặn file tải lên.',
            default => 'File tải lên không hợp lệ.',
        };
    }
}
