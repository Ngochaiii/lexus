<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CategoryResource;
use App\Support\Catalog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Catalog::query('category')
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(string $slug): CategoryResource
    {
        $category = Catalog::query('category')
            ->with('children')
            ->where('slug', $slug)
            ->firstOrFail();

        return CategoryResource::make($category);
    }
}
