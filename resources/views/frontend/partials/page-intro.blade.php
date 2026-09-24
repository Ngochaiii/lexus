{{--
    Dải mở đầu của trang trong: breadcrumb + eyebrow + H1 + mô tả.

    Biến: $title (bắt buộc) · $eyebrow · $text · $crumbs (mảng [nhãn => url],
    phần tử cuối không có link). Trang chủ luôn là mắt xích đầu.
--}}
@php
    $crumbs  = $crumbs  ?? [];
    $eyebrow = $eyebrow ?? null;
    $text    = $text    ?? null;
@endphp
<section class="page-intro stone">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Trang chủ</a>
            @foreach ($crumbs as $label => $url)
                &nbsp; / &nbsp; @if ($url)<a href="{{ $url }}">{{ $label }}</a>@else{{ $label }}@endif
            @endforeach
            &nbsp; / &nbsp; {{ $title }}
        </div>
        @if ($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
        <h1>{{ $title }}</h1>
        @if ($text)<p>{{ $text }}</p>@endif
    </div>
</section>
