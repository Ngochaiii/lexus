<?php

namespace App\Models;

use App\Models\Concerns\CreatesRedirectOnSlugChange;
use App\Models\Concerns\HasSections;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use CreatesRedirectOnSlugChange;
    use HasFactory;
    use HasSections;
    use HasSlug;
    use SoftDeletes;

    protected $guarded = [];

    public function urlType(): string
    {
        return 'product';
    }

    protected function casts(): array
    {
        return [
            'hero' => 'array',
            'highlights' => 'array',
            'sections' => 'array',
            'specs' => 'array',
            'spec_notes' => 'array',
            'spec_images' => 'array',
            'seo' => 'array',
            'price_from' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(catalog_model('category'));
    }

    public function variants(): HasMany
    {
        return $this->hasMany(catalog_model('variant'))->orderBy('sort');
    }

    public function options(): HasMany
    {
        return $this->hasMany(catalog_model('option'))->orderBy('sort');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(catalog_model('lead'));
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /**
     * Loại một danh mục ra khỏi danh sách, theo slug. Dùng để giữ phụ kiện
     * khỏi lẫn vào dải xe ở trang chủ và /san-pham (danh mục khai ở
     * config('catalog.frontend.accessory_category')).
     *
     * Slug rỗng hoặc chưa có danh mục nào mang slug đó thì không lọc gì —
     * dự án không dùng trang phụ kiện vẫn chạy y như cũ.
     */
    public function scopeNotInCategory(Builder $query, ?string $slug): Builder
    {
        if (blank($slug)) {
            return $query;
        }

        return $query->whereNot(
            fn (Builder $q) => $q->whereHas('category', fn (Builder $c) => $c->where('slug', $slug))
        );
    }

    /**
     * Nhân bản sản phẩm: copy nguyên sections + specs sang bản mới.
     * Sản phẩm đầu mất vài giờ, sản phẩm sau còn 20 phút.
     */
    public function duplicate(?string $name = null): static
    {
        $copy = $this->replicate(['published_at']);
        $copy->name = $name ?? $this->name.' (bản sao)';
        $copy->slug = $this->generateUniqueSlug($copy->name);
        $copy->status = 'draft';
        $copy->published_at = null;
        $copy->save();

        foreach ($this->variants as $variant) {
            $copy->variants()->create($variant->only([
                'name',
                'price',
                'price_original',
                'note',
                'battery_kwh',
                'range_km',
                'sort',
                'is_default',
            ]));
        }

        foreach ($this->options as $option) {
            $copy->options()->create($option->only(['name', 'hex', 'image', 'spin_frames', 'sort']));
        }

        return $copy;
    }
}
