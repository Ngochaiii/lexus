<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasSlug
{
    /*
     * KHÔNG khai báo $slugSourceColumn ở đây. Model nào cần cột khác `name`
     * (Post, Page dùng `title`) sẽ khai lại property đó, mà PHP coi property
     * trùng tên giữa trait và class là xung đột nếu giá trị mặc định khác
     * nhau — fatal error ngay lúc nạp class.
     */

    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            if (blank($model->slug)) {
                $source = $model->{$model->slugSource()};
                $model->slug = $model->generateUniqueSlug((string) $source);
            }
        });
    }

    public function slugSource(): string
    {
        return property_exists($this, 'slugSourceColumn') ? $this->slugSourceColumn : 'name';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function generateUniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: Str::random(8);
        $slug = $base;
        $i = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
