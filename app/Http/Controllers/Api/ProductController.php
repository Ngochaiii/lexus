<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductSummaryResource;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Catalog::query('product')
            ->published()
            ->with('category')
            ->when($request->string('category')->toString(), fn ($q, $slug) => $q->whereHas(
                'category',
                fn ($c) => $c->where('slug', $slug)
            ))
            ->when($request->string('q')->toString(), fn ($q, $term) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('tagline', 'like', "%{$term}%")
            ))
            ->orderBy('sort')
            ->orderByDesc('published_at')
            ->paginate($this->perPage($request->integer('per_page')))
            ->withQueryString();

        return ProductSummaryResource::collection($products);
    }

    public function show(string $slug): ProductResource
    {
        $product = Catalog::query('product')
            ->published()
            ->with(['category', 'variants', 'options'])
            ->where('slug', $slug)
            ->firstOrFail();

        return ProductResource::make($product);
    }
}
