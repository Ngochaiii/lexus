<?php

namespace Tests\Unit;

use App\Support\Media;
use PHPUnit\Framework\TestCase;

class MediaTest extends TestCase
{
    public function test_doi_link_youtube_va_vimeo_thanh_link_nhung(): void
    {
        $this->assertSame(
            'https://www.youtube.com/embed/abc123XYZ',
            Media::embed('https://www.youtube.com/watch?v=abc123XYZ&t=30s'),
        );

        $this->assertSame(
            'https://www.youtube.com/embed/abc123XYZ',
            Media::embed('https://youtu.be/abc123XYZ'),
        );

        $this->assertSame(
            'https://player.vimeo.com/video/76979871',
            Media::embed('https://vimeo.com/76979871'),
        );
    }

    public function test_link_la_tra_nguyen_khong_doan_bua(): void
    {
        $this->assertSame(
            'https://cdn.hang-xe.vn/phim/gx550.mp4',
            Media::embed('https://cdn.hang-xe.vn/phim/gx550.mp4'),
        );

        $this->assertNull(Media::embed(null));
    }

    public function test_phan_biet_file_video_voi_player_nhung(): void
    {
        $this->assertTrue(Media::isFile('https://cdn.hang-xe.vn/phim/gx550.mp4'));
        $this->assertTrue(Media::isFile('/storage/catalog/gx550.webm'));
        $this->assertFalse(Media::isFile('https://www.youtube.com/embed/abc123XYZ'));
        $this->assertFalse(Media::isFile(null));
    }

    public function test_link_ngoai_va_duong_dan_tuyet_doi_khong_bi_boc_them_storage(): void
    {
        $this->assertSame('https://cdn.hang-xe.vn/a.webp', Media::url('https://cdn.hang-xe.vn/a.webp'));
        $this->assertSame('/img/a.webp', Media::url('/img/a.webp'));
        $this->assertNull(Media::url(null));
        $this->assertNull(Media::url([]));
    }
}
