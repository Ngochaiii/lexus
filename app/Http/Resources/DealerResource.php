<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'address'       => $this->address,
            'phone'         => $this->phone,
            'lat'           => $this->lat,
            'lng'           => $this->lng,
            'opening_hours' => $this->opening_hours,
            'province'      => $this->whenLoaded('province', fn () => [
                'id'   => $this->province->id,
                'name' => $this->province->name,
            ]),
        ];
    }
}
