{{--
    Băng chuyển đổi nền tối, đặt cuối hầu hết các trang.
    Nối backend: hai nhãn nút lấy từ config('catalog.labels.cta').
--}}
<section class="conversion dark">
    <div>
        <div class="eyebrow">Hành trình bắt đầu từ bạn</div>
        <h2>Chạm đến trải nghiệm Lexus.</h2>
    </div>
    <div class="actions">
        <a class="button light" href="{{ route('booking') }}">Đặt lịch lái thử</a>
        <a class="text-link" href="{{ route('quote') }}" data-quote>Nhận tư vấn riêng</a>
    </div>
</section>
