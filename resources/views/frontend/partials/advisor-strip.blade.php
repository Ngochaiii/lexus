{{--
    Khối người tư vấn gọn, đặt NGAY TRÊN giá ở trang đích quảng cáo (05/11/2026).

    Vì sao: khách sắp hỏi xe 3–7 tỷ phải cuộn hơn 3.300 px trên điện thoại mới
    thấy chị Hà là ai (partials/advisor-card nằm sau bảng giá), và web đã có đủ
    giá nên không còn lý do liên hệ. Lý do thật: phí lăn bánh tuỳ nơi đăng ký
    xe, web chỉ tạm tính một nơi. Bấm Zalo chép sẵn lời nhắn có chỗ điền tỉnh
    (data-zalo-tail, lead.js). Lượt bấm ghi place=strip.

    Biến: $car (Product, tuỳ chọn) → chưa cài Zalo thì form báo giá chọn sẵn xe.
--}}
@php
    $stripName = catalog_setting('advisor_name');
    $stripExp  = catalog_setting('advisor_experience');
    $stripRaw  = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
    $stripZalo = $stripRaw ? (\Illuminate\Support\Str::startsWith($stripRaw, 'http') ? $stripRaw : 'https://zalo.me/'.$stripRaw) : null;
    $stripPhoto = catalog_image(catalog_setting('advisor_image'));
    $stripCar  = $car ?? null;
@endphp
@if (filled($stripName) && ! catalog_setting('advisor_off'))
    <aside class="advisor-strip" aria-label="Người tư vấn {{ $stripName }}">
        <img class="advisor-avatar" src="{{ $stripPhoto ?: asset('assets/personal/avatar-160.webp') }}"
             width="160" height="160" alt="Chân dung {{ $stripName }}" loading="lazy" decoding="async">
        <div class="advisor-strip__body">
            <p class="advisor-strip__who"><strong>{{ $stripName }}</strong>
                @if (filled($stripExp))<span>{{ $stripExp }}</span>@endif</p>
            <p>Giá trên web tạm tính cho {{ config('catalog.on_road.region') }}. Nhắn Zalo nơi đăng ký xe, {{ $stripName }} gửi giá lăn bánh đúng nơi của bạn.</p>
            @if ($stripZalo)
                <a class="text-link" href="{{ $stripZalo }}" rel="noopener" target="_blank" data-zalo-tail=", đăng ký xe ở ... ạ.">Nhắn Zalo hỏi giá</a>
            @else
                <a class="text-link" href="{{ route('quote', array_filter(['xe' => $stripCar?->slug])) }}" data-quote
                   @if ($stripCar) data-product="{{ $stripCar->id }}" @endif>Để lại số, {{ $stripName }} gọi lại</a>
            @endif
        </div>
    </aside>
@endif
