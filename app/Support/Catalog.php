<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Cửa duy nhất để đọc config/catalog.php.
 * Không chỗ nào trong core được gọi thẳng config('catalog.*').
 */
class Catalog
{
    /** @return class-string<Model> */
    public static function model(string $key): string
    {
        $class = config("catalog.models.{$key}");

        if (! $class || ! is_subclass_of($class, Model::class)) {
            throw new InvalidArgumentException(
                "config('catalog.models.{$key}') chưa trỏ tới một Eloquent model hợp lệ."
            );
        }

        return $class;
    }

    /** @return Model */
    public static function make(string $key, array $attributes = []): Model
    {
        $class = static::model($key);

        return new $class($attributes);
    }

    public static function query(string $key)
    {
        return static::model($key)::query();
    }

    /**
     * catalog_label('product.single') → "Xe"
     * catalog_label('sections')       → "Chi tiết xe"
     */
    public static function label(string $key, ?string $default = null): string
    {
        return (string) config("catalog.labels.{$key}", $default ?? str($key)->afterLast('.')->headline());
    }

    public static function feature(string $key): bool
    {
        return (bool) config("catalog.features.{$key}", false);
    }

    /**
     * Các mục gốc của một menu, kèm con cháu (children() eager-load đệ quy).
     * Menu chưa tạo thì trả rỗng để layout không phải bọc @if.
     *
     * Chưa cache: cache thì phải xoá đúng lúc ở cả Menu lẫn MenuItem, mà đây
     * mới là hai truy vấn cho mỗi request. Thêm cache khi đo thấy cần.
     *
     * @return \Illuminate\Support\Collection<int, Model>
     */
    public static function menu(string $key): \Illuminate\Support\Collection
    {
        $menu = static::query('menu')->where('key', $key)->first();

        return $menu ? $menu->rootItems : collect();
    }

    /** @return array<int, string> */
    public static function sectionPresets(): array
    {
        return (array) config('catalog.section_presets', []);
    }

    /** @return array<string, string> */
    public static function sectionLayouts(): array
    {
        return (array) config('catalog.section_layouts', []);
    }

    /** @return array<string, string> */
    public static function sectionTypes(): array
    {
        return (array) config('catalog.section_types', []);
    }
}
