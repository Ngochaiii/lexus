<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Sự kiện ẩn danh: bấm Gọi/Zalo, số đo tốc độ của khách thật. */
class SiteEvent extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['value' => 'float', 'created_at' => 'datetime'];
    }
}
