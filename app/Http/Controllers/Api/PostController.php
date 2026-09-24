<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PostResource;
use App\Http\Resources\PostSummaryResource;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $posts = Catalog::query('post')
            ->published()
            ->with('category')
            ->when($request->string('category')->toString(), fn ($q, $slug) => $q->whereHas(
                'category',
                fn ($c) => $c->where('slug', $slug)
            ))
            ->orderByDesc('published_at')
            ->paginate($this->perPage($request->integer('per_page')))
            ->withQueryString();

        return PostSummaryResource::collection($posts);
    }

    public function show(string $slug): PostResource
    {
        $post = Catalog::query('post')
            ->published()
            ->with('category')
            ->where('slug', $slug)
            ->firstOrFail();

        return PostResource::make($post);
    }
}
