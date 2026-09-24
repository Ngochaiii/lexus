<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasSlug;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['seo' => 'array'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(catalog_model('category'), 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(catalog_model('category'), 'parent_id')->orderBy('sort');
    }

    public function products(): HasMany
    {
        return $this->hasMany(catalog_model('product'));
    }
}
