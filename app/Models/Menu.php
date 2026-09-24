<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $guarded = [];

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function items(): HasMany
    {
        return $this->hasMany(catalog_model('menu_item'))->orderBy('sort');
    }

    /** Chỉ các mục gốc, kèm con cháu. */
    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id')->with('children');
    }
}
