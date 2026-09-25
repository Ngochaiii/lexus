<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        // slug cho trang riêng /san-pham/{xe}/{slug}: tự sinh từ tên, không
        // trùng trong cùng dòng xe. Đã có slug thì giữ (link không đổi khi sửa giá).
        static::saving(function (ProductVariant $variant): void {
            if (filled($variant->slug) || blank($variant->name)) {
                return;
            }

            $base = Str::slug($variant->name) ?: 'phien-ban';
            $slug = $base;
            for ($i = 2; static::query()->where('product_id', $variant->product_id)->where('slug', $slug)->whereKeyNot($variant->getKey())->exists(); $i++) {
                $slug = $base.'-'.$i;
            }
            $variant->slug = $slug;
        });

        static::saved(function (ProductVariant $variant): void {
            if (! $variant->is_default || ! $variant->product_id) {
                return;
            }

            static::query()
                ->where('product_id', $variant->product_id)
                ->whereKeyNot($variant->getKey())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        });
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_original' => 'decimal:2',
            'battery_kwh' => 'decimal:2',
            'range_km' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(catalog_model('product'));
    }
}
