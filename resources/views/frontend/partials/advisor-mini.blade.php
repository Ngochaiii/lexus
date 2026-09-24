{{--
    Thẻ nhỏ giới thiệu chuyên viên tư vấn, đặt cạnh form thu lead.

    Tên, chức danh và ảnh lấy từ Cài đặt (nhóm advisor_*). Chưa upload ảnh
    thì dùng bộ chân dung có sẵn trong public/assets/personal/.
--}}
@php
    $advisor = catalog_setting('advisor_name', 'Chuyên viên tư vấn');
    $role    = catalog_setting('advisor_role', 'Chuyên viên tư vấn');
    $photo   = catalog_image(catalog_setting('advisor_image'));
@endphp
<a class="advisor-mini" href="{{ route('home') }}#chuyen-vien">
    <img class="advisor-avatar"
         src="{{ $photo ?: asset('assets/personal/portrait-960.webp') }}"
         @unless ($photo)
             srcset="{{ asset('assets/personal/portrait-320.webp') }} 320w,
                     {{ asset('assets/personal/portrait-640.webp') }} 640w,
                     {{ asset('assets/personal/portrait-960.webp') }} 960w"
         @endunless
         sizes="76px" width="960" height="960"
         alt="Chân dung {{ $advisor }}"
         loading="lazy" decoding="async">
    <span>
        <span class="eyebrow">NGƯỜI TƯ VẤN CỦA BẠN</span>
        <strong>{{ $advisor }}</strong>
        <span>{{ $role }}</span>
        <span class="advisor-mini-link">Tìm hiểu người đồng hành ↗</span>
    </span>
</a>
