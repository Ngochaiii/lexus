<?php

namespace App\Models\Concerns;

use App\Support\SectionCollection;

/**
 * `sections` là trái tim hệ thống — một mảng có thứ tự, mỗi phần tử là một
 * mục do người nhập tự đặt tên. Trait này chỉ lo phần chuẩn hoá + lọc field
 * trống, để frontend không phải kiểm tra `isset()` ở mọi chỗ.
 *
 * Lưu ý: `$model->sections` vẫn là mảng thô như trong DB.
 * `$model->sectionList()` mới là bản đã bọc collection.
 */
trait HasSections
{
    public function sectionList(): SectionCollection
    {
        return SectionCollection::make($this->sections ?? []);
    }

    /** Các mục đã bỏ field trống, sẵn sàng render. */
    public function renderableSections(): array
    {
        return $this->sectionList()->renderable();
    }

    public function findSection(string $title): ?array
    {
        return $this->sectionList()->firstWhere('title', $title);
    }
}
