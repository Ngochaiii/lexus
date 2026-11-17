{{--
    Khung sau nút Gọi — một <dialog> dùng chung cho mọi nút Gọi của site.

    Vì sao (04/11/2026): 3 lượt bấm Gọi từ quảng cáo trong một ngày, chị Hà
    không có cuộc gọi nào; gọi thử vẫn đổ chuông — khách mở màn hình quay số
    rồi đổi ý. Số khách để lại thì nằm trên máy chủ, không thất lạc.

    Bấm Gọi (lead.js): link vẫn quay số như cũ, khung chỉ chờ sẵn khi khách
    quay lại web. Không JS thì khung không bao giờ mở. Form dùng chung
    `goi-lai-nhanh`; chưa có form thì không hiện khung.
--}}
@php
    $callPhone   = catalog_setting('advisor_phone') ?: catalog_setting('hotline');
    $callAdvisor = catalog_setting('advisor_name') ?: 'chuyên viên';
    $callZaloRaw = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
    $callZalo    = $callZaloRaw ? (\Illuminate\Support\Str::startsWith($callZaloRaw, 'http') ? $callZaloRaw : 'https://zalo.me/'.$callZaloRaw) : null;
    $callForm    = once(fn () => \App\Support\Catalog::query('form')->where('key', 'goi-lai-nhanh')->exists());
@endphp
@if ($callPhone && $callForm)
    <dialog class="call-sheet" id="call-sheet" aria-labelledby="call-sheet-title">
        <button type="button" class="quote-dialog__close" data-dialog-close aria-label="Đóng">×</button>

        <h2 id="call-sheet-title"><span @if ($callZalo) class="touch-only" @endif>Chưa gọi được?</span>@if ($callZalo)<span class="desk-only">Gọi hoặc nhắn Zalo {{ $callAdvisor }}</span>@endif</h2>

        {{-- Máy tính (16/11/2026): không quay số được → bấm Gọi mở khung này
             (lead.js chặn link) với mã QR Zalo + số in rõ, form để lại số
             ngay bên dưới (không thu lại — 17/11). --}}
        @if ($callZalo)
            @include('frontend.partials.zalo-qr', ['zaloUrl' => $callZalo, 'qrName' => $callAdvisor, 'qrPhone' => $callPhone])
        @endif
        @include('frontend.partials.quick-callback', [
            'idPrefix' => 'call-sheet',
            'label'    => 'Để lại số, '.$callAdvisor.' gọi lại cho bạn.',
        ])

        <p class="call-sheet__again{{ $callZalo ? ' touch-only' : '' }}">Hoặc gọi lại:
            <a class="text-link" href="tel:{{ $callPhone }}" data-call-again>{{ \App\Support\Phone::format($callPhone) }}</a></p>
    </dialog>
@endif
