{{--
    Khung chung của mọi trang khách xem — giao diện Lexus Thăng Long.

    Giữ ĐÚNG hợp đồng biến của layout core (cars-backend) để mọi controller
    sẵn có chạy không phải sửa. Chỉ phần markup và tài nguyên là của Lexus.

    Biến nhận vào (đều tuỳ chọn):
      $title · $description · $canonical · $ogImage · $ogType · $jsonld
      $robots · $prev · $next · $bodyClass

    Riêng của bản Lexus:
      $overlay   true → header trong suốt đè lên hero, dùng logo bản trắng
      $salesBar  true → thanh Báo giá / Gọi / Zalo dính đáy trên mobile
      $popup     true → popup báo giá tự bật (chỉ trang chủ truyền)

    CSS là file tĩnh public/assets/style.css — không Vite, không build.
    Font NobelVnu nhúng cục bộ trong CSS, không gọi Google Fonts.
--}}
@php
    $siteName = catalog_setting('site_name', config('app.name'));

    $pageTitle       = $title ?? $siteName;
    $pageDescription = $description ?? catalog_setting('site_description');
    // Google chỉ hiện ~160 ký tự. Dài hơn thì cắt ở cuối câu gần nhất (hoặc ở
    // ranh giới từ + "…") thay vì để Google tự cắt ngang chữ. og:description
    // vẫn giữ bản đầy đủ — Facebook/Zalo hiện được dài hơn.
    $metaDescription = \App\Support\SeoText::description($pageDescription);
    $pageCanonical   = $canonical ?? request()->url();
    $pageType        = $ogType ?? 'website';
    $pageRobots      = $robots ?? 'index,follow,max-image-preview:large';
    $pageImage       = \App\Support\Url::asset(
        ($ogImage ?? null) ?: catalog_setting('social_image') ?: catalog_setting('logo')
    );

    $overlay  = $overlay  ?? false;
    $salesBar = $salesBar ?? false;
    $popup    = $popup    ?? false;

    // Nhúng ?v=filemtime để cache vĩnh viễn vẫn an toàn khi sửa CSS.
    $cssVersion = @filemtime(public_path('assets/style.css')) ?: null;
    $jsVersion  = @filemtime(public_path('assets/lead.js')) ?: null;
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $pageTitle }}</title>
    <meta name="theme-color" content="#151617">

    @if (filled($pageDescription))
        <meta name="description" content="{{ $metaDescription }}">
    @endif
    <meta name="robots" content="{{ $pageRobots }}">
    <link rel="canonical" href="{{ $pageCanonical }}">
    @if (filled($prev ?? null))<link rel="prev" href="{{ $prev }}">@endif
    @if (filled($next ?? null))<link rel="next" href="{{ $next }}">@endif

    <meta property="og:locale" content="vi_VN">
    <meta property="og:type" content="{{ $pageType }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:url" content="{{ $pageCanonical }}">
    @if (filled($pageDescription))
        <meta property="og:description" content="{{ $pageDescription }}">
    @endif
    @if (filled($pageImage))
        <meta property="og:image" content="{{ $pageImage }}">
        <meta property="og:image:alt" content="{{ $pageTitle }}">
    @endif

    <meta name="twitter:card" content="{{ filled($pageImage) ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    @if (filled($pageDescription))
        <meta name="twitter:description" content="{{ $pageDescription }}">
    @endif
    @if (filled($pageImage))
        <meta name="twitter:image" content="{{ $pageImage }}">
        <meta name="twitter:image:alt" content="{{ $pageTitle }}">
    @endif

    <link rel="icon" href="{{ asset('assets/favicon-32.png') }}" sizes="32x32" type="image/png">
    <link rel="icon" href="{{ asset('assets/favicon-192.png') }}" sizes="192x192" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('assets/apple-touch-icon.png') }}">

    {{-- Font duy nhất của site: tải song song với CSS thay vì đợi CSS xong
         mới phát hiện → chữ đổi font sớm, không làm nhảy bố cục (CLS) trên mobile. --}}
    <link rel="preload" href="{{ asset('assets/fonts/NobelVnu-Book.woff') }}" as="font" type="font/woff" crossorigin>
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}@if ($cssVersion)?v={{ $cssVersion }}@endif">
    @stack('preload')

    @isset($jsonld)
        {{-- JSON_HEX_TAG: chữ admin nhập có "</script>" cũng không phá được thẻ. --}}
        <script type="application/ld+json">{!! json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endisset

    @include('frontend.partials.tracking')
</head>
<body class="{{ trim(($bodyClass ?? '').($salesBar ? ' has-sales' : '')) }}">
<a class="skip" href="#main">Đến nội dung chính</a>

@include('frontend.partials.header', ['overlay' => $overlay])

<main id="main">
    @yield('content')
</main>

@include('frontend.partials.footer')

@if ($salesBar)
    @include('frontend.partials.sales-bar')
@endif

{{-- Popup báo giá: có mặt ở mọi trang để nút "Báo giá" mở tại chỗ; chỉ tự
     bật khi $popup = true. Trang /bao-gia đã có form sẵn nên bỏ qua. --}}
@unless (request()->routeIs('quote'))
    @include('frontend.partials.quote-dialog', ['autoOpen' => $popup])
@endunless

{{-- JavaScript duy nhất của site — lớp nâng cấp, tắt đi mọi thứ vẫn chạy. --}}
<script src="{{ asset('assets/lead.js') }}@if ($jsVersion)?v={{ $jsVersion }}@endif" defer></script>
@stack('scripts')
</body>
</html>
