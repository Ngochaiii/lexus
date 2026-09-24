<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Catalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Trang "Đặt cọc & lái thử" — /dat-coc (tiền tố lấy từ config).
 *
 * Không có bảng đơn hàng riêng: mỗi hình thức là một Form trong admin, gửi
 * đi vẫn là một lead như mọi form khác. Trang này chỉ dựng lại bố cục wizard
 * của bản thiết kế quanh các form đó.
 *
 * Query string (đều tuỳ chọn, để link từ trang khác vào đúng trạng thái):
 *   ?xe=<slug sản phẩm>   — chọn sẵn mẫu xe
 *   ?hinh-thuc=<form key> — mở sẵn tab "Đặt cọc" hay "Đăng ký lái thử"
 */
class BookingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $keys = array_values((array) config('catalog.frontend.booking.forms', []));

        // Giữ đúng thứ tự khai trong config — whereIn trả về theo thứ tự DB.
        $forms = Catalog::query('form')
            ->whereIn('key', $keys)
            ->where('is_active', true)
            ->with('fields')
            ->get()
            ->sortBy(fn ($form) => array_search($form->key, $keys, true))
            ->values();

        abort_if($forms->isEmpty(), 404);

        $products = Catalog::query('product')
            ->published()
            ->notInCategory(config('catalog.frontend.accessory_category'))
            ->with('category')
            ->orderBy('sort')
            ->get();

        return view('frontend.booking', [
            'forms' => $forms,
            'products' => $products,
            'mode' => $forms->firstWhere('key', $request->query('hinh-thuc')) ?? $forms->first(),
            'selected' => $products->firstWhere('slug', $request->query('xe')) ?? $products->first(),
        ]);
    }
}
