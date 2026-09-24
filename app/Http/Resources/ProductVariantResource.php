<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'price_original' => $this->price_original,
            'note' => $this->note,
            'battery_kwh' => $this->battery_kwh,
            'range_km' => $this->range_km,
            'is_default' => (bool) $this->is_default,
        ];
    }
}
