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
    $callForm    = once(fn () => \App\Support\Catalog::query('form')->where('key', 'goi-lai-nhanh')->exists());
@endphp
@if ($callPhone && $callForm)
    <dialog class="call-sheet" id="call-sheet" aria-labelledby="call-sheet-title">
        <button type="button" class="quote-dialog__close" data-dialog-close aria-label="Đóng">×</button>

        <h2 id="call-sheet-title">Chưa gọi được?</h2>

        @include('frontend.partials.quick-callback', [
            'idPrefix' => 'call-sheet',
            'label'    => 'Để lại số, '.$callAdvisor.' gọi lại cho bạn.',
        ])

        <p class="call-sheet__again">Hoặc gọi lại:
            <a class="text-link" href="tel:{{ $callPhone }}" data-call-again>{{ \App\Support\Phone::format($callPhone) }}</a></p>
    </dialog>
@endif
