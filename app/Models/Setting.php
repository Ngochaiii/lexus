<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('catalog.settings'));
        static::deleted(fn () => Cache::forget('catalog.settings'));
    }

    /** Toàn bộ settings dưới dạng mảng phẳng, có cache. */
    public static function allValues(): array
    {
        return Cache::rememberForever(
            'catalog.settings',
            fn () => static::query()->pluck('value', 'key')->all()
        );
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allValues()[$key] ?? $default;
    }

    public static function put(string $key, mixed $value, string $group = 'general'): static
    {
        return static::updateOrCreate(['key' => $key], compact('value', 'group'));
    }
}
