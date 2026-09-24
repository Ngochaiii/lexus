<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'utm'  => 'array',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(catalog_model('form'));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(catalog_model('product'));
    }

    /** Phiên bản khách chọn (khi gửi từ thẻ phiên bản). */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
