<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dealer extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'lat'           => 'float',
            'lng'           => 'float',
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(catalog_model('province'));
    }
}
