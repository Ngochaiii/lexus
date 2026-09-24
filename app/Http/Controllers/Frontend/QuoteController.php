<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Catalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Trang nhận báo giá — /bao-gia.
 *
 * Đích đến của mọi nút "Báo giá" khi trình duyệt không chạy JavaScript. Có
 * JS thì public/assets/lead.js chặn click và mở popup ngay tại trang, nên
 * trang này chủ yếu là đường dự phòng + trang đích cho quảng cáo/SEO.
 *
 * ?xe={slug} chọn sẵn dòng xe — nút báo giá ở trang chi tiết xe truyền vào.
 * ?phien-ban={id} chọn sẵn phiên bản — nút trên thẻ phiên bản (trang chủ,
 * trang xe) truyền vào, để lead ghi đúng mẫu khách quan tâm.
 */
class QuoteController extends Controller
{
    public function __invoke(Request $request): View
    {
        $form = Catalog::query('form')
            ->where('key', 'nhan-bao-gia')
            ->where('is_active', true)
            ->firstOrFail();

        $products = Catalog::query('product')->published()->orderBy('sort')->get(['id', 'slug', 'name']);

        $variant = \App\Models\ProductVariant::query()
            ->whereIn('product_id', $products->modelKeys())
            ->find($request->integer('phien-ban') ?: null);

        return view('frontend.quote', [
            'form'            => $form,
            'products'        => $products,
            'selected'        => $variant ? $products->find($variant->product_id) : $products->firstWhere('slug', $request->query('xe')),
            'selectedVariant' => $variant,
        ]);
    }
}
