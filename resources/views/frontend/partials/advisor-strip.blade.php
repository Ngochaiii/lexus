{{--
    Khối người tư vấn gọn, đặt NGAY TRÊN giá ở trang đích quảng cáo (05/11/2026).

    Vì sao: khách sắp hỏi xe 3–7 tỷ phải cuộn hơn 3.300 px trên điện thoại mới
    thấy chị Hà là ai (partials/advisor-card nằm sau bảng giá), và web đã có đủ
    giá nên không còn lý do liên hệ. Lý do thật: phí lăn bánh tuỳ nơi đăng ký
    xe, web chỉ tạm tính một nơi. Bấm Zalo chép sẵn lời nhắn có chỗ điền tỉnh
    (data-zalo-tail, lead.js). Lượt bấm ghi place=strip.

    Biến: $car (Product, tuỳ chọn) → chưa cài Zalo thì form báo giá chọn sẵn xe.
          $callback (true, tuỳ chọn) → gắn ô "để lại số" (form goi-lai-nhanh)
          ngay trong khối, cạnh ảnh người tư vấn (10/11/2026): trước đó ô này
          là một khối riêng nói cùng ý, hai khối chồng nhau đẩy giá xuống.
          Tắt khối người tư vấn thì ô để lại số vẫn hiện riêng.

    Máy tính (16/11/2026): có Zalo thì hiện mã QR Zalo (partials/zalo-qr) làm
    lối chính, ô để lại số thu lại sau nút "Hoặc để lại số" — cả 3 form từ
    quảng cáo đều gửi từ máy tính và người nghe máy đều nói không gửi. Điện
    thoại giữ nguyên (.touch-only / .desk-only, style.css).
--}}
@php
    $stripName = catalog_setting('advisor_name');
    $stripExp  = catalog_setting('advisor_experience');
    $stripRaw  = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
    $stripZalo = $stripRaw ? (\Illuminate\Support\Str::startsWith($stripRaw, 'http') ? $stripRaw : 'https://zalo.me/'.$stripRaw) : null;
    $stripPhoto = catalog_image(catalog_setting('advisor_image'));
    $stripCar  = $car ?? null;
    $stripCallback = ($callback ?? false) && once(fn () => \App\Support\Catalog::query('form')->where('key', 'goi-lai-nhanh')->exists());
    $stripCallbackArgs = [
        'idPrefix'  => $stripCar ? 'xe' : 'quick-callback',
        'productId' => $stripCar?->id,
    ];
@endphp
@if (filled($stripName) && ! catalog_setting('advisor_off'))
    <aside class="advisor-strip" aria-label="Người tư vấn {{ $stripName }}">
        <img class="advisor-avatar" src="{{ $stripPhoto ?: asset('assets/personal/avatar-160.webp') }}"
             width="160" height="160" alt="Chân dung {{ $stripName }}" loading="lazy" decoding="async">
        <div class="advisor-strip__body">
            <p class="advisor-strip__who"><strong>{{ $stripName }}</strong>
                @if (filled($stripExp))<span>{{ $stripExp }}</span>@endif</p>
            @if ($stripCallback)
                <p @if ($stripZalo) class="touch-only" @endif>Giá trên web tạm tính cho {{ config('catalog.on_road.region') }}. Để lại số, {{ $stripName }} gọi lại báo giá lăn bánh{{ $stripCar ? ' '.$stripCar->name : '' }} theo nơi đăng ký xe của bạn.</p>
                @if ($stripZalo)
                    <p class="desk-only">Giá trên web tạm tính cho {{ config('catalog.on_road.region') }}. Nhắn Zalo nơi đăng ký xe, {{ $stripName }} gửi giá lăn bánh{{ $stripCar ? ' '.$stripCar->name : '' }} đúng nơi của bạn.</p>
                    @include('frontend.partials.zalo-qr', ['zaloUrl' => $stripZalo, 'qrName' => $stripName, 'qrPhone' => catalog_setting('advisor_phone') ?: catalog_setting('hotline')])
                    <button type="button" class="text-link desk-only advisor-strip__reveal" data-desk-reveal>Hoặc để lại số, {{ $stripName }} gọi lại</button>
                    <div class="desk-collapse" data-desk-collapse>
                @endif
                @include('frontend.partials.quick-callback', $stripCallbackArgs + [
                    'label' => 'Số điện thoại để '.$stripName.' gọi lại', 'labelHidden' => true,
                ])
                @if ($stripZalo)
                    </div>
                    <a class="text-link advisor-strip__zalo touch-only" href="{{ $stripZalo }}" rel="noopener" target="_blank" data-zalo-tail=", đăng ký xe ở ... ạ.">Hoặc nhắn Zalo hỏi giá</a>
                @endif
            @else
            <p>Giá trên web tạm tính cho {{ config('catalog.on_road.region') }}. Nhắn Zalo nơi đăng ký xe, {{ $stripName }} gửi giá lăn bánh đúng nơi của bạn.</p>
            @if ($stripZalo)
                <a class="text-link" href="{{ $stripZalo }}" rel="noopener" target="_blank" data-zalo-tail=", đăng ký xe ở ... ạ.">Nhắn Zalo hỏi giá</a>
            @else
                <a class="text-link" href="{{ route('quote', array_filter(['xe' => $stripCar?->slug])) }}" data-quote
                   @if ($stripCar) data-product="{{ $stripCar->id }}" @endif>Để lại số, {{ $stripName }} gọi lại</a>
            @endif
            @endif
        </div>
    </aside>
@elseif ($stripCallback)
    @include('frontend.partials.quick-callback', $stripCallbackArgs + [
        'label' => 'Cần giá lăn bánh'.($stripCar ? ' '.$stripCar->name : '').' chi tiết? Để lại số, chuyên viên gọi lại cho bạn.',
    ])
@endif
