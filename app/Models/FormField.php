<?php

namespace App\Models;

use App\Rules\VietnamPhone;
use App\Support\Catalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class FormField extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'rules' => 'array',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(catalog_model('form'));
    }

    /** @return array<int, mixed> */
    public function validationRules(): array
    {
        $rules = $this->rules ?: ['nullable'];

        $typeRules = match ($this->type) {
            'email' => ['email'],
            'tel' => ['string', new VietnamPhone],
            'date' => ['date'],
            'checkbox' => ['array'],
            'product' => [
                'integer',
                Rule::exists(Catalog::model('product'), 'id')
                    ->where(fn ($query) => $query
                        ->where('status', 'published')
                        ->whereNull('deleted_at')
                        ->where(fn ($published) => $published
                            ->whereNull('published_at')
                            ->orWhere('published_at', '<=', now()))),
            ],
            default => ['string'],
        };
        $rules = [...$rules, ...$typeRules];

        if (in_array($this->type, ['select', 'radio'], true) && filled($this->options)) {
            $rules[] = Rule::in(array_keys($this->options));
        }

        return array_values(array_unique($rules, SORT_REGULAR));
    }
}
