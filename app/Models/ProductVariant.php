<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
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
