{{-- Chi tiết xe: dữ liệu admin + trình xem màu/360° tải riêng cho trang sản phẩm. --}}
@extends('frontend.layout', [
    'overlay'     => ! $studio,
    'salesBar'    => true,
    'title'       => data_get($product->seo, 'title', $product->name.' | '.catalog_setting('site_name', config('app.name'))),
    'description' => data_get($product->seo, 'description')
        ?: collect([$product->name, $product->tagline])->filter()->join(' — ')
            .'. Khám phá thiết kế, phiên bản và đăng ký lái thử.',
    'canonical'   => \App\Support\Url::absolute('product', $product->slug),
    'ogType'      => 'product',
    'ogImage'     => catalog_image(data_get($product->hero, 'src')),
    'jsonld'      => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::forProduct($product),
        \App\Support\JsonLd::forBreadcrumb([
            ['name' => 'Trang chủ', 'url' => route('home')],
            ['name' => 'Dòng xe', 'url' => route('products.index')],
            ['name' => $product->name, 'url' => \App\Support\Url::absolute('product', $product->slug)],
        ]),
        \App\Support\JsonLd::forFaq($sections, \App\Support\Url::absolute('product', $product->slug)),
        \App\Support\JsonLd::organization(),
    ),
])

@php
    $heroImage = catalog_image(data_get($product->hero, 'src')) ?: asset('assets/co-so/khu-trung-bay.webp');

    // Ảnh hero dưới ~1400px mà kéo tràn màn hình thì vỡ hạt. Khi đó chuyển
    // sang kiểu "studio": nền đá sáng, ảnh giữ gần kích thước gốc, header
    // nền trắng thay vì trong suốt. Upload ảnh lớn hơn là tự về kiểu tràn.
    $heroSize = \App\Support\Media::dimensions(data_get($product->hero, 'src'));
    $studio   = $heroSize && $heroSize['w'] < 1400;

    $heroSrcset = \App\Support\Media::srcset(data_get($product->hero, 'src'));
    $heroSizes  = $studio ? '(max-width: 900px) 100vw, 55vw' : '100vw';
@endphp

@push('preload')
    {{-- imagesrcset khớp srcset của <img> — thiếu thì trình duyệt tải hai lần. --}}
    <link rel="preload" as="image" fetchpriority="high" href="{{ $heroImage }}"
          @if ($heroSrcset) imagesrcset="{{ $heroSrcset }}" imagesizes="{{ $heroSizes }}" @endif>
@endpush

