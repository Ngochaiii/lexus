{{--
    Khối thông tin showroom + chuyên viên, lấy từ Cài đặt (tab Chung).
    Dùng ở trang đăng ký lái thử và các trang tĩnh. Ô nào trống thì khối đó
    không render — đúng nguyên tắc "ô trống thì không hiện" của core.
--}}
@php
    $dealer   = catalog_setting('site_name');
    $address  = catalog_setting('address');
    $mapUrl   = catalog_setting('map_url');
    $advisor  = catalog_setting('advisor_name');
    $phone    = catalog_setting('advisor_phone') ?: catalog_setting('hotline');
    $zalo     = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
    $hours    = catalog_setting('opening_hours');

    $phoneFmt = \App\Support\Phone::format($phone);
@endphp
<div class="contact-details">
    @if (filled($address))
        <div>
            <span>Showroom {{ $dealer }}</span>
            <p>{!! nl2br(e($address)) !!}</p>
            @if (filled($mapUrl))
                <a class="small" href="{{ $mapUrl }}" rel="noopener" target="_blank">Chỉ đường trên Google Maps ↗</a>
            @endif
        </div>
    @endif

    @if (filled($phone))
        <div>
            <span>Chuyên viên tư vấn</span>
            <p>@if ($advisor){{ $advisor }} &nbsp; — &nbsp; @endif<a href="tel:{{ $phone }}">{{ $phoneFmt }}</a></p>
        </div>
    @endif

    @if (filled($zalo))
        <div>
            <span>Zalo</span>
            <a href="{{ \Illuminate\Support\Str::startsWith($zalo, 'http') ? $zalo : 'https://zalo.me/'.$zalo }}"
               rel="noopener">Nhắn Zalo {{ $phoneFmt }} ↗</a>
        </div>
    @endif

    @if (filled($hours))
        <div>
            <span>Giờ đón tiếp</span>
            <p>{{ $hours }}</p>
        </div>
    @endif
</div>
