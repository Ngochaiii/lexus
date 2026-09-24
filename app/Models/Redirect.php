<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['status_code' => 'integer', 'hits' => 'integer'];
    }
}
