{{--
    Header dùng chung: logo đại lý, mega menu dòng xe, CTA, menu ngăn kéo mobile.

    $overlay = true thì header trong suốt nằm đè lên hero và dùng logo bản
    trắng; ngược lại nền trắng, logo bản đen.

    Menu ngăn kéo là <details> thuần HTML — không có JS. Nhãn đổi MENU ☰ ↔
    ĐÓNG ✕ bằng CSS `.mobile-menu[open]`.

    Mega menu lấy xe và danh mục thật từ DB. Các link còn lại trỏ vào trang
    tĩnh theo slug cố định — đổi slug thì sửa ở đây.
--}}
@php
    $overlay = $overlay ?? false;

    // Mega menu lấy xe thật. Cache trong request để mọi trang chỉ truy vấn
    // một lần dù header render ở layout.
    $navModels = once(fn () => \App\Support\Catalog::query('product')
        ->published()->with('category')->orderBy('sort')->take(8)->get());

    $navCategories = once(fn () => \App\Support\Catalog::query('category')
        ->orderBy('sort')->get());

    $spotlight = $navModels->first();
@endphp
<header class="site-header {{ $overlay ? 'over-hero' : '' }}">
    <a class="brand" href="{{ route('home') }}" aria-label="{{ catalog_setting('site_name', 'Lexus Thăng Long') }} — Trang chủ">
        <img class="brand-logo"
             src="{{ asset('assets/logo-ltl-'.($overlay ? 'white' : 'black').'.webp') }}"
             alt="{{ catalog_setting('site_name', 'Lexus Thăng Long') }}"
             width="800" height="68" decoding="async">
    </a>

    <nav class="desktop-nav" aria-label="Điều hướng chính">
        <details>
            <summary>Dòng xe</summary>
            <div class="mega">
                <div>
                    <span class="eyebrow">Bộ sưu tập Lexus</span>
                    @foreach ($navCategories as $c)
                        <a href="{{ route('categories.show', $c->slug) }}">{{ $c->name }}</a>
                    @endforeach
                    <a href="{{ route('products.index') }}">Tất cả dòng xe ↗</a>
                </div>
                <div>
                    @foreach ($navModels as $m)
                        <a href="{{ route('products.show', $m->slug) }}">{{ $m->name }}
                            @if ($m->category)<small>— {{ $m->category->name }}</small>@endif</a>
                    @endforeach
                </div>
                @if ($spotlight)
                    <div>
                        <img src="{{ catalog_image(data_get($spotlight->hero, 'src')) ?: asset('assets/rx.webp') }}"
                             alt="{{ $spotlight->name }}"
                             width="1600" height="1067" loading="lazy" decoding="async">
                        <a href="{{ route('products.show', $spotlight->slug) }}">Khám phá {{ $spotlight->name }} ↗</a>
                    </div>
                @endif
            </div>
        </details>
        <a href="{{ route('pages.show', 'bang-gia') }}">Mua xe</a>
        <a href="{{ route('pages.show', 'the-gioi-lexus') }}">Thế giới Lexus</a>
        <a href="{{ route('pages.show', 'dich-vu') }}">Dịch vụ</a>
        <a href="{{ route('home') }}#chuyen-vien">Người đồng hành</a>
    </nav>

    @php $headerPhone = catalog_setting('advisor_phone') ?: catalog_setting('hotline'); @endphp
    @if ($headerPhone)
        {{-- Hotline luôn thấy ở đầu trang; bấm là gọi (mobile) hoặc mở ứng dụng gọi. --}}
        <a class="header-phone" href="tel:{{ $headerPhone }}" aria-label="Gọi hotline {{ \App\Support\Phone::format($headerPhone) }}">
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/></svg>
            <span>{{ \App\Support\Phone::format($headerPhone) }}</span>
        </a>
    @endif
    <a class="header-cta" href="{{ route('booking') }}">ĐẶT LỊCH LÁI THỬ ↗</a>

    <details class="mobile-menu">
        <summary><span class="menu-open">MENU ☰</span><span class="menu-close">ĐÓNG ✕</span></summary>
        <nav aria-label="Điều hướng di động">
            @if ($headerPhone)<a href="tel:{{ $headerPhone }}">Hotline {{ \App\Support\Phone::format($headerPhone) }}</a>@endif
            <a href="{{ route('home') }}#chuyen-vien">Người đồng hành</a>
            <a href="{{ route('products.index') }}">Dòng xe Lexus</a>
            <a href="{{ route('pages.show', 'bang-gia') }}">Bảng giá &amp; mua xe</a>
            <a href="{{ route('pages.show', 'the-gioi-lexus') }}">Thế giới Lexus</a>
            <a href="{{ route('pages.show', 'dich-vu') }}">Dịch vụ &amp; chăm sóc</a>
            <a href="{{ route('posts.index') }}">Tin tức</a>
            <a href="{{ route('pages.show', 'showroom') }}">Showroom &amp; liên hệ</a>
            <a class="small" href="{{ route('booking') }}">Đặt lịch lái thử ↗</a>
            <a class="small" href="{{ route('quote') }}" data-quote>Nhận báo giá ↗</a>
        </nav>
    </details>
</header>
