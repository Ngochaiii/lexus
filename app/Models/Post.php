<?php

namespace App\Models;

use App\Models\Concerns\CreatesRedirectOnSlugChange;
use App\Models\Concerns\HasSections;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use CreatesRedirectOnSlugChange;
    use HasSections;
    use HasSlug;
    use SoftDeletes;

    protected $guarded = [];

    protected string $slugSourceColumn = 'title';

    public function urlType(): string
    {
        return 'post';
    }

    protected function casts(): array
    {
        return [
            'sections'     => 'array',
            'seo'          => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(catalog_model('post_category'), 'post_category_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }
}
