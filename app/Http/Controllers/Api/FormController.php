<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\FormResource;
use App\Support\Catalog;

class FormController extends Controller
{
    public function show(string $key): FormResource
    {
        $form = Catalog::query('form')
            ->with('fields')
            ->where('key', $key)
            ->where('is_active', true)
            ->firstOrFail();

        return FormResource::make($form);
    }
}
