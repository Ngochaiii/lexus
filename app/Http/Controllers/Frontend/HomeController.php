<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Catalog;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        /*
         * Các khối biên tập giữa trang (story, lối tắt, Omotenashi, Takumi,
         * đặc quyền) nằm trong `sections` của trang tĩnh slug `trang-chu`,
         * để sửa được trong admin mà không phải đụng view. Chưa tạo trang đó
         * thì phần giữa rỗng, các khối còn lại vẫn chạy.
         */
        $homePage = Catalog::query('page')->published()->where('slug', 'trang-chu')->first();

        return view('frontend.home', [
            'homeSections' => $homePage?->renderableSections() ?? [],

            // Banner hero. Chưa khai banner nào thì view lùi về dùng ảnh mặt
            // hàng — site mới dựng chưa kịp nhập banner vẫn có hero tử tế.
            'banners' => Catalog::feature('banners')
                ? Catalog::query('banner')->active()->orderBy('sort')->get()
                : collect(),

            'products' => Catalog::query('product')
                ->published()
                ->notInCategory(config('catalog.frontend.accessory_category'))
                ->with(['category', 'variants'])
                ->orderBy('sort')
                ->take((int) config('catalog.frontend.home.products', 8))
                ->get(),

            // Tin tức tắt được qua config('catalog.features.posts').
            'posts' => Catalog::feature('posts')
                ? Catalog::query('post')
                    ->published()
                    ->with('category')
                    ->latest('published_at')
                    ->take((int) config('catalog.frontend.home.posts', 3))
                    ->get()
                : collect(),
        ]);
    }
}
