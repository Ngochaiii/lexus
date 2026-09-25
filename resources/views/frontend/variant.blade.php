{{--
    Trang riêng một phiên bản — /san-pham/{xe}/{phien-ban}.

    Thứ tự giống trang dòng xe (giá trước, rồi màu, rồi nội dung) nhưng mọi
    thứ xoay quanh ĐÚNG phiên bản này: giá + bảng tính lăn bánh, so sánh với
    các bản cùng dòng, thông số của bản này, hỏi đáp riêng. Nút báo giá gửi
    kèm phiên bản → lead ghi đúng mẫu.

    Biến từ VariantController: $product · $variant · $sections · $siblings · $onRoad · $specs · $faq
--}}
@php
    $site      = catalog_setting('site_name', config('app.name'));
    $fullName  = str_starts_with($variant->name, 'Lexus') ? $variant->name : 'Lexus '.$variant->name;
    $priceFull = catalog_money($variant->price);
    $priceShort= catalog_money_short($variant->price);
    $roadShort = $onRoad ? catalog_money_short($onRoad['total']) : null;

    // Tiêu đề ≤ ~65 ký tự: tên + giá, thêm lăn bánh nếu còn chỗ.
    $seoTitle = $fullName.' 2026'.($priceShort ? ': giá '.$priceShort : '');
    if ($roadShort && mb_strlen($seoTitle.', lăn bánh '.$roadShort) <= 62) {
        $seoTitle .= ', lăn bánh '.$roadShort;
    }
    if (mb_strlen($seoTitle.' | '.$site) <= 70) {
        $seoTitle .= ' | '.$site;
    }

    $seoDesc = 'Giá '.$fullName.' 2026 tại Hà Nội: '
        .($priceFull ? 'niêm yết '.$priceFull : 'đang cập nhật')
        .($roadShort ? ', lăn bánh tạm tính khoảng '.$roadShort : '').'. '
        .(filled($variant->note) ? $variant->note.'. ' : '')
        .'Xem màu, thông số và nhận báo giá chi tiết tại '.$site.'.';

    $canonical = \App\Support\Url::variant($product->slug, $variant->slug, true);
    $image     = $variant->image ?: data_get($product->hero, 'src');
    $heroImage = catalog_image(data_get($product->hero, 'src')) ?: asset('assets/co-so/khu-trung-bay.webp');
    $pct       = $onRoad ? rtrim(rtrim(number_format($onRoad['rate'] * 100, 1, ',', '.'), '0'), ',') : null;
    $options   = $product->options;
@endphp
@extends('frontend.layout', [
    'title'       => $seoTitle,
    'description' => $seoDesc,
    'canonical'   => $canonical,
    'ogType'      => 'product',
    'ogImage'     => catalog_image($image),
    'jsonld'      => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::forVariant($product, $variant, $seoDesc),
        \App\Support\JsonLd::forBreadcrumb([
            ['name' => 'Trang chủ', 'url' => route('home')],
            ['name' => 'Dòng xe', 'url' => route('products.index')],
            ['name' => $product->name, 'url' => \App\Support\Url::absolute('product', $product->slug)],
            ['name' => $variant->name, 'url' => $canonical],
        ]),
        \App\Support\JsonLd::forFaq($faq ? [$faq] : [], $canonical),
        \App\Support\JsonLd::organization(),
    ),
])

