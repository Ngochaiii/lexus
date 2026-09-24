<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PostCategory;
use App\Support\Catalog;
use Illuminate\Contracts\View\View;

/**
 * Chuyên mục bài viết — /chuyen-muc/{slug}. Dùng lại view danh sách tin tức.
 */
class PostCategoryController extends Controller
{
    public function __invoke(PostCategory $postCategory): View
    {
        return view('frontend.posts', [
            'heading'      => $postCategory->name,
            'postCategory' => $postCategory,
            'categories'   => Catalog::query('post_category')->orderBy('sort')->get(),
            'canonical'    => \App\Support\Url::absolute('post_category', $postCategory->slug),
            'posts'        => $postCategory->posts()
                ->published()
                ->with('category')
                ->latest('published_at')
                ->paginate((int) config('catalog.frontend.per_page', 12)),
        ]);
    }
}
