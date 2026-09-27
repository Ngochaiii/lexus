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

    protected static function booted(): void
    {
        // Đăng bài mà để trống "Đăng lúc" (vd bài Gemini chuyển Nháp → Đã đăng)
        // thì lấy thời điểm đăng: Google cần datePublished, khách cần thấy ngày.
        static::saving(function (Post $post) {
            if ($post->status === 'published' && blank($post->published_at)) {
                $post->published_at = now();
            }
        });
    }

    public function urlType(): string
    {
        return 'post';
    }

    protected function casts(): array
    {
        return [
            'sections'     => 'array',
            'seo'          => 'array',
            'share_kit'    => 'array',
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
