<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Catalog;
use App\Support\Url;
use Illuminate\Contracts\View\View;

/**
 * Danh sách bài viết — /tin-tuc.
 */
class PostIndexController extends Controller
{
    public function __invoke(): View
    {
        return view('frontend.posts', [
            'heading' => 'Tin tức',
            'canonical' => Url::route('posts.index'),
            'categories' => Catalog::query('post_category')->orderBy('sort')->get(),
            'posts' => Catalog::query('post')
                ->published()
                ->with('category')
                ->latest('published_at')
                ->paginate((int) config('catalog.frontend.per_page', 12)),
        ]);
    }
}
