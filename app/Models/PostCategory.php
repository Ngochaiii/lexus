<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostCategory extends Model
{
    use HasSlug;

    protected $guarded = [];

    public function posts(): HasMany
    {
        return $this->hasMany(catalog_model('post'), 'post_category_id');
    }
}
