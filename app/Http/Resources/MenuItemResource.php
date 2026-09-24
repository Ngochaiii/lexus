<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'label'    => $this->label,
            'url'      => $this->resolvedUrl(),
            'target'   => array_filter([
                'type' => $this->target_type,
                'id'   => $this->target_id,
            ]),
            'meta'     => $this->meta,
            'children' => static::collection($this->whenLoaded('children')),
        ];
    }
}
