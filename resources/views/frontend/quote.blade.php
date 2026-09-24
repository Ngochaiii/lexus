{{--
    Nhận báo giá — /bao-gia. Bố cục giống trang đăng ký lái thử (cắt từ
    template/bao-gia.html): trái là chuyên viên + showroom, phải là form.

    Là đường dự phòng khi không có JavaScript — có JS thì mọi nút "Báo giá"
    mở popup ngay tại trang (partials/quote-dialog). Cũng là trang đích tốt
    cho quảng cáo và tìm kiếm "báo giá lexus".

    Biến từ QuoteController: $form · $products · $selected
--}}
@extends('frontend.layout', [
    'title'       => 'Nhận báo giá xe Lexus | '.catalog_setting('site_name', config('app.name')),
    'description' => 'Để lại thông tin để nhận báo giá lăn bánh và phương án sở hữu Lexus phù hợp từ chuyên viên tư vấn '.catalog_setting('site_name', 'Lexus Thăng Long').'.',
])

@section('content')
    @include('frontend.partials.page-intro', [
        'title'   => 'Nhận báo giá dành riêng cho bạn.',
        'eyebrow' => 'BÁO GIÁ LEXUS',
        'text'    => 'Chọn dòng xe bạn quan tâm. Chuyên viên tư vấn sẽ gửi báo giá lăn bánh và phương án sở hữu phù hợp.',
    ])

    <section class="container section">
        <div class="lead-layout">
            <div class="lead-copy">
                @include('frontend.partials.advisor-mini')

                <div class="eyebrow">TƯ VẤN CÁ NHÂN</div>
                <h2>Một báo giá.<br>Rõ ràng từng khoản.</h2>
                <p>Báo giá gồm giá xe, lệ phí trước bạ, biển số, đăng kiểm và bảo hiểm theo nơi bạn đăng ký
                    xe. Không phát sinh chi phí khi yêu cầu báo giá.</p>

                @include('frontend.partials.contact-details')
            </div>

            @include('frontend.partials.lead-form', [
                'formKey'     => $form->key,
                'submitLabel' => 'Nhận báo giá',
                'products'    => $products,
                'selected'    => $selected,
                'variant'     => $selectedVariant ?? null,
                'instance'    => 'trang',
                'note'        => 'Chuyên viên tư vấn sẽ gửi báo giá trong giờ làm việc. Thông tin của bạn chỉ dùng cho mục đích tư vấn.',
            ])
        </div>
    </section>
@endsection
