<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * "Hồ sơ dữ liệu thật" gửi kèm yêu cầu viết bài cho Gemini.
 *
 * Bài về giá xe mà để AI tự nhớ giá thì gần như chắc chắn sai (giá đổi theo
 * tháng, AI học từ dữ liệu cũ hoặc thị trường khác). Ở đây mọi con số lấy
 * thẳng từ database — cùng nguồn với bảng giá, trang xe, JSON-LD — nên bài
 * Gemini viết khớp từng đồng với phần còn lại của website:
 *   - đại lý: tên, địa chỉ, hotline, chuyên viên (E-E-A-T);
 *   - quy định lăn bánh đang áp dụng (config catalog.on_road);
 *   - từng phiên bản: giá niêm yết + lăn bánh tạm tính TÍNH SẴN;
 *   - thông số chi tiết của dòng xe được nhắc trong tiêu đề/yêu cầu;
 *   - danh sách link nội bộ có thật để chèn (không bịa đường dẫn).
 */
class ArticleContext
{
    public static function build(string $title, ?string $instructions = null): string
    {
        $products = Catalog::query('product')->published()
            ->with(['variants' => fn ($q) => $q->orderBy('sort'), 'category'])
            ->orderBy('sort')->get();

        $focus = self::focusProducts($products, $title.' '.$instructions);

        return implode("\n\n", array_filter([
            self::dealer(),
            self::onRoadRules(),
            self::priceTable($products),
            self::focusDetails($focus),
            self::internalLinks($products),
        ]));
    }

    /**
     * Dòng xe được nhắc trong tiêu đề/yêu cầu ("RX", "Lexus ES", "LM 500h"…).
     *
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Product>
     */
    public static function focusProducts(Collection $products, string $text): Collection
    {
        return $products->filter(function (Product $product) use ($text): bool {
            $code = trim(Str::after($product->name, 'Lexus'));

            return $code !== '' && preg_match('/(?<![a-z0-9])'.preg_quote($code, '/').'(?![a-z])/iu', $text) === 1;
        })->values();
    }

    private static function dealer(): string
    {
        $address = collect(config('catalog.seo.organization.address', []))
            ->only(['streetAddress', 'addressLocality', 'addressRegion'])->filter()->implode(', ');

        $lines = array_filter([
            'Tên website/đại lý: '.catalog_setting('site_name', config('app.name')),
            filled($address) ? 'Địa chỉ showroom: '.$address : null,
            filled($hotline = catalog_setting('hotline')) ? 'Hotline: '.Phone::format($hotline) : null,
            filled($advisor = catalog_setting('advisor_name')) ? 'Chuyên viên tư vấn (người đứng tên bài): '.$advisor : null,
            'Giờ mở cửa: '.strtr(implode(', ', (array) config('catalog.seo.organization.opening_hours', [])), [
                'Mo-Su' => 'Thứ Hai – Chủ nhật', 'Mo-Fr' => 'Thứ Hai – Thứ Sáu', 'Mo-Sa' => 'Thứ Hai – Thứ Bảy', '-' => ' – ',
            ]),
            'Ngày viết bài: '.now()->format('d/m/Y').' (tháng '.now()->format('n/Y').')',
        ]);

        return "## ĐẠI LÝ\n- ".implode("\n- ", $lines);
    }

    private static function onRoadRules(): string
    {
        $c = config('catalog.on_road');
        $pct = fn (float $r): string => rtrim(rtrim(number_format($r * 100, 1, ',', '.'), '0'), ',').'%';

        return "## QUY ĐỊNH LĂN BÁNH ĐANG ÁP DỤNG ({$c['region']})\n"
            .'- Lệ phí trước bạ lần đầu ô tô con xăng/hybrid: '.$pct($c['tax_rate'])." giá niêm yết.\n"
            .'- Ô tô điện chạy pin (vd ES 500e): '.$pct($c['ev_tax_rate'])." (miễn đến hết năm 2030 theo Nghị định 202/2026/NĐ-CP).\n"
            .'- Phí cấp biển số: '.Money::format($c['plate_fee'])." (Hà Nội, từ 1/1/2026, Thông tư 155/2025/TT-BTC).\n"
            ."- Phí đăng kiểm, phí bảo trì đường bộ, bảo hiểm TNDS bắt buộc: nhỏ (vài triệu đồng), KHÔNG có số chính xác trong dữ liệu — chỉ được nêu tên khoản, không tự điền số.\n"
            .'- Bảo hiểm vật chất: tùy chọn, không cộng vào lăn bánh tạm tính.';
    }

