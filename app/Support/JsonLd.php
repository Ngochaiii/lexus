<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

/**
 * Dựng structured data (schema.org) cho từng loại trang. Frontend nhúng vào
 * <script type="application/ld+json"> để Google — và các công cụ AI đọc
 * web (ChatGPT, Perplexity, Gemini…) — hiểu đúng thực thể trên trang.
 *
 * Trả về mảng — frontend tự json_encode, hoặc dùng ::script() cho sẵn thẻ.
 *
 * Trang web dùng ::graph(...) để gộp nhiều thực thể vào một khối @graph và
 * nối chúng với nhau bằng @id: xe → người bán là đại lý → đại lý có chuyên
 * viên, địa chỉ, giờ mở cửa. Nối như vậy máy mới hiểu "Lexus RX giá 3,35 tỷ,
 * bán tại Lexus Thăng Long ở Cầu Giấy" là MỘT câu chuyện, không phải ba mẩu
 * rời nhau.
 */
class JsonLd
{
    // ── Định danh dùng chung ─────────────────────────────────────────────

    public static function organizationId(): string
    {
        return rtrim((string) config('app.url'), '/').'/#organization';
    }

    public static function websiteId(): string
    {
        return rtrim((string) config('app.url'), '/').'/#website';
    }

    public static function advisorId(): string
    {
        return rtrim((string) config('app.url'), '/').'/#advisor';
    }

    // ── Xe ───────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public static function forProduct(Model $product): array
    {
        $url = data_get($product->seo, 'canonical') ?: Url::absolute('product', $product->slug);

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => $url.'#product',
            'name' => $product->name,
            'description' => data_get($product->seo, 'description') ?: $product->tagline,
            'url' => $url,
            'brand' => ['@type' => 'Brand', 'name' => (string) config('catalog.seo.brand', 'Lexus')],
            'category' => $product->category_id ? $product->category?->name : null,
        ];

        if ($image = data_get($product->seo, 'image') ?: data_get($product->hero, 'src')) {
            $data['image'] = Url::asset($image);
        }

        // Tên màu là thông tin khách hay hỏi ("RX có mấy màu?").
        if (method_exists($product, 'options')) {
            $colors = $product->options()->orderBy('sort')->pluck('name')->filter()->values()->all();
            if ($colors) {
                $data['color'] = implode(', ', $colors);
            }
        }

        $data['offers'] = self::offers($product, $url);

