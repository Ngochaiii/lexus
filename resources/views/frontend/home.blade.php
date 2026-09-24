{{--
    Trang chủ — dựng lại theo bản thiết kế (template/index.html), đã nối dữ liệu.

    Thứ tự khối giữ đúng bản gốc:
      01 hero toàn màn hình — ảnh và chữ sửa ở admin (Banner)
      02 dải chuyên viên tư vấn (advisor-ribbon)
      03 bộ sưu tập — lọc bằng CSS, 3 xe đầu
      04 hồ sơ chuyên viên (#chuyen-vien)
      05–09 khối biên tập từ trang tĩnh `trang-chu`
            (story · lối tắt · Omotenashi · Takumi · đặc quyền)
      10 khoảnh khắc đồng hành (#khoanh-khac)
      11 tin tức
      12 băng chuyển đổi

    Lấy từ DB: xe (03), tin tức (11), chuyên viên (02, 04), và các khối
    biên tập 05–09 lấy từ `sections` của trang tĩnh slug `trang-chu` — sửa
    được trong admin ở Trang → Trang chủ.

    Chỉ còn khối 10 (khoảnh khắc đồng hành) là chữ cố định trong view.

    Ảnh chuyên viên và khoảnh khắc bàn giao nằm ở public/assets/personal/,
    ghi đè được bằng Cài đặt → advisor_image.

    Biến từ HomeController: $products · $posts · $homeSections · $banners
--}}
@extends('frontend.layout', [
    'overlay' => true,
    'popup'   => true,   // popup báo giá tự bật — cấu hình ở catalog.frontend.popup
    // Tiêu đề trang chủ mang từ khoá địa phương ("đại lý Lexus Hà Nội") —
    // sửa ở Cài đặt → seo_home_title; bỏ trống thì chỉ còn tên đại lý.
    'title'   => catalog_setting('seo_home_title') ?: catalog_setting('site_name', config('app.name')),
    'description' => catalog_setting('site_description'),
    'jsonld'  => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::organization(),
        \App\Support\JsonLd::website(),
    ),
])

@php
    $advisor   = catalog_setting('advisor_name');
    $advisorRo = catalog_setting('advisor_role', 'Chuyên viên tư vấn');
    $portrait  = catalog_image(catalog_setting('advisor_image'));
    $hasAdvisor = filled($advisor) && ! catalog_setting('advisor_off');

    $heroProduct = $products->first();

    // Ảnh banner tính ở đây (không phải trong @section) để preload đúng ảnh
    // đang hiển thị — preload ảnh khác là tải thừa cả trăm KB mỗi lượt vào.
    $banner  = $banners->first();
    $bImage  = catalog_image($banner?->image) ?: asset('assets/co-so/khu-trung-bay.webp');
    $bMobile = catalog_image($banner?->image_mobile);
    $bSrcset = \App\Support\Media::srcset($banner?->image);
@endphp

@push('preload')
    <link rel="preload" as="image" fetchpriority="high" href="{{ $bImage }}"
          @if ($bSrcset) imagesrcset="{{ $bSrcset }}" imagesizes="100vw" @endif>
@endpush

@section('content')

    {{-- ══ 01 · Hero ══ --}}
    {{--
        Banner sửa trong admin ở Banner → thêm/sửa bản ghi. Mỗi ô bỏ trống
        thì lùi về đúng nội dung của bản thiết kế, nên xoá sạch bảng banner
        cũng không làm vỡ trang chủ.

        `title` và `subtitle` cho xuống dòng bằng Enter — bản thiết kế ngắt
        dòng giữa câu, nên nl2br chứ không để trình duyệt tự ngắt.
    --}}
    @php
        $bEyebrow = $banner?->eyebrow  ?: 'THE LEXUS RX · MỘT CHUẨN MỰC MỚI';
        $bTitle   = $banner?->title    ?: "Dấu ấn riêng.\nHành trình khác biệt.";
        $bText    = $banner?->subtitle ?: "Sự tĩnh tại trong từng chuyển động.\nTinh hoa Lexus, dành riêng cho bạn.";


        // Nút chính: lấy từ banner, không có thì trỏ vào xe đầu danh sách.
        $bCtaUrl   = $banner?->cta_url;
        $bCtaLabel = $banner?->cta_label;

        if (blank($bCtaUrl) && $heroProduct) {
            $bCtaUrl   = route('products.show', $heroProduct->slug);
            $bCtaLabel = $bCtaLabel ?: 'Khám phá '.$heroProduct->name;
        }
    @endphp
    <section class="hero">
        <picture>
            @if ($bMobile)<source media="(max-width: 600px)" srcset="{{ $bMobile }}">@endif
            <img class="hero-media" src="{{ $bImage }}" @if ($bSrcset) srcset="{{ $bSrcset }}" sizes="100vw" @endif
                 alt="{{ catalog_setting('site_name', 'Lexus Thăng Long') }} — khu trưng bày xe Lexus tại Cầu Giấy, Hà Nội"
                 width="1600" height="1067" fetchpriority="high" loading="eager" decoding="async">
        </picture>
        <div class="container">
            <div class="hero-copy">
                @if (filled($bEyebrow))<div class="eyebrow">{{ $bEyebrow }}</div>@endif
                <h1>{!! nl2br(e($bTitle)) !!}</h1>
                @if (filled($bText))<p>{!! nl2br(e($bText)) !!}</p>@endif
                <div class="actions">
                    @if (filled($bCtaUrl))
                        <a class="button light" href="{{ $bCtaUrl }}">{{ $bCtaLabel ?: 'Khám phá dòng xe' }}</a>
                    @else
                        <a class="button light" href="{{ route('products.index') }}">Khám phá dòng xe</a>
                    @endif
                    {{-- Link phụ luôn là hành động của site, không thuộc nội dung banner. --}}
                    <a class="text-link" href="{{ route('booking') }}">{{ catalog_label('cta.test_drive') }}</a>
                </div>
            </div>
        </div>
        <div class="hero-bottom">
            @php $modelCount = \App\Support\Catalog::query('product')->published()->count(); @endphp
            <span class="hero-line">{{ mb_strtoupper(catalog_setting('site_name', 'Lexus Thăng Long')) }}@if ($modelCount) &nbsp; / &nbsp; {{ $modelCount }} DÒNG XE @endif</span>
            <span class="hero-tag">{{ mb_strtoupper((string) catalog_setting('opening_hours', '')) }}</span>
            <a href="#collection">CUỘN ĐỂ KHÁM PHÁ &nbsp; ↓</a>
        </div>
    </section>

    {{-- ══ 02 · Dải chuyên viên ══ --}}
    @if ($hasAdvisor)
        <aside class="advisor-ribbon">
            <div class="container advisor-ribbon-inner">
                <a class="advisor-identity" href="#chuyen-vien">
                    <img class="advisor-avatar" src="{{ $portrait ?: asset('assets/personal/portrait-960.webp') }}"
                         @unless ($portrait)
                             srcset="{{ asset('assets/personal/portrait-320.webp') }} 320w,
                                     {{ asset('assets/personal/portrait-640.webp') }} 640w,
                                     {{ asset('assets/personal/portrait-960.webp') }} 960w"
                         @endunless
                         sizes="56px" width="960" height="960"
                         alt="Chân dung {{ $advisor }}" loading="lazy" decoding="async">
                    <span><strong>{{ $advisor }}</strong><span>{{ $advisorRo }}</span></span>
                </a>
                <p>Một người đồng hành cho hành trình Lexus của bạn.</p>
                <a class="text-link" href="#chuyen-vien">Gặp người tư vấn</a>
            </div>
        </aside>
    @endif

    {{-- ══ 03 · Bộ sưu tập — từng PHIÊN BẢN ══ --}}
    {{--
        Hiện đủ mọi phiên bản (RX 350h Premium, RX 350h Luxury…) chứ không
        gộp theo dòng xe: khách bấm "Nhận báo giá" ngay trên phiên bản mình
        thích → lead ghi đúng mẫu đó (leads.product_variant_id), admin thống
        kê được mẫu nào được hỏi nhiều. Lọc SUV/Sedan/MPV/Hybrid bằng CSS.
    --}}
    @php
        $homeVariants = \App\Models\ProductVariant::query()
            ->whereHas('product', fn ($q) => $q->published())
            ->with('product.category')
            ->get()
            // Theo thứ tự dòng xe trong admin, rồi thứ tự phiên bản.
            ->sortBy(fn ($v) => sprintf('%05d-%05d', $v->product->sort, $v->sort))
            ->values();
        $homeFilters = $homeVariants->pluck('product.category')->filter()->unique('id')->sortBy('sort');
        $hasHybrid   = $homeVariants->contains(fn ($v) => preg_match('/\d{3}h\b/i', $v->name));
    @endphp
    @if ($homeVariants->isNotEmpty())
        <section class="container model-discovery" id="collection">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">Bộ sưu tập Lexus · {{ $homeVariants->count() }} phiên bản</div>
                    <h2>Chọn đúng phiên bản của bạn.</h2>
                </div>
                <a class="text-link" href="{{ route('pages.show', 'bang-gia') }}">Bảng giá chi tiết</a>
            </div>

            <div class="catalog">
                <div class="filters" aria-label="Lọc phiên bản">
                    <input class="filter-input" type="radio" name="category" id="all" checked>
                    <label for="all">Tất cả</label>
                    @foreach ($homeFilters as $item)
                        <input class="filter-input" type="radio" name="category" id="{{ $item->slug }}">
                        <label for="{{ $item->slug }}">{{ $item->name }}</label>
                    @endforeach
                    @if ($hasHybrid)
                        <input class="filter-input" type="radio" name="category" id="hybrid">
                        <label for="hybrid">Hybrid</label>
                    @endif
                </div>

                <div class="model-grid variant-home-grid">
                    @foreach ($homeVariants as $variant)
                        @include('frontend.partials.variant-card', ['variant' => $variant])
                    @endforeach
                </div>
            </div>

            <div class="section-foot">
                <small>Giá niêm yết đã gồm VAT, chưa gồm lệ phí trước bạ và phí đăng ký.</small>
                <a class="text-link" href="{{ route('booking') }}">Đăng ký lái thử</a>
            </div>
        </section>
    @endif

    {{-- ══ 04 · Hồ sơ chuyên viên ══ --}}
    @if ($hasAdvisor)
        <section class="advisor-section stone" id="chuyen-vien" aria-labelledby="advisor-title">
            <div class="container advisor-layout">
                <div class="advisor-copy">
                    <p class="eyebrow">NGƯỜI ĐỒNG HÀNH CỦA BẠN</p>
                    <h2 id="advisor-title">Chọn một chiếc xe.<br>Gặp một người<br> <span>thấu hiểu.</span></h2>

                    <div class="advisor-name">
                        <strong>{{ $advisor }}</strong><span>{{ $advisorRo }}</span>
                    </div>

                    <p class="advisor-intro">{{ catalog_setting('advisor_text')
                        ?: 'Một chiếc Lexus phù hợp bắt đầu từ việc hiểu điều bạn cần. Tôi ở đây để cùng bạn tìm hiểu từng lựa chọn, chuẩn bị buổi lái thử và chăm chút cho khoảnh khắc nhận xe.' }}</p>

                    @php
                        // Cài đặt advisor_points: mỗi dòng "Tiêu đề|Mô tả".
                        // Bỏ trống thì dùng ba cam kết của bản thiết kế.
                        $points = collect(preg_split('/\R/', (string) catalog_setting('advisor_points')))
                            ->filter()
                            ->map(fn ($line) => trim(explode('|', $line)[1] ?? explode('|', $line)[0]))
                            ->values();

                        if ($points->isEmpty()) {
                            $points = collect([
                                'Lắng nghe nhu cầu sử dụng của bạn',
                                'Cùng tìm hiểu phiên bản & chi phí',
                                'Đồng hành từ lái thử đến nhận xe',
                            ]);
                        }
                    @endphp
                    <div class="advisor-promises">
                        @foreach ($points as $point)
                            <div>
                                <span>{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span>
                                <p>{{ $point }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="actions">
                        <a class="button" href="{{ route('pages.show', 'lien-he') }}">Trao đổi cùng tôi</a>
                        <a class="text-link" href="#khoanh-khac">Những lần đồng hành</a>
                    </div>
                </div>

                <figure class="advisor-portrait">
                    <img src="{{ $portrait ?: asset('assets/personal/portrait-960.webp') }}"
                         @unless ($portrait)
                             srcset="{{ asset('assets/personal/portrait-320.webp') }} 320w,
                                     {{ asset('assets/personal/portrait-640.webp') }} 640w,
                                     {{ asset('assets/personal/portrait-960.webp') }} 960w"
                         @endunless
                         sizes="(max-width: 600px) 100vw, 50vw" width="960" height="960"
                         alt="Chân dung {{ $advisor }} trong không gian {{ catalog_setting('site_name') }}"
                         loading="lazy" decoding="async">
                    <figcaption>
                        <span>PERSONAL CONSULTATION</span>
                        <p>Sự tận tâm bắt đầu<br>từ một cuộc trò chuyện.</p>
                    </figcaption>
                </figure>
            </div>
        </section>
    @endif

    {{-- ══ 05–09 · Khối biên tập, sửa trong admin ══ --}}
    {{--
        Năm khối giữa trang (story, lối tắt, Omotenashi, Takumi, đặc quyền)
        lấy từ `sections` của trang tĩnh slug `trang-chu`. Biên tập viên vào
        Trang → Trang chủ để sửa chữ, ảnh và link, không phải đụng view.

        Bố cục tương ứng trong admin:
          Tràn màn hình (bleed)        → khối story
          Lưới ô bấm đánh số           → hành trình sở hữu
          Chữ/ảnh (split · split-alt)  → Omotenashi · Takumi
          2 cột (cols-2)               → hai thẻ đặc quyền

        Không đánh số 01/ 02/ trên eyebrow vì bản thiết kế trang chủ không có.
    --}}
    @include('frontend.partials.sections', [
        'sections' => $homeSections,
        'numbered' => false,
    ])

    {{-- ══ 10 · Khoảnh khắc đồng hành ══ --}}
    @if ($hasAdvisor)
        @php
            // Ba ảnh của bản thiết kế. Bước sau có thể chuyển sang Cài đặt
            // giống nhóm showroom_image_* của core.
            $moments = [
                ['key' => 'handover',    'w' => 1200, 'h' => 900,  'index' => '01 / NGÀY BÀN GIAO',   'title' => 'Một khởi đầu đáng nhớ.',            'alt' => 'Chuyên viên và khách hàng cùng cầm hộp bàn giao trước xe Lexus', 'featured' => true],
                ['key' => 'celebration', 'w' => 1200, 'h' => 900,  'index' => '02 / NIỀM VUI GẶP GỠ', 'title' => 'Cùng lưu lại niềm vui.',            'alt' => 'Khoảnh khắc cùng khách hàng bên xe Lexus và hoa tại showroom'],
                ['key' => 'delivery',    'w' => 1200, 'h' => 1609, 'index' => '03 / HÀNH TRÌNH MỚI',  'title' => 'Sẵn sàng cho chặng đường mới.',     'alt' => 'Chuyên viên và khách hàng cầm hoa bên chiếc Lexus màu trắng'],
            ];
        @endphp
        <section class="container section personal-moments" id="khoanh-khac" aria-labelledby="moments-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">NHỮNG KHOẢNH KHẮC ĐỒNG HÀNH</p>
                    <h2 id="moments-title">Niềm vui ngày nhận xe.<br>Dấu ấn của một hành trình.</h2>
                </div>
                <p>Từ cuộc gặp gỡ đầu tiên đến khoảnh khắc bàn giao. Những hình ảnh lưu lại sự kết nối giữa
                    người tư vấn, khách hàng và chiếc xe được lựa chọn.</p>
            </div>

            <div class="moments-grid">
                @foreach ($moments as $moment)
                    <figure class="moment {{ ($moment['featured'] ?? false) ? 'moment-featured' : '' }}">
                        <img src="{{ asset('assets/personal/'.$moment['key'].'-1200.webp') }}"
                             srcset="{{ asset('assets/personal/'.$moment['key'].'-640.webp') }} 640w,
                                     {{ asset('assets/personal/'.$moment['key'].'-1200.webp') }} 1200w"
                             sizes="{{ ($moment['featured'] ?? false)
                                 ? '(max-width: 600px) calc(100vw - 40px), (max-width: 1000px) 90vw, 46vw'
                                 : '(max-width: 600px) calc(100vw - 40px), 30vw' }}"
                             width="{{ $moment['w'] }}" height="{{ $moment['h'] }}"
                             alt="{{ $moment['alt'] }}" loading="lazy" decoding="async">
                        <figcaption>
                            <span class="moment-index">{{ $moment['index'] }}</span>
                            <h3>{{ $moment['title'] }}</h3>
                        </figcaption>
                    </figure>
                @endforeach
            </div>

            <div class="moments-invite">
                <p>Chiếc Lexus tiếp theo sẽ kể câu chuyện của bạn.</p>
                <a class="text-link" href="{{ route('booking') }}">Hẹn một buổi lái thử</a>
            </div>
        </section>
    @endif

    {{-- ══ 11 · Tin tức ══ --}}
    @if ($posts->isNotEmpty())
        <section class="container section" style="padding-top:0">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">Góc nhìn Lexus</div>
                    <h2>Những câu chuyện truyền cảm hứng.</h2>
                </div>
                <a class="text-link" href="{{ route('posts.index') }}">Tất cả câu chuyện</a>
            </div>
            <div class="news-grid">
                @foreach ($posts as $post)
                    @include('frontend.partials.post-card', ['post' => $post])
                @endforeach
            </div>
        </section>
    @endif

    {{-- ══ 12 · Băng chuyển đổi ══ --}}
    @include('frontend.partials.conversion')
@endsection
