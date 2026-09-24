<?php

namespace App\Models;

use App\Models\Concerns\CreatesRedirectOnSlugChange;
use App\Models\Concerns\HasSections;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use CreatesRedirectOnSlugChange;
    use HasSections;
    use HasSlug;
    use SoftDeletes;

    protected $guarded = [];

    protected string $slugSourceColumn = 'title';

    public function urlType(): string
    {
        return 'page';
    }

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'seo'      => 'array',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
