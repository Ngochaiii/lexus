<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mẫu bố cục: dựng xong sản phẩm đầu của một hãng thì lưu khung mục lại,
 * sản phẩm sau tạo từ mẫu đã có sẵn các mục trống.
 */
class Template extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    /** Lấy khung sections đã bỏ hết nội dung, chỉ giữ tiêu đề + layout. */
    public function blankSections(): array
    {
        return collect($this->payload['sections'] ?? [])
            ->map(fn (array $section) => [
                'title'  => $section['title'] ?? '',
                'intro'  => '',
                'type'   => $section['type'] ?? 'media',
                'layout' => $section['layout'] ?? 'cols-3',
                'items'  => [],
            ])
            ->all();
    }
}
