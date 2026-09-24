<?php

namespace Tests\Unit;

use App\Support\SectionCollection;
use PHPUnit\Framework\TestCase;

/**
 * Quy tắc hiển thị mục 3: label/desc/intro trống thì frontend không render.
 * Nghĩa là mục "Thư viện" chỉ cần quăng ảnh vào, không phải điền gì thêm.
 */
class SectionCollectionTest extends TestCase
{
    public function test_bo_field_trong_khoi_muc_va_khoi_item(): void
    {
        $sections = SectionCollection::make([
            [
                'title' => 'Thư viện', 'intro' => '', 'type' => 'media', 'layout' => 'slider',
                'items' => [['image' => 'a.webp', 'label' => '', 'desc' => '']],
            ],
        ])->renderable();

        $this->assertArrayNotHasKey('intro', $sections[0]);
        $this->assertSame(['image' => 'a.webp'], $sections[0]['items'][0]);
    }

    public function test_giu_lai_field_co_noi_dung(): void
    {
        $sections = SectionCollection::make([
            [
                'title' => 'Mâm xe', 'intro' => 'Mâm hợp kim nhôm.', 'type' => 'media', 'layout' => 'cols-2',
                'items' => [['image' => 'w.webp', 'label' => 'Luxury', 'desc' => 'Tối ưu đô thị.']],
            ],
        ])->renderable();

        $this->assertSame('Mâm hợp kim nhôm.', $sections[0]['intro']);
        $this->assertSame('Luxury', $sections[0]['items'][0]['label']);
    }

    public function test_muc_khong_co_noi_dung_bi_loai_han(): void
    {
        $sections = SectionCollection::make([
            ['title' => 'Mục rỗng', 'intro' => '', 'items' => []],
            ['title' => 'Có ảnh', 'items' => [['image' => 'a.webp']]],
        ])->renderable();

        $this->assertCount(1, $sections);
        $this->assertSame('Có ảnh', $sections[0]['title']);
    }

    public function test_mac_dinh_type_media_va_layout_cols_3(): void
    {
        $sections = SectionCollection::make([
            ['title' => 'X', 'items' => [['image' => 'a.webp']]],
        ])->renderable();

        $this->assertSame('media', $sections[0]['type']);
        $this->assertSame('cols-3', $sections[0]['layout']);
    }

    public function test_muc_kieu_text_giu_lai_nho_co_body(): void
    {
        $sections = SectionCollection::make([
            ['title' => 'Giới thiệu', 'type' => 'text', 'body' => 'Một đoạn văn.', 'items' => []],
        ])->renderable();

        $this->assertCount(1, $sections);
        $this->assertSame('Một đoạn văn.', $sections[0]['body']);
    }
}