@section('content')
    @php
        $highlights = collect($product->highlights ?? [])->filter(fn ($h) => filled($h['value'] ?? null));
        $variants   = $product->variants;
        $options    = $product->options;

        // Thư viện có mục riêng thì thanh điều hướng mới hiện link "Thư viện".
        $galleryIndex = collect($sections)
            ->search(fn ($s) => ($s['layout'] ?? null) === 'gallery');
    @endphp

    {{--
        Thứ tự trang theo đúng câu hỏi trong đầu người đã bấm vào một dòng xe:
          1. "Bao nhiêu tiền?"  → Giá & phiên bản ngay sau hero (kèm lăn bánh tạm
             tính). Giấu giá xuống cuối khiến khách phải đi tìm — nhiều người bỏ
             trang ở đây. Biết giá sớm thì phần còn lại đọc với tâm thế "có đáng
             không" thay vì "chắc đắt lắm".
          2. "Xe của mình trông thế nào?" → Màu sắc & xoay 360°. Chọn màu là lúc
             khách bắt đầu hình dung chiếc xe là của mình (hiệu ứng sở hữu),
             bù lại cảm giác "đau ví" vừa gặp ở bước giá.
          3. "Vì sao đáng tiền?" → Điểm nổi bật, ngoại thất, nội thất, vận hành,
             chi tiết, an toàn, thư viện ảnh (các mục admin, giữ thứ tự nhập).
          4. Gỡ băn khoăn → Hỏi đáp (trong các mục), thông số, rồi form cuối trang.
        Mỗi bước có một lời mời hành động (báo giá / lái thử) đúng lúc khách
        vừa trả lời xong câu hỏi của mình.
    --}}

    {{-- ══ Hero toàn màn hình ══ --}}
    <section class="hero model-hero {{ $studio ? 'is-studio' : '' }}"
             @if ($studio) style="--hero-native: {{ (int) round($heroSize['w'] * 1.25) }}px" @endif>
        <img class="hero-media" src="{{ $heroImage }}" alt="{{ $product->name }}{{ filled($product->tagline) ? ' — '.$product->tagline : '' }}"
             @if ($heroSrcset) srcset="{{ $heroSrcset }}" sizes="{{ $heroSizes }}" @endif
             width="{{ $heroSize['w'] ?? 1600 }}" height="{{ $heroSize['h'] ?? 1067 }}" fetchpriority="high" loading="eager" decoding="async">
        <div class="container">
            <div class="hero-copy">
                <div class="eyebrow">THE {{ mb_strtoupper($product->name) }}</div>
                <h1>{{ mb_strtoupper($product->name) }}</h1>
                @if (filled($product->tagline))<h2>{{ $product->tagline }}</h2>@endif
                @if ($price = catalog_money($product->price_from))
                    <p>Giá từ {{ $price }}</p>
                @elseif ($variants->isNotEmpty())
                    <p>Giá đang cập nhật</p>
                @endif
                <div class="actions">
                    @if ($variants->isNotEmpty())
                        <a class="button light" href="#versions">Xem giá {{ $variants->count() }} phiên bản</a>
                    @endif
                    <a class="text-link" href="{{ route('quote', ['xe' => $product->slug]) }}" data-quote data-product="{{ $product->id }}">Nhận báo giá</a>
                </div>
            </div>
        </div>
        <div class="hero-bottom">
            <span>{{ mb_strtoupper($product->category?->name ?? '') }}</span>
            <a href="{{ $variants->isNotEmpty() ? '#versions' : '#overview' }}">{{ $variants->isNotEmpty() ? 'XEM GIÁ' : 'KHÁM PHÁ' }} &nbsp; ↓</a>
        </div>
    </section>

    {{-- Thanh điều hướng trong trang, dính đỉnh sau khi qua hero — cùng thứ tự trang. --}}
    <nav class="model-nav" aria-label="Nội dung dòng xe">
        <strong>{{ mb_strtoupper($product->name) }}</strong>
        <div>
            @if ($variants->isNotEmpty())<a href="#versions">Giá & phiên bản</a>@endif
            @if (catalog_feature('options') && $options->isNotEmpty())<a href="#vehicle-studio">Màu sắc</a>@endif
            @foreach (collect($sections)->take(3) as $i => $section)
                <a href="#muc-{{ $i }}">{{ $section['title'] ?? 'Mục '.($i + 1) }}</a>
            @endforeach
            @if (filled($product->specs))<a href="#specs">Thông số</a>@endif
        </div>
        <a class="button" href="{{ route('quote', ['xe' => $product->slug]) }}" data-quote data-product="{{ $product->id }}">Báo giá</a>
    </nav>

    {{-- ══ 1 · Giá & phiên bản ══ --}}
    @if ($variants->isNotEmpty())
        @php
            $onRoad = config('catalog.on_road');
            $taxPct = rtrim(rtrim(number_format($onRoad['tax_rate'] * 100, 1, ',', '.'), '0'), ',');
        @endphp
        <section class="section versions" id="versions">
            <div class="container">
                <div class="section-heading">
                    <div>
                        <div class="eyebrow">GIÁ NIÊM YẾT · {{ $variants->count() }} PHIÊN BẢN</div>
                        <h2>Giá {{ $product->name }} theo từng phiên bản.</h2>
                    </div>
                    <p class="small">
                        Giá niêm yết đã gồm VAT. Lăn bánh tạm tính tại {{ $onRoad['region'] }} = giá niêm yết
                        + lệ phí trước bạ {{ $taxPct }}% + biển số {{ catalog_money_short($onRoad['plate_fee']) }};
                        chưa gồm đăng kiểm, phí đường bộ, bảo hiểm (vài triệu đồng).
                    </p>
                </div>
                <div class="variant-grid">
                    @foreach ($variants as $variant)
                        @php $estimate = \App\Support\OnRoadPrice::for($variant); @endphp
                        <article class="variant">
                            @if (filled($variant->image))
                                <div class="variant-image">
                                    <x-img :src="$variant->image" :alt="$product->name.' — '.$variant->name"
                                           sizes="(max-width: 600px) 100vw, 33vw" />
                                </div>
                            @endif
                            @php $variantUrl = filled($variant->slug) ? \App\Support\Url::variant($product->slug, $variant->slug) : null; @endphp
                            <h3>@if ($variantUrl)<a href="{{ $variantUrl }}">{{ $variant->name }}</a>@else{{ $variant->name }}@endif</h3>
                            @if (filled($variant->note))<p class="variant-note">{{ $variant->note }}</p>@endif
                            <dl class="variant-prices">
                                <div class="is-list">
                                    <dt>Giá niêm yết</dt>
                                    <dd>{{ catalog_money($variant->price) ?: 'Đang cập nhật' }}</dd>
                                </div>
                                @if ($estimate)
                                    <div>
                                        {{-- Font NobelVnu không có ký tự ≈ → ghi "tạm tính" bằng chữ. --}}
                                        <dt>Lăn bánh {{ $estimate['region'] }} (tạm tính)</dt>
                                        <dd>{{ catalog_money_short($estimate['total']) }}</dd>
                                    </div>
                                @endif
                            </dl>
                            {{-- Gửi kèm phiên bản → lead ghi đúng mẫu khách chọn. --}}
                            <a class="button" href="{{ route('quote', ['xe' => $product->slug, 'phien-ban' => $variant->id]) }}"
                               data-quote data-product="{{ $product->id }}"
                               data-variant="{{ $variant->id }}" data-variant-name="{{ $variant->name }}">Nhận báo giá chi tiết</a>
                            @if ($variantUrl)<a class="variant-more" href="{{ $variantUrl }}">Giá lăn bánh & thông số {{ $variant->name }} ↗</a>@endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ══ 2 · Màu sắc & xoay 360° ══ --}}
    @include('frontend.partials.vehicle-studio')

    {{-- ══ 3 · Điểm nổi bật → các mục admin (ngoại thất, nội thất, vận hành…) ══ --}}
    @if ($highlights->isNotEmpty())
        <div class="container spec-strip" id="overview">
            @foreach ($highlights as $item)
                <div>
                    <small>{{ mb_strtoupper($item['label'] ?? '') }}</small>
                    <strong>{{ $item['value'] }}{{ filled($item['unit'] ?? null) ? ' '.$item['unit'] : '' }}</strong>
                </div>
            @endforeach
        </div>
    @else
        <span id="overview"></span>
    @endif

    @include('frontend.partials.sections', ['sections' => $sections])

    {{-- ══ 4 · Thông số kỹ thuật ══ --}}
    @if (filled($product->specs))
        <section class="container section" id="specs">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">THÔNG TIN SỞ HỮU</div>
                    <h2>Tìm hiểu kỹ hơn.</h2>
                </div>
            </div>
            <div class="accordion">
                @foreach ($product->specs as $group)
                    <details @if ($loop->first) open @endif>
                        <summary>{{ $group['group'] ?? 'Thông số' }}</summary>
                        <div class="table-wrap">
                            <table class="data-table">
                                <tbody>
                                @foreach ($group['rows'] ?? [] as $row)
                                    <tr>
                                        <td>{{ $row['label'] ?? '' }}</td>
                                        <td>{{ $row['value'] ?? '' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endforeach
            </div>
            @if (filled($product->spec_notes))
                @foreach ((array) $product->spec_notes as $note)
                    <div class="notice">
                        @if (filled($note['label'] ?? null))<strong class="notice__label">{{ $note['label'] }}</strong>@endif
                        {{ is_array($note) ? ($note['body'] ?? '') : $note }}
                    </div>
                @endforeach
            @endif
        </section>
    @endif

    {{-- ══ Form thu lead cuối trang (khoá khai ở config product_forms) ══ --}}
    @foreach ($forms as $form)
        <section class="section stone">
            <div class="container lead-layout">
                <div class="lead-copy">
                    <div class="eyebrow">MỘT BƯỚC ĐẾN GẦN HƠN</div>
                    <h2>{{ $product->name }}.<br>Dành riêng cho bạn.</h2>
                    @if (filled($form->description))<p>{{ $form->description }}</p>@endif
                    <div class="actions">
                        <a class="text-link" href="{{ route('booking') }}">Đăng ký lái thử</a>
                    </div>
                </div>

                @include('frontend.partials.lead-form', [
                    'formKey'     => $form->key,
                    'submitLabel' => $form->name,
                    'selected'    => $product->id,
                    'instance'    => 'cuoi-trang',
                ])
            </div>
        </section>
    @endforeach

    {{-- ══ Xe liên quan ══ --}}
    @php
        $related = \App\Support\Catalog::query('product')->published()
            ->where('id', '!=', $product->id)->with('category')->orderBy('sort')->take(3)->get();
    @endphp
    @if ($related->isNotEmpty())
        <section class="container section">
            <div class="section-heading">
                <h2>Tiếp tục khám phá.</h2>
                <a class="text-link" href="{{ route('products.index') }}">Tất cả dòng xe</a>
            </div>
            <div class="model-grid">
                @foreach ($related as $item)
                    @include('frontend.partials.product-card', ['product' => $item])
                @endforeach
            </div>
        </section>
    @endif
@endsection
