<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'slug'         => $this->slug,
            'title'        => $this->title,
            'excerpt'      => $this->excerpt,
            'cover'        => $this->cover,
            'category'     => PostCategoryResource::make($this->whenLoaded('category')),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
