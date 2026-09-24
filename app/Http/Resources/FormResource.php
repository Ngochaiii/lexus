<?php

namespace App\Http\Resources;

use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $productOptions = $this->fields->contains(fn ($field) => $field->type === 'product')
            ? Catalog::query('product')
                ->published()
                ->notInCategory(config('catalog.frontend.accessory_category'))
                ->orderBy('sort')
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all()
            : [];

        return [
            'key' => $this->key,
            'name' => $this->name,
            'success_message' => $this->success_message,
            'fields' => $this->whenLoaded('fields', fn () => $this->fields->map(fn ($field) => [
                'key' => $field->key,
                'label' => $field->label,
                'type' => $field->type,
                'options' => $field->type === 'product' ? $productOptions : $field->options,
                'placeholder' => $field->placeholder,
                'width' => $field->width,
                'required' => in_array('required', $field->rules ?? [], true),
            ])->all()),
        ];
    }
}
