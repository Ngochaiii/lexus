{{--
    Mã QR Zalo — chỉ hiện trên máy tính (.desk-only, style.css). Xem
    App\Support\ZaloQr và tests/Feature/DesktopZaloQrTest.

    Vì sao (16/11/2026): cả 3 form từ quảng cáo đều gửi từ máy tính, người nghe
    máy đều nói không gửi. Máy tính không quay số được, Zalo thường không mở →
    khách chỉ còn cách gõ số vào form. Quét QR thì khách nhắn bằng chính Zalo
    của mình, chị Hà thấy tên, ảnh thật.

    Biến: $zaloUrl (bắt buộc), $qrName (tên người tư vấn), $qrPhone (số gọi).
--}}
<div class="zalo-qr desk-only">
    {!! \App\Support\ZaloQr::svg($zaloUrl) !!}
    <p class="zalo-qr__text"><strong>Quét bằng camera điện thoại để nhắn Zalo {{ $qrName ?: 'chuyên viên' }}</strong>
        @if (filled($qrPhone ?? null))<span>hoặc gọi {{ \App\Support\Phone::format($qrPhone) }}</span>@endif</p>
</div>
