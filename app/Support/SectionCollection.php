<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Quy tắc hiển thị (mục 3 tài liệu kiến trúc):
 *   - label trống  → không render nhãn
 *   - desc trống   → không render mô tả
 *   - intro trống  → không render đoạn mở đầu
 *
 * Nghĩa là mục "Thư viện" chỉ cần quăng ảnh vào, không phải điền gì thêm.
 */
class SectionCollection extends Collection
{
    /**
     * Bỏ hết field trống khỏi từng mục và từng item.
     * Frontend nhận về mảng chỉ chứa thứ thực sự có nội dung.
     */
    public function renderable(): array
    {
        return $this
            ->map(fn (array $section) => $this->clean($section))
            ->filter(fn (array $section) => $this->hasContent($section))
            ->values()
            ->all();
    }

    /**
     * Mục rỗng thì không render, nhưng "có nội dung" tuỳ kiểu: `media` cần ảnh,
     * `text` và `notice` cần body, `video` cần link, `form` cần khoá form,
     * `table` cần dòng.
     */
    protected function hasContent(array $section): bool
    {
        foreach (['items', 'body', 'video_url', 'form_key', 'rows'] as $key) {
            if (filled($section[$key] ?? null)) {
                return true;
            }
        }

        // `custom` do dự án tự render — không có dữ liệu nào ở đây để kiểm.
        return ($section['type'] ?? null) === 'custom';
    }

    protected function clean(array $section): array
    {
        $clean = array_filter([
            'title'     => $section['title'] ?? null,
            'intro'     => $section['intro'] ?? null,
            'type'      => $section['type'] ?? 'media',
            'layout'    => $section['layout'] ?? 'cols-3',
            // Bề rộng mục: narrow / wide / full. Trống thì Blade lấy mặc định
            // của trang (bài viết: cột chữ, sản phẩm & trang tĩnh: rộng).
            'width'     => $section['width'] ?? null,
            'body'      => $section['body'] ?? null,
            'video_url' => $section['video_url'] ?? null,
            'form_key'  => $section['form_key'] ?? null,
            // Nút hành động của băng ảnh tràn màn (`bleed`). Danh sách này là
            // whitelist: khoá nào không kê ở đây sẽ bị loại trước khi tới Blade.
            'cta_label'  => $section['cta_label'] ?? null,
            'cta_url'    => $section['cta_url'] ?? null,
            'cta2_label' => $section['cta2_label'] ?? null,
            'cta2_url'   => $section['cta2_url'] ?? null,
        ], fn ($v) => filled($v));

        $rows = collect($section['rows'] ?? [])
            ->map(fn (array $row) => array_filter($row, fn ($v) => filled($v)))
            ->filter()
            ->values()
            ->all();

        if ($rows) {
            $clean['rows'] = $rows;
        }

        $items = collect($section['items'] ?? [])
            ->map(fn (array $item) => array_filter($item, fn ($v) => filled($v)))
            ->filter()
            ->values()
            ->all();

        if ($items) {
            $clean['items'] = $items;
        }

        return $clean;
    }
}
