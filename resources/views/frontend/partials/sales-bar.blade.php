{{--
    Liên hệ nhanh trên mọi trang ($salesBar mặc định true ở layout):
      · mobile (≤600px): thanh dính đáy Báo giá / Gọi / Zalo — `body.has-sales` chừa chỗ;
      · màn hình lớn hơn: hai nút tròn nổi góc dưới trái (Gọi có vòng sóng, Zalo).
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

@if ($phone || $zalo)
    <div class="contact-float" aria-label="Liên hệ nhanh">
        @if ($phone)
            <a class="contact-float__btn contact-float__call" href="tel:{{ $phone }}"
               aria-label="Gọi {{ $advisor ?: 'tư vấn' }} {{ \App\Support\Phone::format($phone) }}">
                <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/></svg>
                <span class="contact-float__label">{{ \App\Support\Phone::format($phone) }}</span>
            </a>
        @endif
        @if ($zalo)
            <a class="contact-float__btn contact-float__zalo" href="{{ $zalo }}" rel="noopener" target="_blank"
               aria-label="Nhắn Zalo {{ $advisor ?: 'tư vấn' }}">
                <span aria-hidden="true">Zalo</span>
                <span class="contact-float__label">Nhắn Zalo</span>
            </a>
        @endif
    </div>
@endif
