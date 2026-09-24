{{--
    Footer 5 cột nền tối + dòng pháp lý.

    Cột "Dòng xe" lấy từ Product::published(); liên hệ và dòng bản quyền lấy
    từ Cài đặt. Ô nào trống thì link đó không render.
--}}
@php
    $dealer   = catalog_setting('site_name', 'Lexus Thăng Long');
    $advisor  = catalog_setting('advisor_name');
    $phone    = catalog_setting('advisor_phone') ?: catalog_setting('hotline');
    $phoneFmt = \App\Support\Phone::format($phone);
    $zaloRaw  = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
    $zalo     = $zaloRaw ? (\Illuminate\Support\Str::startsWith($zaloRaw, 'http') ? $zaloRaw : 'https://zalo.me/'.$zaloRaw) : null;
    $maps     = catalog_setting('map_url');
    $company  = catalog_setting('company_name', $dealer);

    $footerModels = once(fn () => \App\Support\Catalog::query('product')
        ->published()->orderBy('sort')->take(6)->get());
@endphp
<footer class="site-footer">
    <div class="footer-top">
        <div class="footer-brand">
            <a class="brand" href="{{ route('home') }}" aria-label="{{ $dealer }} — Trang chủ">
                <img class="brand-logo" src="{{ asset('assets/logo-ltl-white.webp') }}"
                     alt="{{ $dealer }}" width="800" height="68" loading="lazy" decoding="async">
            </a>
            <p>Tinh hoa trong từng chi tiết.<br>Cảm hứng trên mọi hành trình.</p>
        </div>

        <div>
            <h3>Dòng xe</h3>
            @foreach ($footerModels as $item)
                <a href="{{ route('products.show', $item->slug) }}">{{ $item->name }}</a>
            @endforeach
        </div>

        <div>
            <h3>Mua xe</h3>
            <a href="{{ route('pages.show', 'bang-gia') }}">Bảng giá xe</a>
            <a href="{{ route('pages.show', 'uu-dai') }}">Đặc quyền sở hữu</a>
            <a href="{{ route('pages.show', 'tai-chinh') }}">Giải pháp tài chính</a>
            <a href="{{ route('booking') }}">Đăng ký lái thử</a>
        </div>

        <div>
            <h3>Trải nghiệm Lexus</h3>
            <a href="{{ route('pages.show', 'the-gioi-lexus') }}">Tinh hoa Takumi</a>
            <a href="{{ route('pages.show', 'the-gioi-lexus') }}#omotenashi">Omotenashi</a>
            <a href="{{ route('pages.show', 'dich-vu') }}">Dịch vụ &amp; chăm sóc</a>
            <a href="{{ route('posts.index') }}">Câu chuyện Lexus</a>
        </div>

        <div>
            <h3>Kết nối</h3>
            <a href="{{ route('home') }}#chuyen-vien">Người tư vấn của bạn</a>
            <a href="{{ route('pages.show', 'showroom') }}">Showroom</a>
            <a href="{{ route('pages.show', 'lien-he') }}">Liên hệ tư vấn</a>
            @if ($phone)<a href="tel:{{ $phone }}">{{ $advisor ? $advisor.' · ' : '' }}{{ $phoneFmt }}</a>@endif
            @if ($zalo)<a href="{{ $zalo }}" rel="noopener">Zalo: {{ $phoneFmt }}</a>@endif
            @if ($maps)<a href="{{ $maps }}" rel="noopener" target="_blank">{{ $dealer }}</a>@endif
            <a href="{{ route('pages.show', 'faq') }}">Câu hỏi thường gặp</a>
        </div>
    </div>

    <div class="footer-bottom">
        <div>© {{ date('Y') }} {{ $company }}. Website tư vấn cá nhân của chuyên viên bán hàng,
            không phải website chính thức của Lexus Việt Nam.<br>Giá là thông tin tham khảo, vui lòng liên hệ
            để nhận báo giá chính thức. Hình ảnh xe có thể khác phiên bản tại Việt Nam.</div>
        <span>
            <a href="{{ route('pages.show', 'quyen-rieng-tu') }}">Quyền riêng tư</a> &nbsp; / &nbsp;
            <a href="{{ route('pages.show', 'dieu-khoan') }}">Điều khoản</a>
        </span>
    </div>
</footer>
