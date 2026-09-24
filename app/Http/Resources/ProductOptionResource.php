<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'hex'   => $this->hex,
            'image' => $this->image,
            'spin_frames' => $this->spin_frames ?? [],
        ];
    }
}
