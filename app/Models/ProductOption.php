<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['spin_frames' => 'array'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(catalog_model('product'));
    }
}
