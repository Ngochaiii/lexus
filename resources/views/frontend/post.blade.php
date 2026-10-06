{{--
    Bài viết — cắt từ template/bai-viet.html, đã nối dữ liệu thật.

    Thân bài do người nhập dựng bằng `sections` (giống trang xe và trang
    tĩnh), không phải một cột HTML duy nhất. Mục kiểu `text` render trong
    khối .article rộng 820px, đúng đặc tả mục 16 (content width 720–820px).

    Desktop: 2 cột — thân bài + cột phải dính theo (chuyên viên, giá xe, mục
    lục; partials/post-aside). Mobile: cột phải xuống sau bài.

    Biến từ PostController: $post · $sections · $toc · $car · $related
--}}
@extends('frontend.layout', [
    'title'       => data_get($post->seo, 'title', $post->title.\App\Support\SeoText::titleSuffix()),
    'description' => data_get($post->seo, 'description') ?: $post->excerpt,
    'canonical'   => \App\Support\Url::absolute('post', $post->slug),
    'ogType'      => 'article',
    'ogImage'     => catalog_image($post->cover),
    'jsonld'      => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::forPost($post),
        \App\Support\JsonLd::forBreadcrumb([
            ['name' => 'Trang chủ', 'url' => route('home')],
            ['name' => 'Tin tức', 'url' => route('posts.index')],
            ['name' => $post->title, 'url' => \App\Support\Url::absolute('post', $post->slug)],
        ]),
        \App\Support\JsonLd::forFaq($sections, \App\Support\Url::absolute('post', $post->slug)),
        \App\Support\JsonLd::organization(),
    ),
])

@section('content')
    @include('frontend.partials.page-intro', [
        'title'   => $post->title,
        'eyebrow' => trim(mb_strtoupper($post->category?->name ?? 'Lexus Journal')
            .($post->published_at ? ' · '.$post->published_at->format('d.m.Y') : '')),
        'text'    => $post->excerpt,
        'crumbs'  => ['Tin tức' => route('posts.index')],
    ])

    {{--
        Ảnh bìa theo ô "Bề rộng ảnh bìa" trong admin (cover_width):
          narrow (mặc định) — trong cột bài, cạnh cột phải · wide — khung nội
          dung · full — tràn màn hình (hai kiểu này nằm trên, trước 2 cột).
        Giữ nguyên tỉ lệ ảnh, không cắt: ảnh bìa hay là banner có chữ.
        Là LCP của trang nên không lazy; `sizes` khớp bề rộng thật để mobile
        và cột hẹp không tải bản 1536px.
    --}}
    @php
        $cover       = catalog_image($post->cover);
        $coverWidth  = in_array($post->cover_width, ['wide', 'full'], true) ? $post->cover_width : 'narrow';
        $author      = catalog_setting('advisor_name');
    @endphp

    @if ($cover && $coverWidth !== 'narrow')
        @include('frontend.partials.post-cover')
    @endif

    <div class="container post-layout">
        <div class="post-main">
            @if ($cover && $coverWidth === 'narrow')
                @include('frontend.partials.post-cover')
            @endif

            {{-- Ai viết, cập nhật khi nào: người đọc và công cụ tìm kiếm/AI đều dựa
                 vào đây để đánh giá độ tin cậy của bài có số liệu (giá, phí). --}}
            <div class="post-byline">
                @if ($author)
                    <p>Người viết: <a href="{{ route('pages.show', 'lien-he') }}" rel="author">{{ $author }}</a>
                        — {{ catalog_setting('advisor_role', 'Chuyên viên tư vấn') }}</p>
                @endif
                <p>
                    @if ($post->published_at)Đăng <time datetime="{{ $post->published_at->toAtomString() }}">{{ $post->published_at->format('d/m/Y') }}</time>@endif
                    @if ($post->updated_at && $post->published_at && $post->updated_at->gt($post->published_at->copy()->addDay()))
                        · Cập nhật <time datetime="{{ $post->updated_at->toAtomString() }}">{{ $post->updated_at->format('d/m/Y') }}</time>
                    @endif
                </p>
            </div>

            @include('frontend.partials.sections', ['sections' => $sections, 'numbered' => false])
        </div>

        @include('frontend.partials.post-aside', ['car' => $car ?? null, 'toc' => $toc ?? []])
    </div>

    {{-- ══ Bài liên quan ══ --}}
    @if (filled($related))
        <section class="container section">
            <div class="section-heading">
                <h2>Tiếp tục đọc.</h2>
                <a class="text-link" href="{{ route('posts.index') }}">Tất cả câu chuyện</a>
            </div>
            <div class="news-grid">
                @foreach ($related as $item)
                    @include('frontend.partials.post-card', ['post' => $item])
                @endforeach
            </div>
        </section>
    @endif

    @include('frontend.partials.conversion')
@endsection
