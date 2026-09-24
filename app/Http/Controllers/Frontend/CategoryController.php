<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\Catalog;
use App\Support\Url;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

/**
 * Danh mục mặt hàng — /danh-muc/{slug}. Dùng lại đúng view danh sách của
 * /san-pham, chỉ khác điều kiện lọc và tiêu đề.
 */
class CategoryController extends Controller
{
    public function __invoke(Category $category): View|RedirectResponse
    {
        // Danh mục phụ kiện đã có trang riêng với bố cục thẻ nhỏ — vào bằng
        // /danh-muc/phu-kien thì đẩy sang đó, khỏi hai URL cùng nội dung.
        if (Route::has('accessories') && $category->slug === config('catalog.frontend.accessory_category')) {
            return redirect()->route('accessories', status: 301);
        }

        return view('frontend.products', [
            'heading' => $category->name,
            'intro' => $category->description,
            'category' => $category,
            'categories' => Catalog::query('category')
                ->when(config('catalog.frontend.accessory_category'),
                    fn ($q, $slug) => $q->where('slug', '!=', $slug))
                ->orderBy('sort')
                ->get(),
            'seo' => $category->seo,
            'canonical' => Url::absolute('category', $category->slug),
            'products' => $category->products()
                ->published()
                ->with('category')
                ->orderBy('sort')
                ->paginate((int) config('catalog.frontend.per_page', 12)),
        ]);
    }
}