    /** @param Collection<int, Product> $products */
    private static function priceTable(Collection $products): string
    {
        $rows = $products->flatMap(fn (Product $p) => $p->variants->map(function ($v) use ($p): string {
            $road = OnRoadPrice::for($v);

            return '| '.$p->name.' | '.$v->name.' | '
                .(Money::format($v->price) ?? 'Đang cập nhật (KHÔNG được tự đoán giá)').' | '
                .($road ? Money::format($road['total']).' (khoảng '.catalog_money_short($road['total']).')' : '—').' | '
                .($v->note ?: '').' |';
        }));

        return "## BẢNG GIÁ CHÍNH THỨC TỪNG PHIÊN BẢN (giá niêm yết đã gồm VAT; lăn bánh = niêm yết + trước bạ + biển số, đã tính sẵn)\n"
            ."| Dòng xe | Phiên bản | Giá niêm yết | Lăn bánh Hà Nội tạm tính | Ghi chú |\n|---|---|---|---|---|\n"
            .$rows->implode("\n");
    }

    /** @param Collection<int, Product> $focus */
    private static function focusDetails(Collection $focus): ?string
    {
        if ($focus->isEmpty()) {
            return null;
        }

        return $focus->map(function (Product $p): string {
            $out = ['## CHI TIẾT '.mb_strtoupper($p->name).' (nguồn: trang xe trên website)'];
            if (filled($p->tagline)) {
                $out[] = 'Định vị: '.$p->tagline;
            }
            foreach ((array) $p->highlights as $h) {
                if (filled($h['value'] ?? null)) {
                    $out[] = '- '.($h['label'] ?? '').': '.$h['value'].(filled($h['unit'] ?? null) ? ' '.$h['unit'] : '');
                }
            }
            foreach (array_slice((array) $p->specs, 0, 6) as $group) {
                $out[] = '### '.($group['group'] ?? 'Thông số');
                foreach (array_slice($group['rows'] ?? [], 0, 12) as $row) {
                    $out[] = '- '.($row['label'] ?? '').': '.($row['value'] ?? '');
                }
            }

            return implode("\n", $out);
        })->implode("\n\n");
    }

    /** @param Collection<int, Product> $products */
    private static function internalLinks(Collection $products): string
    {
        $links = $products->flatMap(fn (Product $p) => collect(['- '.$p->name.': '.route('products.show', $p->slug, false)])
            ->merge($p->variants->filter(fn ($v) => filled($v->slug))
                ->map(fn ($v) => '- '.$v->name.' (trang phiên bản: giá, lăn bánh, thông số): '.Url::variant($p->slug, $v->slug))));

        $pages = Page::query()->published()->whereIn('slug', ['bang-gia', 'tai-chinh', 'uu-dai', 'showroom', 'dich-vu', 'faq'])
            ->get(['slug', 'title'])->map(fn (Page $pg) => '- '.$pg->title.': '.route('pages.show', $pg->slug, false));

        $posts = Post::query()->published()->latest('published_at')->take(15)->get(['slug', 'title'])
            ->map(fn (Post $post) => '- '.$post->title.': '.route('posts.show', $post->slug, false));

        return "## LINK NỘI BỘ CÓ THẬT (chỉ dùng các đường dẫn này, dạng tương đối)\n"
            .$links->merge($pages)->merge($posts)
                ->push('- Nhận báo giá lăn bánh: '.route('quote', [], false))
                ->push('- Đăng ký lái thử: '.route('booking', [], false))
                ->implode("\n");
    }
}
