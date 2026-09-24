{{--
    Thanh hành động dính đáy, chỉ hiện ở mobile (CSS `.sales-bar`, ≤600px)
    và chỉ trên trang có $salesBar = true. `body.has-sales` chừa chỗ cho nó.
--}}
@php
    $advisor = catalog_setting('advisor_name');
    $phone   = catalog_setting('advisor_phone') ?: catalog_setting('hotline');
    $zaloRaw = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
    $zalo    = $zaloRaw ? (\Illuminate\Support\Str::startsWith($zaloRaw, 'http') ? $zaloRaw : 'https://zalo.me/'.$zaloRaw) : null;
@endphp
<nav class="sales-bar" aria-label="Tư vấn nhanh">
    <a href="{{ route('quote') }}" data-quote>Nhận báo giá</a>
    @if ($phone)<a href="tel:{{ $phone }}">Gọi {{ $advisor ?: 'tư vấn' }}</a>@endif
    @if ($zalo)<a href="{{ $zalo }}" rel="noopener">Zalo</a>@endif
</nav>
