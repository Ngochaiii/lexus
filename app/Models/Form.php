<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'notify_emails' => 'array',
            'is_active'     => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function fields(): HasMany
    {
        return $this->hasMany(catalog_model('form_field'))->orderBy('sort');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(catalog_model('lead'));
    }

    /**
     * Luật validate dựng từ form_fields — không hardcode ở controller.
     *
     * @return array<string, array<int, string>>
     */
    public function validationRules(): array
    {
        return $this->fields
            ->mapWithKeys(fn (FormField $field) => [$field->key => $field->validationRules()])
            ->all();
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return $this->fields->pluck('label', 'key')->all();
    }
}
