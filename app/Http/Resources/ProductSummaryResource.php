<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Bản rút gọn cho trang danh sách — không kéo theo sections/specs. */
class ProductSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'slug'       => $this->slug,
            'name'       => $this->name,
            'tagline'    => $this->tagline,
            'price_from' => $this->price_from,
            'hero'       => $this->hero,
            'category'   => CategoryResource::make($this->whenLoaded('category')),
            'highlights' => $this->when(catalog_feature('highlights'), fn () => $this->highlights ?? []),
        ];
    }
}
