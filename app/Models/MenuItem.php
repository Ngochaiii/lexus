<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    protected static function booted(): void
    {
        /*
         * Mục con tạo qua quan hệ children() chỉ được gán parent_id — quan hệ
         * đó là hasMany(..., 'parent_id') nên không biết gì về menu_id, mà cột
         * này lại NOT NULL. Thừa kế từ cha để mục con luôn thuộc đúng menu.
         */
        static::saving(function (self $item): void {
            if (blank($item->menu_id) && filled($item->parent_id)) {
                $item->menu_id = static::query()->whereKey($item->parent_id)->value('menu_id');
            }
        });
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(catalog_model('menu'));
    }

    public function children(): HasMany
    {
        return $this->hasMany(catalog_model('menu_item'), 'parent_id')->orderBy('sort')->with('children');
    }

    /**
     * URL cuối cùng: hoặc gõ tay, hoặc suy ra từ target_type + target_id.
     */
    public function resolvedUrl(): ?string
    {
        if (filled($this->url)) {
            return $this->url;
        }

        if (blank($this->target_type) || blank($this->target_id)) {
            return null;
        }

        $model = rescue(fn () => catalog_model($this->target_type)::find($this->target_id), null, false);

        if (! $model) {
            return null;
        }

        // Chỉ suy được URL cho loại có khai tiền tố trong config('catalog.routes')
        if (! array_key_exists($this->target_type, (array) config('catalog.routes'))) {
            return null;
        }

        return \App\Support\Url::to($this->target_type, $model->slug);
    }
}
