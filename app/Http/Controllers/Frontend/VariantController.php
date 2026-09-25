<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\OnRoadPrice;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

/**
 * Trang riêng của một phiên bản — /san-pham/{xe}/{phien-ban}.
 *
 * Người mua gõ đích danh phiên bản ("giá lexus rx 350h premium", "lx 600 vip
 * lăn bánh"): trang đích có tiêu đề, giá, lăn bánh và FAQ đúng phiên bản đó
 * xếp hạng tốt hơn trang dòng xe chung. Nội dung dựng từ dữ liệu thật (giá,
 * ghi chú, thông số, so sánh với các bản cùng dòng) nên mỗi trang khác nhau,
 * không phải bản sao của trang dòng xe.
 */
class VariantController extends Controller
{
    public function __invoke(Product $product, ProductVariant $variant): View
    {
        abort_unless(
            $product->newQuery()->published()->whereKey($product->getKey())->exists(),
            404
        );

        $product->load(['category', 'options', 'variants' => fn ($q) => $q->orderBy('sort')]);

        $siblings = $product->variants->reject(fn ($v) => $v->is($variant))->values();
        $onRoad = OnRoadPrice::for($variant);

        // Các mục ảnh của dòng xe (Thiết kế, Nội thất, Vận hành, Chi tiết, An toàn,
        // Thư viện) — khách bấm vào một phiên bản vẫn xem đủ xe. Bỏ mục hỏi đáp
        // của dòng xe: trang phiên bản có hỏi đáp riêng (một FAQPage duy nhất).
        $sections = collect($product->renderableSections())
            ->reject(fn (array $section) => ($section['type'] ?? null) === 'faq')
            ->values()->all();

        return view('frontend.variant', [
            'product' => $product,
            'variant' => $variant,
            'sections' => $sections,
            'siblings' => $siblings,
            'onRoad' => $onRoad,
            'specs' => $this->specsFor($product, $variant),
            'faq' => $this->faq($product, $variant, $siblings, $onRoad),
        ]);
    }

    /**
     * Nhóm thông số của dòng xe, bỏ nhóm ghi rõ là của phiên bản KHÁC
     * (vd "Động cơ — RX 500h F SPORT Performance" không hiện ở trang RX 350h).
     *
     * @return array<int, array<string, mixed>>
     */
    private function specsFor(Product $product, ProductVariant $variant): array
    {
        $others = $product->variants->reject(fn ($v) => $v->is($variant))->pluck('name');

        return collect((array) $product->specs)
            ->reject(function ($group) use ($others, $variant) {
                $title = (string) ($group['group'] ?? '');

                return ! str_contains($title, $variant->name)
                    && $others->contains(fn ($name) => str_contains($title, $name));
            })
            ->values()->all();
    }

    /**
     * Hỏi đáp riêng của phiên bản — câu trả lời lấy thẳng từ dữ liệu, tự đúng
     * khi đổi giá trong admin. Đi vào FAQPage JSON-LD qua partial sections.
     *
     * @return array<string, mixed>|null
     */
    private function faq(Product $product, ProductVariant $variant, $siblings, ?array $onRoad): ?array
    {
        $name = Str::startsWith($variant->name, 'Lexus') ? $variant->name : 'Lexus '.$variant->name;
        $dealer = catalog_setting('site_name', config('app.name'));
        $address = collect(config('catalog.seo.organization.address', []))
            ->only(['streetAddress', 'addressLocality', 'addressRegion'])->filter()->implode(', ');
        $hotline = catalog_setting('advisor_phone') ?: catalog_setting('hotline');

        $rows = [];

        if ($price = catalog_money($variant->price)) {
            $rows[] = ['label' => "Giá xe {$name} 2026 bao nhiêu?",
                'value' => "Giá niêm yết {$name} là {$price} (đã gồm VAT)"
                    .($onRoad ? '; lăn bánh tại '.$onRoad['region'].' tạm tính khoảng '.catalog_money_short($onRoad['total']).'.' : '.')];
        }

        if ($onRoad) {
            $pct = rtrim(rtrim(number_format($onRoad['rate'] * 100, 1, ',', '.'), '0'), ',');
            $rows[] = ['label' => "Giá lăn bánh {$name} tại {$onRoad['region']} gồm những khoản nào?",
                'value' => 'Giá niêm yết '.catalog_money($variant->price).' + lệ phí trước bạ '.$pct.'% ('.catalog_money($onRoad['tax']).')'
                    .' + phí cấp biển số '.catalog_money($onRoad['plate']).' = khoảng '.catalog_money($onRoad['total'])
                    .'. Chưa gồm phí đăng kiểm, phí bảo trì đường bộ và bảo hiểm (vài triệu đồng).'];
        }

        foreach ($siblings->take(2) as $other) {
            $diff = filled($variant->price) && filled($other->price) ? abs((float) $variant->price - (float) $other->price) : null;
            $rows[] = ['label' => "{$variant->name} khác {$other->name} thế nào?",
                'value' => collect([
                    filled($variant->note) ? "{$variant->name}: {$variant->note}." : null,
                    filled($other->note) ? "{$other->name}: {$other->note}." : null,
                    $diff ? 'Chênh lệch giá niêm yết '.catalog_money_short($diff).' ('
                        .((float) $variant->price > (float) $other->price ? $variant->name : $other->name).' cao hơn).' : null,
                ])->filter()->implode(' ')];
        }

        $rows[] = ['label' => "Mua {$name} chính hãng ở đâu tại Hà Nội?",
            'value' => trim("{$dealer}".($address ? " — {$address}" : '').'. Có xe lái thử và xưởng dịch vụ chính hãng tại đại lý.'
                .($hotline ? ' Hotline '.\App\Support\Phone::format($hotline).'.' : ''))];

        $rows = array_values(array_filter($rows, fn ($r) => filled($r['value'])));

        return $rows ? ['type' => 'faq', 'title' => 'Hỏi đáp', 'intro' => "Câu hỏi thường gặp về {$name}", 'rows' => $rows] : null;
    }
}
