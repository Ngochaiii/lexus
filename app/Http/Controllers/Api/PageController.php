<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PageResource;
use App\Support\Catalog;

class PageController extends Controller
{
    public function show(string $slug): PageResource
    {
        $page = Catalog::query('page')->published()->where('slug', $slug)->firstOrFail();

        return PageResource::make($page);
    }
}
