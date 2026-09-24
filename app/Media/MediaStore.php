<?php

namespace App\Media;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface MediaStore
{
    public function storeUploadedFile(UploadedFile $file, string $directory, string $kind): StoredMedia;

    public function url(string $path): string;

    public function absolutePath(string $path): string;

    public function exists(string $path): bool;

    public function size(string $path): ?int;

    public function read(string $path): ?string;

    public function write(string $path, string $contents): void;

    public function delete(string $path): bool;

    /** @return array<int, string> */
    public function allFiles(string $directory = ''): array;
}
