<?php

namespace App\Http\Resources;

use App\Support\JsonLd;
use App\Support\Url;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'cover' => $this->cover,
            'category' => PostCategoryResource::make($this->whenLoaded('category')),
            'sections' => $this->renderableSections(),
            'seo' => $this->seo,
            'canonical' => data_get($this->seo, 'canonical') ?: Url::absolute('post', $this->slug),
            'jsonld' => JsonLd::forPost($this->resource),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
