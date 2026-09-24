{{--
    Danh sách dòng xe — cắt từ template/models.html, đã nối dữ liệu thật.

    Dùng chung cho hai route:
      products.index   → tất cả xe, có bộ lọc
      categories.show  → một danh mục, biến $category có giá trị

    Bộ lọc chạy hoàn toàn bằng CSS: radio ẩn + quy tắc
    `.catalog:has(#suv:checked) .model-card:not([data-category~=suv])` trong
    style.css. Nghĩa là `id` của radio PHẢI trùng slug danh mục, và style.css
    hiện chỉ khai sẵn cho suv · sedan · mpv · hybrid. Thêm danh mục slug khác
    thì phải thêm quy tắc CSS tương ứng — đây là cái giá của việc bỏ JS.

    Lọc bằng CSS chỉ ẩn/hiện thẻ ĐÃ render, nên chỉ đúng khi mọi xe nằm trên
    một trang. Có phân trang thì lùi về link danh mục thật.

    Biến: $products (paginator) · $categories · $category (chỉ route danh mục)
--}}
@php
    // Trang 2 trở đi phải tự trỏ canonical về chính nó, kèm rel prev/next.
    $indexCanonical = \App\Support\Url::paginated($canonical ?? request()->url(), $products->currentPage());

    // Tiêu đề/mô tả nêu đích danh các dòng xe + giá thấp nhất: đúng thứ người
    // tìm "xe Lexus SUV giá bao nhiêu" gõ, thay cho câu khẩu hiệu chung chung.
    $listName   = ($category ?? null) ? 'Xe Lexus '.$category->name : 'Các dòng xe Lexus';
    $listModels = collect($products->items())->pluck('name')->map(fn ($n) => trim(str_replace('Lexus', '', $n)))->filter();
    $listFrom   = collect($products->items())->pluck('price_from')->filter()->min();
    $listTitle  = ($category ?? null)
        ? $listName.': '.$listModels->join(', ').' — giá & phiên bản'
        : 'Giá xe Lexus 2026: '.$listModels->count().' dòng xe SUV, Sedan, MPV';
    $listDesc   = $listName.' tại Lexus Thăng Long, Hà Nội: '.$listModels->map(fn ($m) => 'Lexus '.$m)->join(', ')
        .($listFrom ? ' — giá từ '.catalog_money_short($listFrom) : '')
        .'. Xem giá từng phiên bản, màu xe và đăng ký lái thử.';
@endphp
@extends('frontend.layout', [
    'title'       => $listTitle.' | '.catalog_setting('site_name', config('app.name')),
    'description' => $listDesc,
    'canonical'   => $indexCanonical,
    'prev'        => $products->previousPageUrl(),
    'next'        => $products->nextPageUrl(),
    'jsonld'      => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::forCollection($listName, $indexCanonical,
            collect($products->items())->map(fn ($p) => ['name' => $p->name, 'url' => \App\Support\Url::absolute('product', $p->slug)])->all(),
            $listDesc),
        \App\Support\JsonLd::forBreadcrumb(array_values(array_filter([
            ['name' => 'Trang chủ', 'url' => route('home')],
            ['name' => 'Dòng xe', 'url' => route('products.index')],
            ($category ?? null) ? ['name' => $category->name, 'url' => $indexCanonical] : null,
        ]))),
        \App\Support\JsonLd::organization(),
    ),
])

@section('content')
    @php
        $category = $category ?? null;

        // Danh mục mà style.css đã có sẵn quy tắc lọc.
        $filters = $categories->whereIn('slug', ['suv', 'sedan', 'mpv', 'hybrid']);

        $showFilters = $filters->isNotEmpty() && ! $category && ! $products->hasPages();
    @endphp

    @include('frontend.partials.page-intro', [
        'title'   => $category?->name ?? 'Các dòng xe Lexus',
        'eyebrow' => 'THE LEXUS COLLECTION',
        'text'    => 'Mỗi thiết kế là một cá tính. Tìm chiếc Lexus đồng điệu với phong cách và hành trình của bạn.',
        'crumbs'  => $category ? ['Các dòng xe' => route('products.index')] : [],
    ])

    <section class="container section">
        {{-- Mốc neo cho link "SUV / Sedan / MPV" trên mega menu. --}}
        @foreach ($filters as $item)
            <span id="{{ $item->slug }}-section"></span>
        @endforeach

        <div class="catalog">
            @if ($showFilters)
                <div class="filters" aria-label="Lọc theo dòng xe">
                    <input class="filter-input" type="radio" name="category" id="all" checked>
                    <label for="all">Tất cả dòng xe</label>
                    @foreach ($filters as $item)
                        <input class="filter-input" type="radio" name="category" id="{{ $item->slug }}">
                        <label for="{{ $item->slug }}">{{ $item->name }}</label>
                    @endforeach
                </div>
            @elseif ($categories->isNotEmpty())
                {{-- Không lọc được bằng CSS thì lùi về link danh mục thật. --}}
                <div class="filters" aria-label="Danh mục xe">
                    <a class="{{ $category ? '' : 'active' }}" href="{{ route('products.index') }}">Tất cả dòng xe</a>
                    @foreach ($categories as $item)
                        <a class="{{ $category?->is($item) ? 'active' : '' }}"
                           href="{{ route('categories.show', $item->slug) }}">{{ $item->name }}</a>
                    @endforeach
                </div>
            @endif

            @if ($products->isEmpty())
                <p>Chưa có dòng xe nào được đăng. Vui lòng quay lại sau.</p>
            @else
                {{-- Tiêu đề ẩn cho trình đọc màn hình: h1 → h2 → h3 (thẻ xe) đúng thứ bậc. --}}
                <h2 class="studio-sr">{{ $listName }} — {{ $products->total() }} dòng xe</h2>
                <div class="model-grid">
                    @foreach ($products as $product)
                        @include('frontend.partials.product-card', ['product' => $product, 'eager' => $loop->first])
                    @endforeach
                </div>
            @endif

            <div class="section-foot">
                <small>Giá tham khảo, không phải báo giá chính thức.</small>
                <a class="text-link" href="{{ route('booking') }}">Đăng ký lái thử</a>
            </div>

            @include('frontend.partials.pagination', ['paginator' => $products])
        </div>
    </section>

    <section class="split stone">
        <div class="split-media">
            <img src="{{ asset('assets/co-so/sanh-trung-bay.webp') }}"
                 alt="Sảnh trưng bày xe Lexus hybrid tại Lexus Thăng Long, Cầu Giấy"
                 width="1335" height="893" loading="lazy" decoding="async">
        </div>
        <div class="split-copy">
            <div class="eyebrow">LEXUS ELECTRIFIED</div>
            <h2>Chuyển động hôm nay.<br>Cảm hứng ngày mai.</h2>
            <p>RX, NX, ES, LM và LS đều có bản hybrid — không cần cắm sạc, êm và tiết kiệm trong phố. Ghé showroom để lái thử và so sánh trực tiếp.</p>
            <a class="text-link" href="{{ route('pages.show', 'the-gioi-lexus') }}">Khám phá trải nghiệm</a>
        </div>
    </section>

    @include('frontend.partials.conversion')
@endsection
