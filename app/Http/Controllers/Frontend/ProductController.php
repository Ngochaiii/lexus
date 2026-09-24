<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Product;
use App\Support\Catalog;
use App\Support\FuelCalculator;
use App\Support\Loan;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __invoke(Product $product, Request $request): View
    {
        abort_unless(
            $product->newQuery()->published()->whereKey($product->getKey())->exists(),
            404
        );

        $product->load(['category', 'variants', 'options']);

        $sections = $product->renderableSections();

        return view('frontend.product', [
            'product' => $product,
            'sections' => $sections,
            'forms' => $this->forms($sections),
            'fuelCalc' => $this->fuelCalc($product, $request),
            'loan' => $this->loan($product, $request),
        ]);
    }

    /**
     * Form(s) đặt ở cuối trang chi tiết. Khoá khai trong config; form không
     * tồn tại, đã tắt, hoặc đã nhúng giữa trang rồi thì bỏ qua đúng cái đó.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @return Collection<int, Form>
     */
    protected function forms(array $sections): Collection
    {
        $keys = (array) config('catalog.frontend.product_forms', []);

        if (empty($keys) || ! Catalog::feature('forms')) {
            return new Collection;
        }

        // Người nhập đã tự chèn form này vào giữa trang bằng mục kiểu `form`
        // thì không dựng lại lần nữa ở cuối — hai form giống hệt nhau, id
        // trong DOM trùng, và khách không biết bấm cái nào.
        $embedded = collect($sections)
            ->filter(fn (array $section) => ($section['type'] ?? null) === 'form')
            ->pluck('form_key');

        $keys = array_diff($keys, $embedded->all());

        if (empty($keys)) {
            return new Collection;
        }

        return Catalog::query('form')
            ->with('fields')
            ->whereIn('key', $keys)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($form) => array_search($form->key, $keys, true))
            ->values();
    }

    /**
     * Trả góp cho trang chi tiết. Đọc số liệu từ query string như bộ so sánh
     * chi phí nhiên liệu — form GET, tính bằng PHP, không cần JS.
     *
     * Xe chưa có giá thì trả null, view tự ẩn cả khối.
     *
     * @return array<string, mixed>|null
     */
    protected function loan(Product $product, Request $request): ?array
    {
        if (! Catalog::feature('loan_calc')) {
            return null;
        }

        $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
        $price = (float) ($product->price_from ?: $variant?->price);

        if ($price <= 0) {
            return null;
        }

        $defaults = (array) config('catalog.loan', []);

        // Người dùng gõ "200.000.000" hay "200000000" đều nhận — bỏ hết ký tự
        // không phải số thay vì bắt họ gõ đúng định dạng.
        $down = $request->filled('down')
            ? (float) preg_replace('/\D/', '', (string) $request->query('down'))
            : $price * ((float) ($defaults['down_payment_percent'] ?? 30)) / 100;

        $months = (int) $request->query('months', $defaults['months'] ?? 60);
        $rate = (float) str_replace(',', '.', (string) $request->query('rate', $defaults['annual_rate'] ?? 9));

        return Loan::schedule($price, $down, $rate, $months) + [
            'price' => $price,
            'down' => round($down, 2),
            'rate' => $rate,
            'month_options' => (array) ($defaults['month_options'] ?? [12, 24, 36, 48, 60]),
        ];
    }

    /**
     * So sánh chi phí nhiên liệu — đọc quãng đường/tháng, loại nhiên liệu và
     * mức tiêu thụ từ query string (form GET, tải lại trang là ra kết quả
     * mới, không cần JS). null khi tắt feature hoặc biến thể thiếu dữ liệu.
     *
     * @return array<string, mixed>|null
     */
    protected function fuelCalc(Product $product, Request $request): ?array
    {
        if (! Catalog::feature('fuel_calc')) {
            return null;
        }

        $variant = FuelCalculator::variantFor($product);

        if (! FuelCalculator::usable($variant)) {
            return null;
        }

        $fuel = $request->query('fuel') === 'dau' ? 'dau' : 'xang';
        $cons = (float) str_replace(',', '.', (string) $request->query('cons', '8'));
        $km = (float) str_replace(',', '.', (string) $request->query('km', '3000'));

        return array_merge(
            ['variant' => $variant, 'fuel' => $fuel, 'cons' => $cons, 'km' => $km],
            FuelCalculator::compare($variant, $fuel, $cons, $km),
        );
    }
}
