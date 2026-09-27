<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Product;
use App\Support\ArticleContext;
use App\Support\Catalog;
use App\Support\PostToc;
use Illuminate\Contracts\View\View;

class PostController extends Controller
{
    public function __invoke(Post $post): View
    {
        abort_unless(
            $post->newQuery()->published()->whereKey($post->getKey())->exists(),
            404
        );

        [$sections, $toc] = PostToc::apply($post->renderableSections());

        return view('frontend.post', [
            'post' => $post->load('category'),
            'sections' => $sections,
            'toc' => count($toc) >= 3 ? $toc : [],
            'car' => $this->carInPost($post),
            'related' => $this->related($post),
        ]);
    }

    /**
     * Dòng xe bài đang nói tới (theo tiêu đề, không có thì theo từ khoá SEO) —
     * cột phải hiện bảng giá + lăn bánh của dòng đó, cùng nguồn với bảng giá.
     */
    private function carInPost(Post $post): ?Product
    {
        $products = Catalog::query('product')->published()
            ->with(['variants' => fn ($q) => $q->orderBy('sort')])
            ->orderBy('sort')->get();

        return ArticleContext::focusProducts($products, $post->title)->first()
            ?? ArticleContext::focusProducts($products, (string) data_get($post->seo, 'keywords'))->first();
    }

    /**
     * Cột phải của trang tin: cùng chuyên mục trước, thiếu thì bù bằng tin mới
     * nhất — cột trống là cả nửa màn hình bỏ không.
     */
    private function related(Post $post, int $take = 6): \Illuminate\Support\Collection
    {
        $base = fn () => Post::published()
            ->with('category')
            ->whereKeyNot($post->getKey())
            ->latest('published_at');

        $related = $post->post_category_id
            ? $base()->where('post_category_id', $post->post_category_id)->take($take)->get()
            : collect();

        if ($related->count() >= $take) {
            return $related;
        }

        return $related->concat(
            $base()
                ->whereKeyNot($related->pluck('id')->all() ?: [0])
                ->take($take - $related->count())
                ->get()
        );
    }
}
