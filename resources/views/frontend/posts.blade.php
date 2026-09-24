{{--
    Danh sách tin tức — cắt từ template/tin-tuc.html, đã nối dữ liệu thật.

    Dùng chung cho posts.index và post-categories.show ($category có giá trị).

    Bài mới nhất được tách ra làm "câu chuyện nổi bật" (ảnh trái, chữ phải),
    đúng như bản thiết kế — chỉ làm vậy ở trang đầu, sang trang 2 thì mọi bài
    về lưới cho khỏi lặp.

    Biến: $posts (paginator) · $categories · $category (chỉ route chuyên mục)
--}}
@php
    $indexCanonical = \App\Support\Url::paginated($canonical ?? request()->url(), $posts->currentPage());
    $listName = ($category ?? null)?->name ?? 'Tin tức & tư vấn mua xe Lexus';
    $listDesc = ($category ?? null)
        ? $category->name.' — bài viết của chuyên viên tư vấn Lexus Thăng Long, Hà Nội: giá xe, so sánh phiên bản, kinh nghiệm mua xe Lexus.'
        : 'Bảng giá, giá lăn bánh, so sánh phiên bản và kinh nghiệm chọn xe Lexus tại Hà Nội — cập nhật bởi chuyên viên tư vấn Lexus Thăng Long.';
@endphp
@extends('frontend.layout', [
    'title'       => $listName.' | '.catalog_setting('site_name', config('app.name')),
    'description' => $listDesc,
    'canonical'   => $indexCanonical,
    'prev'        => $posts->previousPageUrl(),
    'next'        => $posts->nextPageUrl(),
    'jsonld'      => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::forCollection($listName, $indexCanonical,
            collect($posts->items())->map(fn ($p) => ['name' => $p->title, 'url' => route('posts.show', $p->slug)])->all(),
            $listDesc),
        \App\Support\JsonLd::organization(),
    ),
])

@section('content')
    @php
        $category = $category ?? null;
        $items    = collect($posts->items());

        // Chỉ trang đầu mới có bài nổi bật.
        $featured = $posts->currentPage() === 1 ? $items->first() : null;
        $rest     = $featured ? $items->slice(1) : $items;
    @endphp

    @include('frontend.partials.page-intro', [
        'title'   => $category?->name ?? 'Những câu chuyện đáng dành thời gian.',
        'eyebrow' => 'LEXUS JOURNAL',
        'text'    => 'Thiết kế, con người và những trải nghiệm làm nên một góc nhìn Lexus.',
        'crumbs'  => $category ? ['Tin tức' => route('posts.index')] : [],
    ])

    @if ($categories->isNotEmpty())
        <div class="container">
            <div class="filters" aria-label="Chuyên mục">
                <a class="{{ $category ? '' : 'active' }}" href="{{ route('posts.index') }}">Tất cả</a>
                @foreach ($categories as $item)
                    <a class="{{ $category?->is($item) ? 'active' : '' }}"
                       href="{{ route('post-categories.show', $item->slug) }}">{{ $item->name }}</a>
                @endforeach
            </div>
        </div>
    @endif

    @if ($posts->isEmpty())
        <section class="container section">
            <p>Chưa có bài viết nào được đăng. Vui lòng quay lại sau.</p>
        </section>
    @else
        @if ($featured)
            <section class="container section">
                <div class="editorial-grid" style="align-items:center">
                    <a href="{{ route('posts.show', $featured->slug) }}">
                        {{-- Ảnh đầu trang = LCP: tải ngay, ưu tiên cao, có srcset. --}}
                        @if (catalog_image($featured->cover))
                            <x-img :src="$featured->cover" :alt="$featured->title" :eager="true"
                                   sizes="(max-width: 600px) 100vw, 50vw" />
                        @else
                            <img src="{{ asset('assets/co-so/mat-tien.webp') }}" alt="{{ $featured->title }}"
                                 width="1600" height="1067" fetchpriority="high" decoding="async">
                        @endif
                    </a>
                    <div>
                        <div class="eyebrow">CÂU CHUYỆN NỔI BẬT</div>
                        <h2>{{ $featured->title }}</h2>
                        @if (filled($featured->excerpt))
                            <p style="margin-top:22px">{{ $featured->excerpt }}</p>
                        @endif
                        <a class="text-link" href="{{ route('posts.show', $featured->slug) }}">Đọc câu chuyện</a>
                    </div>
                </div>
            </section>
        @endif

        @if ($rest->isNotEmpty())
            <section class="container section" style="padding-top:0">
                <div class="news-grid">
                    @foreach ($rest as $post)
                        @include('frontend.partials.post-card', ['post' => $post])
                    @endforeach
                </div>

                @include('frontend.partials.pagination', ['paginator' => $posts])
            </section>
        @endif
    @endif

    @include('frontend.partials.conversion')
@endsection
