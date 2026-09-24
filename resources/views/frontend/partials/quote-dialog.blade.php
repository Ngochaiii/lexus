{{--
    Popup nhận báo giá — một <dialog> dùng chung cho cả site.

    Hai cách mở, cùng một hộp:
      1. Khách bấm nút "Báo giá" bất kỳ (link có data-quote hoặc trỏ tới
         /bao-gia) → gửi vào form `nhan-bao-gia`.
      2. Tự bật trên trang chủ sau config('catalog.frontend.popup.delay') giây
         → gửi vào form `popup-bao-gia`, để danh sách Lead ở admin tách được
         khách đến từ popup với khách tự bấm nút.

    Toàn bộ hành vi nằm ở public/assets/lead.js. Không có JS thì <dialog>
    không mở, và mọi nút "Báo giá" đi thẳng tới trang /bao-gia — không có
    nút chết.

    <dialog> gốc của trình duyệt lo sẵn: nền mờ (::backdrop), phím ESC đóng,
    focus bị giữ trong hộp khi mở bằng showModal(), trả focus về nút cũ khi
    đóng. Không cần thư viện.

    Biến: $autoOpen (bool) — chỉ trang chủ truyền true.
--}}
@php
    $cfg      = (array) config('catalog.frontend.popup', []);
    $autoOpen = ($autoOpen ?? false) && ($cfg['enabled'] ?? true);
@endphp
<dialog class="quote-dialog" id="quote-dialog" aria-labelledby="quote-dialog-title"
        data-quote-path="{{ parse_url(route('quote'), PHP_URL_PATH) }}"
        data-manual-action="{{ route('leads.store', 'nhan-bao-gia') }}"
        data-auto-action="{{ route('leads.store', 'popup-bao-gia') }}"
        @if ($autoOpen)
            data-auto-delay="{{ (int) ($cfg['delay'] ?? 20) }}"
            data-auto-mobile="{{ ($cfg['mobile'] ?? false) ? 1 : 0 }}"
            data-dismiss-days="{{ (int) ($cfg['dismiss_days'] ?? 7) }}"
            data-sent-days="{{ (int) ($cfg['sent_days'] ?? 90) }}"
        @endif>

    {{-- method="dialog": bấm là đóng hộp, không cần JS cho nút này. --}}
    <form method="dialog" class="quote-dialog__close-form">
        <button class="quote-dialog__close" aria-label="Đóng">×</button>
    </form>

    {{-- Ảnh thật tại quầy lễ tân Lexus Thăng Long: có logo trên tường, và là
         người sẽ gọi lại cho khách — gắn form với một cơ sở, một con người
         cụ thể thay vì ảnh xe chung chung. Ảnh dọc khớp khung cao của popup.
         Cố ý không dùng ảnh bàn giao: có mặt khách hàng, không nên đặt cạnh
         form xin thông tin. --}}
    @php
        $dealer  = catalog_setting('site_name', 'Lexus Thăng Long');
        $advisor = catalog_setting('advisor_name');
    @endphp
    <figure class="quote-dialog__media">
        <img src="{{ asset('assets/personal/welcome-1200.webp') }}"
             srcset="{{ asset('assets/personal/welcome-640.webp') }} 640w,
                     {{ asset('assets/personal/welcome-1200.webp') }} 1200w"
             sizes="(max-width: 700px) 0px, 400px"
             width="1200" height="2191" loading="lazy" decoding="async"
             alt="{{ $advisor ? $advisor.', chuyên viên tư vấn, tại quầy lễ tân '.$dealer : 'Quầy lễ tân '.$dealer }}">
        <figcaption>
            <span>{{ mb_strtoupper($dealer) }}</span>
            @if ($advisor)
                <p>{{ $advisor }} — người sẽ gửi báo giá cho bạn.</p>
            @else
                <p>Hân hạnh được đón tiếp bạn tại showroom.</p>
            @endif
        </figcaption>
    </figure>

    <div class="quote-dialog__body">
        <div class="eyebrow">BÁO GIÁ LEXUS</div>
        <h2 id="quote-dialog-title">Báo giá lăn bánh,<br>gửi riêng cho bạn.</h2>
        <p class="quote-dialog__lede">Chọn dòng xe bạn quan tâm. Chuyên viên tư vấn sẽ gửi báo giá chi tiết
            từng khoản và phương án sở hữu phù hợp.</p>

        {{-- products/selected truyền null TƯỜNG MINH: @include kế thừa biến
             của trang cha, nên nếu bỏ trống thì popup nhận nhầm $products
             (paginator của trang danh sách) và $selected (xe đang chọn ở
             trang lái thử). JS tự chọn xe khi mở popup. --}}
        @include('frontend.partials.lead-form', [
            'formKey'     => 'nhan-bao-gia',
            'submitLabel' => 'Nhận báo giá',
            'instance'    => 'popup',
            'products'    => null,
            'selected'    => null,
            'note'        => 'Không phát sinh chi phí. Thông tin của bạn chỉ dùng cho mục đích tư vấn.',
        ])
    </div>
</dialog>
