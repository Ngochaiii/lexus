<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Catalog;
use App\Support\Url;
use Illuminate\Contracts\View\View;

/**
 * Danh sách toàn bộ mặt hàng — /san-pham (tiền tố lấy từ config).
 */
class ProductIndexController extends Controller
{
    public function __invoke(): View
    {
        // Phụ kiện có trang riêng (/phu-kien) nên không lẫn vào đây, cả ở
        // dải chip danh mục lẫn danh sách.
        $accessories = config('catalog.frontend.accessory_category');

        return view('frontend.products', [
            'heading' => catalog_label('product.plural'),
            'intro' => null,
            'canonical' => Url::route('products.index'),
            'categories' => Catalog::query('category')
                ->when($accessories, fn ($q, $slug) => $q->where('slug', '!=', $slug))
                ->orderBy('sort')
                ->get(),
            'products' => Catalog::query('product')
                ->published()
                ->notInCategory($accessories)
                ->with('category')
                ->orderBy('sort')
                ->paginate((int) config('catalog.frontend.per_page', 12)),
        ]);
    }
}