@section('content')
    {{-- ══ 1 · Giá ngay đầu trang ══ --}}
    <section class="container variant-page-hero">
        <div class="variant-page-hero__media">
            <x-img :src="$image" :alt="$fullName" :eager="true" sizes="(max-width: 900px) 100vw, 58vw" />
            @if ($product->category)<span class="model-label">{{ mb_strtoupper($product->category->name) }}</span>@endif
        </div>
        <div class="variant-page-hero__copy">
            <nav class="breadcrumb" aria-label="Đường dẫn">
                <a href="{{ route('home') }}">Trang chủ</a> &nbsp;/&nbsp;
                <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a> &nbsp;/&nbsp;
                <span>{{ $variant->name }}</span>
            </nav>
            <div class="eyebrow">{{ mb_strtoupper($product->name) }} · PHIÊN BẢN</div>
            <h1>{{ $fullName }} 2026</h1>
            @if (filled($variant->note))<p class="variant-page-hero__note">{{ $variant->note }}</p>@endif

            <dl class="variant-prices variant-page-hero__prices">
                <div class="is-list">
                    <dt>Giá niêm yết (đã gồm VAT)</dt>
                    <dd>{{ $priceFull ?: 'Đang cập nhật' }}</dd>
                </div>
                @if ($onRoad)
                    <div>
                        <dt>Lăn bánh {{ $onRoad['region'] }} (tạm tính)</dt>
                        <dd>{{ catalog_money($onRoad['total']) }}</dd>
                    </div>
                @endif
            </dl>

            <div class="actions">
                <a class="button" href="{{ route('quote', ['xe' => $product->slug, 'phien-ban' => $variant->id]) }}"
                   data-quote data-product="{{ $product->id }}"
                   data-variant="{{ $variant->id }}" data-variant-name="{{ $variant->name }}">Nhận báo giá chi tiết</a>
                <a class="text-link" href="{{ route('booking', ['xe' => $product->slug]) }}">Đăng ký lái thử</a>
            </div>
        </div>
    </section>

    @if (filled($variant->description))
        <section class="container variant-page-intro">
            <p>{!! nl2br(e($variant->description)) !!}</p>
        </section>
    @endif

    {{-- ══ Bảng tính lăn bánh ══ --}}
    @if ($onRoad)
        <section class="section stone" id="lan-banh">
            <div class="container">
                <div class="section-heading">
                    <div>
                        <div class="eyebrow">GIÁ LĂN BÁNH {{ mb_strtoupper($onRoad['region']) }}</div>
                        <h2>{{ $fullName }} lăn bánh bao nhiêu?</h2>
                    </div>
                    <p class="small">Tạm tính theo quy định hiện hành. Chưa gồm phí đăng kiểm, phí bảo trì đường bộ và bảo hiểm (vài triệu đồng).</p>
                </div>
                <div class="table-wrap">
                    <table class="data-table variant-cost">
                        <caption class="studio-sr">Bảng tính giá lăn bánh {{ $fullName }} tại {{ $onRoad['region'] }}</caption>
                        <tbody>
                            <tr><th scope="row">Giá niêm yết</th><td>{{ $priceFull }}</td></tr>
                            <tr><th scope="row">Lệ phí trước bạ ({{ $pct }}%)</th><td>{{ catalog_money($onRoad['tax']) }}</td></tr>
                            <tr><th scope="row">Phí cấp biển số {{ $onRoad['region'] }}</th><td>{{ catalog_money($onRoad['plate']) }}</td></tr>
                            <tr class="is-total"><th scope="row">Lăn bánh tạm tính</th><td>{{ catalog_money($onRoad['total']) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    {{-- ══ 2 · Màu sắc ══ --}}
    @include('frontend.partials.vehicle-studio')

    {{-- ══ So sánh với các bản cùng dòng ══ --}}
    @if ($siblings->isNotEmpty())
        <section class="container section" id="so-sanh">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">CÁC PHIÊN BẢN {{ mb_strtoupper($product->name) }}</div>
                    <h2>So sánh {{ $variant->name }} với bản khác.</h2>
                </div>
                <a class="text-link" href="{{ route('products.show', $product->slug) }}">Toàn bộ {{ $product->name }}</a>
            </div>
            <div class="table-wrap">
                <table class="data-table variant-compare">
                    <caption class="studio-sr">So sánh giá các phiên bản {{ $product->name }}</caption>
                    <thead>
                        <tr><th scope="col">Phiên bản</th><th scope="col">Giá niêm yết</th><th scope="col">Lăn bánh tạm tính</th><th scope="col">Điểm khác</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($product->variants as $row)
                            @php $road = \App\Support\OnRoadPrice::for($row); @endphp
                            <tr @class(['is-current' => $row->is($variant)])>
                                <th scope="row">
                                    @if ($row->is($variant))
                                        {{ $row->name }} <small>Đang xem</small>
                                    @else
                                        <a href="{{ \App\Support\Url::variant($product->slug, $row->slug) }}">{{ $row->name }}</a>
                                    @endif
                                </th>
                                <td>{{ catalog_money($row->price) ?: 'Đang cập nhật' }}</td>
                                <td>{{ $road ? catalog_money_short($road['total']) : '—' }}</td>
                                <td>{{ $row->note }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    {{-- ══ 3 · Điểm nổi bật & thông số ══ --}}
    @php $highlights = collect($product->highlights ?? [])->filter(fn ($h) => filled($h['value'] ?? null)); @endphp
    @if ($highlights->isNotEmpty())
        <div class="container spec-strip">
            @foreach ($highlights as $item)
                <div>
                    <small>{{ mb_strtoupper($item['label'] ?? '') }}</small>
                    <strong>{{ $item['value'] }}{{ filled($item['unit'] ?? null) ? ' '.$item['unit'] : '' }}</strong>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Thiết kế, nội thất, vận hành, chi tiết, an toàn, thư viện — của dòng xe. --}}
    @if (! empty($sections))
        @include('frontend.partials.sections', ['sections' => $sections])
    @endif

    @if (filled($specs))
        <section class="container section" id="thong-so">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">THÔNG SỐ</div>
                    <h2>Thông số {{ $fullName }}.</h2>
                </div>
            </div>
            <div class="accordion">
                @foreach ($specs as $group)
                    <details @if ($loop->first) open @endif>
                        <summary>{{ $group['group'] ?? 'Thông số' }}</summary>
                        <div class="table-wrap">
                            <table class="data-table">
                                <tbody>
                                @foreach ($group['rows'] ?? [] as $row)
                                    <tr><td>{{ $row['label'] ?? '' }}</td><td>{{ $row['value'] ?? '' }}</td></tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ══ 4 · Hỏi đáp riêng phiên bản ══ --}}
    @if ($faq)
        {{-- Khoá mảng = số thứ tự sau các mục trên → id="muc-N" không trùng. --}}
        @include('frontend.partials.sections', ['sections' => [count($sections) => $faq], 'numbered' => false])
    @endif

    <section class="container section variant-page-more">
        <p>Xem thiết kế, nội thất, vận hành, an toàn và thư viện ảnh đầy đủ tại trang
            <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>,
            hoặc so giá mọi dòng xe tại <a href="{{ route('pages.show', 'bang-gia') }}">bảng giá xe Lexus</a>.</p>
    </section>

    @include('frontend.partials.conversion')
@endsection
