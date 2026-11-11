<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * 11/11/2026: ô "Ảnh thương hiệu cho bài viết" (thư mục catalog/brand) báo
 * "Thư mục upload không được phép" trên site thật vì thư mục mới chưa được thêm
 * vào config/media.php. Mọi ô tải file trong admin phải dùng thư mục có trong
 * danh sách cho phép.
 */
class UploadDirectoriesAllowedTest extends TestCase
{
    public function test_moi_thu_muc_upload_trong_admin_deu_duoc_phep(): void
    {
        $allowed = array_keys(config('media.upload_directories'));
        $used = [];
        foreach (File::allFiles(app_path('Filament')) as $file) {
            preg_match_all("/->directory\('([^']+)'\)/", $file->getContents(), $m);
            $used = [...$used, ...$m[1]];
        }

        $this->assertContains('catalog/brand', $used);
        $this->assertSame([], array_values(array_diff(array_unique($used), $allowed)));
    }
}
