{{--
    Một thẻ <img> đã gắn sẵn srcset/sizes/width/height.

        <x-img :src="$product->cover" alt="VF 3" sizes="(max-width: 960px) 100vw, 33vw" />

    - `src`   giá trị người nhập gõ (đường dẫn disk public, mảng FileUpload, hoặc link ngoài)
    - `sizes` bề rộng ảnh sẽ chiếm trên màn hình; mặc định 100vw
    - `eager` đặt true cho ảnh nằm ngay đầu trang (hero) để không lazy-load

    Ảnh chưa chạy `php artisan catalog:images` thì không có srcset — thẻ vẫn
    render bình thường với src gốc, chỉ là không có bản nhẹ để chọn.
--}}
@props([
    'src'   => null,
    'alt'   => '',
    'sizes' => '100vw',
    'eager' => false,
])

@php
    $url = \App\Support\Media::url($src);
@endphp

@if ($url)
    @php
        $set = \App\Support\Media::srcset($src);
        $dim = \App\Support\Media::dimensions($src);
    @endphp

    <img
        src="{{ $url }}"
        @if ($set) srcset="{{ $set }}" sizes="{{ $sizes }}" @endif
        @if ($dim) width="{{ $dim['w'] }}" height="{{ $dim['h'] }}" @endif
        alt="{{ $alt }}"
        loading="{{ $eager ? 'eager' : 'lazy' }}"
        decoding="async"
        @if ($eager) fetchpriority="high" @endif
        {{ $attributes }}
    >
@endif
