<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'registration_fee_rate' => 'decimal:4',
            'plate_fee'             => 'decimal:2',
            'inspection_fee'        => 'decimal:2',
            'road_fee'              => 'decimal:2',
            'insurance_fee'         => 'decimal:2',
        ];
    }

    public function dealers(): HasMany
    {
        return $this->hasMany(catalog_model('dealer'));
    }
}
