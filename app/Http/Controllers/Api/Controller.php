<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    protected function perPage(int $requested = 0): int
    {
        $default = (int) config('catalog.api.per_page', 24);

        return $requested > 0 ? min($requested, 100) : $default;
    }
}
