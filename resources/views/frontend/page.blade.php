{{--
    Trang tĩnh — /{slug}. Dùng cho bảng giá, báo giá, ưu đãi, tài chính,
    dịch vụ, showroom, liên hệ, FAQ, quyền riêng tư, điều khoản.

    Bảng `pages` chỉ có slug · title · sections · seo · status — KHÔNG có cột
    body/cover. Toàn bộ nội dung nằm trong `sections`, mỗi mục render bằng
    view riêng trong partials/section/ theo kiểu của nó.

    Mục ảnh đứng đầu (một ảnh, không tiêu đề) được tách ra làm ảnh bìa tràn
    ngang, giống cách trang bài viết của bản thiết kế đặt ảnh ngay dưới dải
    mở đầu — nhét nó vào dòng mục thường thì ảnh bị bóp méo.
--}}
@extends('frontend.layout', [
    'title'       => data_get($page->seo, 'title', $page->title.' | '.catalog_setting('site_name', config('app.name'))),
    'description' => data_get($page->seo, 'description'),
    'canonical'   => \App\Support\Url::absolute('page', $page->slug),
    'jsonld'      => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::forPage($page),
        \App\Support\JsonLd::forBreadcrumb([
            ['name' => 'Trang chủ', 'url' => route('home')],
            ['name' => $page->title, 'url' => \App\Support\Url::absolute('page', $page->slug)],
        ]),
        \App\Support\JsonLd::forFaq($sections, \App\Support\Url::absolute('page', $page->slug)),
        \App\Support\JsonLd::organization(),
    ),
])

@section('content')
    @php
        $sections = collect($sections);
        $first    = $sections->first();
        $cover    = null;

        if (($first['type'] ?? null) === 'media'
            && count($first['items'] ?? []) === 1
            && blank($first['title'] ?? null)) {
            $cover    = $first['items'][0];
            $sections = $sections->slice(1)->values();
        }
    @endphp

    @include('frontend.partials.page-intro', [
        'title'   => $page->title,
        'eyebrow' => data_get($page->seo, 'eyebrow'),
        'text'    => data_get($page->seo, 'excerpt'),
    ])

    @if ($cover && ($src = catalog_image($cover['image'] ?? null)))
        @php
            $coverSize   = \App\Support\Media::dimensions($cover['image']);
            $coverSrcset = \App\Support\Media::srcset($cover['image']);
        @endphp
        <img src="{{ $src }}" alt="{{ $cover['label'] ?? $page->title }}"
             @if ($coverSrcset) srcset="{{ $coverSrcset }}" sizes="100vw" @endif
             width="{{ $coverSize['w'] ?? 1600 }}" height="{{ $coverSize['h'] ?? 1067 }}" fetchpriority="high" decoding="async">
    @endif

    @if ($page->slug === 'bang-gia')
        @include('frontend.partials.price-table')
    @endif

    @includeWhen($sections->isNotEmpty(), 'frontend.partials.sections', [
        'sections' => $sections,
        'numbered' => false,
        // Không có ảnh bìa và không có bảng giá → ảnh của mục đầu nằm ngay
        // màn hình đầu (thường là LCP): tải ngay, không lazy.
        'eagerFirst' => ! $cover && $page->slug !== 'bang-gia',
    ])

    @if (in_array($page->slug, ['showroom', 'lien-he'], true))
        <section class="container section">
            <div class="section-heading"><h2>Hẹn gặp bạn tại showroom.</h2></div>
            @include('frontend.partials.contact-details')
        </section>
    @endif

    @include('frontend.partials.conversion')
@endsection
