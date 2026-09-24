<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Contracts\View\View;

class PostController extends Controller
{
    public function __invoke(Post $post): View
    {
        abort_unless(
            $post->newQuery()->published()->whereKey($post->getKey())->exists(),
            404
        );

        return view('frontend.post', [
            'post' => $post->load('category'),
            'sections' => $post->renderableSections(),
            'related' => $this->related($post),
        ]);
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
