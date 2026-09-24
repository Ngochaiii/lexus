{{--
    Đăng ký lái thử — cắt từ template/lai-thu.html, đã nối dữ liệu thật.

    Bố cục hai cột: trái là chuyên viên + showroom (lấy từ Cài đặt), phải là
    form. Trên mobile CSS `.lead-layout` xếp thành một cột.

    Biến từ BookingController: $forms · $products · $mode · $selected
--}}
@extends('frontend.layout', [
    'title'       => 'Đăng ký lái thử xe Lexus tại Hà Nội | '.catalog_setting('site_name', config('app.name')),
    'description' => 'Đặt lịch lái thử xe Lexus (SUV, Sedan, MPV) tại showroom Lexus Thăng Long, Cầu Giấy, Hà Nội. Chuyên viên tư vấn gọi lại xác nhận lịch hẹn.',
    'jsonld'      => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::forBreadcrumb([
            ['name' => 'Trang chủ', 'url' => route('home')],
            ['name' => 'Đăng ký lái thử', 'url' => route('booking')],
        ]),
        \App\Support\JsonLd::organization(),
    ),
])

@section('content')
    @php
        // config('catalog.frontend.booking.forms') chỉ khai một form cho bản
        // này; nếu sau có nhiều hình thức thì lấy cái đang chọn.
        $form  = $mode ?? $forms->first();
        $zalo  = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
        $phone = catalog_setting('advisor_phone') ?: catalog_setting('hotline');
    @endphp

    @include('frontend.partials.page-intro', [
        'title'   => 'Cảm nhận Lexus. Bằng chính bạn.',
        'eyebrow' => 'KẾT NỐI CÙNG LEXUS',
        'text'    => 'Chọn một khoảng thời gian thư thái để khám phá chiếc Lexus bạn yêu thích.',
    ])

    <section class="container section">
        <div class="lead-layout">
            <div class="lead-copy">
                @include('frontend.partials.advisor-mini')

                <div class="eyebrow">TƯ VẤN CÁ NHÂN</div>
                <h2>Hân hạnh<br>được đón tiếp bạn.</h2>

                @include('frontend.partials.contact-details')

                @if (filled($zalo))
                    <div id="zalo" class="notice">Zalo:
                        <a href="{{ \Illuminate\Support\Str::startsWith($zalo, 'http') ? $zalo : 'https://zalo.me/'.$zalo }}"
                           rel="noopener"><u>{{ \App\Support\Phone::format($phone) }}</u></a>
                        — nhắn tin trực tiếp cho {{ catalog_setting('advisor_name', 'chuyên viên tư vấn') }},
                        phản hồi trong giờ làm việc.</div>
                @endif
            </div>

            @if ($form)
                @include('frontend.partials.lead-form', [
                    'formKey'     => $form->key,
                    'submitLabel' => $form->name,
                    'products'    => $products,
                    'selected'    => $selected,
                    'instance'    => 'trang',
                    'note'        => 'Chuyên viên tư vấn sẽ gọi lại để xác nhận thời gian và địa điểm lái thử.',
                ])
            @else
                <p>Form đăng ký chưa được cấu hình. Vui lòng liên hệ trực tiếp qua hotline.</p>
            @endif
        </div>
    </section>
@endsection
