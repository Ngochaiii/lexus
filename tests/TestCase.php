<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Test chạy trên MariaDB thật (catalog_cars_test, khai trong phpunit.xml),
    // không phải sqlite :memory: — schema dựa nặng vào cột json và enum.
    use RefreshDatabase;

    protected string $mediaTestRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mediaTestRoot = sys_get_temp_dir().'/cars-media-'.getmypid().'-'.bin2hex(random_bytes(6));
        config(['media.root' => $this->mediaTestRoot]);
    }

    protected function tearDown(): void
    {
        $this->deleteTestDirectory($this->mediaTestRoot ?? '');

        parent::tearDown();
    }

    private function deleteTestDirectory(string $directory): void
    {
        if ($directory === '' || ! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($directory);
    }
}