        return array_filter($data, fn ($v) => filled($v));
    }

    /**
     * Một phiên bản (trang /san-pham/{xe}/{phien-ban}): Product con của dòng
     * xe, một Offer đúng giá — Google hiện giá ngay trên kết quả tìm kiếm.
     *
     * @return array<string, mixed>
     */
    public static function forVariant(Model $product, Model $variant, ?string $description = null): array
    {
        $url = Url::variant($product->slug, $variant->slug, true);
        $name = str_starts_with($variant->name, 'Lexus') ? $variant->name : 'Lexus '.$variant->name;

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => $url.'#product',
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'image' => Url::asset($variant->image ?: data_get($product->hero, 'src')),
            'brand' => ['@type' => 'Brand', 'name' => (string) config('catalog.seo.brand', 'Lexus')],
            'model' => $variant->name,
            'category' => $product->category?->name,
            'isVariantOf' => ['@id' => Url::absolute('product', $product->slug).'#product'],
            'offers' => filled($variant->price) ? [
                '@type' => 'Offer',
                'price' => (string) $variant->price,
                'priceCurrency' => 'VND',
                'url' => $url,
                'availability' => 'https://schema.org/InStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => ['@id' => self::organizationId()],
            ] : null,
        ], fn ($v) => filled($v));
    }

    /**
     * Có phiên bản kèm giá → AggregateOffer (dải giá thấp–cao + từng phiên
     * bản). Không có → một Offer theo "giá từ", như trước.
     *
     * @return array<string, mixed>|null
     */
    protected static function offers(Model $product, string $url): ?array
    {
        $seller = ['@id' => self::organizationId()];

        $variants = method_exists($product, 'variants')
            ? $product->variants()->whereNotNull('price')->orderBy('sort')->get()
            : collect();

        if ($variants->isNotEmpty()) {
            return [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'VND',
                'lowPrice' => (string) $variants->min('price'),
                'highPrice' => (string) $variants->max('price'),
                'offerCount' => $variants->count(),
                'availability' => $variants->every(fn ($v) => str_contains((string) $v->note, 'dự kiến'))
                    ? 'https://schema.org/PreOrder'
                    : 'https://schema.org/InStock',
                'seller' => $seller,
                'offers' => $variants->map(fn ($v) => array_filter([
                    '@type' => 'Offer',
                    'name' => $v->name,
                    'description' => $v->note,
                    'price' => (string) $v->price,
                    'priceCurrency' => 'VND',
                    // Mỗi phiên bản có trang riêng — trỏ Offer về đó.
                    'url' => filled($v->slug) ? Url::variant($product->slug, $v->slug, true) : $url.'#versions',
                    // Phiên bản "giá dự kiến" (xe chưa ra mắt) là đặt trước, không phải còn hàng.
                    'availability' => str_contains((string) $v->note, 'dự kiến')
                        ? 'https://schema.org/PreOrder'
                        : 'https://schema.org/InStock',
                    'itemCondition' => 'https://schema.org/NewCondition',
                ]))->all(),
            ];
        }

        if (filled($product->price_from)) {
            return [
                '@type' => 'Offer',
                'price' => (string) $product->price_from,
                'priceCurrency' => 'VND',
                'availability' => 'https://schema.org/InStock',
                'seller' => $seller,
            ];
        }

        return null;
    }

    // ── Bài viết ─────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public static function forPost(Model $post): array
    {
        $url = data_get($post->seo, 'canonical') ?: Url::absolute('post', $post->slug);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            '@id' => $url.'#article',
            'headline' => $post->title,
            'description' => data_get($post->seo, 'description') ?: $post->excerpt,
            'url' => $url,
            'mainEntityOfPage' => $url,
            'image' => Url::asset(data_get($post->seo, 'image') ?: $post->cover),
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => ($post->updated_at ?? $post->published_at)?->toAtomString(),
            'inLanguage' => 'vi-VN',
            'articleSection' => $post->post_category_id ? $post->category?->name : null,
            'keywords' => data_get($post->seo, 'keywords'),
            // Tác giả là người thật, có tên — tín hiệu E-E-A-T. Chuyên viên
            // tư vấn của đại lý là người viết/duyệt nội dung.
            'author' => filled(Setting::get('advisor_name')) ? self::advisor() : ['@id' => self::organizationId()],
            'publisher' => ['@id' => self::organizationId()],
        ], fn ($v) => filled($v));
    }

    // ── Trang tĩnh ───────────────────────────────────────────────────────

    /**
     * WebPage theo loại trang: showroom/liên hệ là ContactPage, thế giới
     * Lexus là AboutPage — để máy biết trang nào trả lời câu "ở đâu, liên
     * hệ thế nào".
     *
     * @return array<string, mixed>
     */
    public static function forPage(Model $page): array
    {
        $url = Url::absolute('page', $page->slug);

        $type = match ($page->slug) {
            'showroom', 'lien-he' => 'ContactPage',
            'the-gioi-lexus' => 'AboutPage',
            default => 'WebPage',
        };

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => $type,
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => data_get($page->seo, 'title') ?: $page->title,
            'description' => data_get($page->seo, 'description'),
            'inLanguage' => 'vi-VN',
            'dateModified' => $page->updated_at?->toAtomString(),
            'isPartOf' => ['@id' => self::websiteId()],
            'about' => $type !== 'WebPage' ? ['@id' => self::organizationId()] : null,
        ], fn ($v) => filled($v));
    }

    /**
     * FAQPage từ các mục kiểu `faq` trong sections (label = câu hỏi,
     * value = câu trả lời). Không có câu hỏi nào thì trả null.
     *
     * @return array<string, mixed>|null
     */
    public static function forFaq(iterable $sections, ?string $url = null): ?array
    {
        $questions = collect($sections)
            ->filter(fn ($s) => ($s['type'] ?? null) === 'faq')
            ->flatMap(fn ($s) => $s['rows'] ?? [])
            ->filter(fn ($row) => filled($row['label'] ?? null) && filled($row['value'] ?? null))
            ->map(fn ($row) => [
                '@type' => 'Question',
                'name' => $row['label'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags((string) $row['value'])],
            ])
            ->values()
            ->all();

        if (! $questions) {
            return null;
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            '@id' => $url ? $url.'#faq' : null,
            'mainEntity' => $questions,
        ]);
    }

    /**
     * Trang danh sách (dòng xe, danh mục, tin tức): CollectionPage chứa một
     * ItemList — máy đọc được "trang này liệt kê những xe/bài nào".
     *
     * @param  array<int, array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public static function forCollection(string $name, string $url, array $items, ?string $description = null): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => $name,
            'description' => $description,
            'inLanguage' => 'vi-VN',
            'isPartOf' => ['@id' => self::websiteId()],
            'mainEntity' => $items ? [
                '@type' => 'ItemList',
                'numberOfItems' => count($items),
                'itemListElement' => array_map(fn (array $item, int $i): array => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $item['name'],
                    'url' => $item['url'],
                ], $items, array_keys($items)),
            ] : null,
        ], fn ($v) => filled($v));
    }

    /** @return array<string, mixed> */
    public static function forBreadcrumb(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $item, int $i): array => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'] ?? null,
            ], $items, array_keys($items)),
        ];
    }

    // ── Đại lý, website, chuyên viên ─────────────────────────────────────

    /** @return array<string, mixed> */
    public static function organization(): array
    {
        $org = (array) config('catalog.seo.organization', []);
        $name = ($org['name'] ?? null) ?? Setting::get('site_name');
        $logo = ($org['logo'] ?? null) ?? Setting::get('logo');
        $address = array_filter((array) ($org['address'] ?? []));
        $brand = config('catalog.seo.brand');

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => $org['type'] ?? 'Organization',
            '@id' => self::organizationId(),
            'name' => $name,
            'url' => rtrim(config('app.url'), '/'),
            'logo' => Url::asset($logo),
            'image' => Url::asset(Setting::get('social_image') ?: $logo),
            'description' => Setting::get('site_description'),
            'telephone' => self::phone(Setting::get('hotline')),
            'email' => Setting::get('email'),
            'address' => $address ? ['@type' => 'PostalAddress'] + $address : Setting::get('address'),
            'hasMap' => Setting::get('map_url'),
            'openingHoursSpecification' => self::openingHours((array) ($org['opening_hours'] ?? [])),
            'brand' => filled($brand) ? ['@type' => 'Brand', 'name' => $brand] : null,
            'areaServed' => $org['area_served'] ?? null,
            'employee' => filled(Setting::get('advisor_name')) ? self::advisor() : null,
            'sameAs' => array_values(array_filter(
                ($org['sameAs'] ?? null)
                    ?: [Setting::get('facebook'), Setting::get('youtube'), Setting::get('tiktok')]
            )),
        ], fn ($v) => filled($v));
    }

    /** Chuyên viên tư vấn — người thật đứng tên nội dung và nhận cuộc gọi. */
    public static function advisor(): array
    {
        return array_filter([
            '@type' => 'Person',
            '@id' => self::advisorId(),
            'name' => Setting::get('advisor_name'),
            'jobTitle' => Setting::get('advisor_role'),
            'telephone' => self::phone(Setting::get('advisor_phone')),
            'worksFor' => ['@id' => self::organizationId()],
            'url' => Route::has('pages.show') ? route('pages.show', 'lien-he') : null,
        ], fn ($v) => filled($v));
    }

    /** @return array<string, mixed> */
    public static function website(): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => self::websiteId(),
            'url' => rtrim((string) config('app.url'), '/'),
            'name' => Setting::get('site_name') ?: config('app.name'),
            'inLanguage' => 'vi-VN',
            'publisher' => ['@id' => self::organizationId()],
        ]);
    }

    /** 0989345989 → +84989345989 (định dạng quốc tế schema.org khuyên dùng). */
    protected static function phone(?string $raw): ?string
    {
        $digits = Phone::normalize($raw);

        if (blank($digits)) {
            return $raw ?: null;
        }

        return str_starts_with($digits, '0') ? '+84'.substr($digits, 1) : $digits;
    }

    /**
     * ['Mo-Su 08:00-18:00'] → OpeningHoursSpecification.
     *
     * @param  array<int, string>  $lines
     * @return array<int, array<string, mixed>>
     */
    protected static function openingHours(array $lines): array
    {
        $days = ['Mo' => 'Monday', 'Tu' => 'Tuesday', 'We' => 'Wednesday', 'Th' => 'Thursday',
            'Fr' => 'Friday', 'Sa' => 'Saturday', 'Su' => 'Sunday'];
        $keys = array_keys($days);
        $out = [];

        foreach ($lines as $line) {
            if (! preg_match('/^(\w{2})(?:-(\w{2}))?\s+(\d{2}:\d{2})-(\d{2}:\d{2})$/', trim($line), $m)) {
                continue;
            }

            $from = array_search($m[1], $keys, true);
            $to = $m[2] !== '' ? array_search($m[2], $keys, true) : $from;

            if ($from === false || $to === false || $to < $from) {
                continue;
            }

            $out[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => array_map(fn ($k) => $days[$k], array_slice($keys, $from, $to - $from + 1)),
                'opens' => $m[3],
                'closes' => $m[4],
            ];
        }

        return $out;
    }

    // ── Gộp ──────────────────────────────────────────────────────────────

    /**
     * Gộp nhiều thực thể vào một khối @graph (bỏ null, bỏ @context con).
     *
     * @param  array<string, mixed>|null  ...$nodes
     * @return array<string, mixed>
     */
    public static function graph(?array ...$nodes): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => collect($nodes)->filter()
                ->map(fn (array $node) => array_diff_key($node, ['@context' => true]))
                ->values()->all(),
        ];
    }

    /** Bọc sẵn trong thẻ script để nhúng thẳng vào <head>. */
    public static function script(array $data): string
    {
        return '<script type="application/ld+json">'
            .json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)
            .'</script>';
    }
}
