{{--
    Thẻ một PHIÊN BẢN (RX 350h Luxury, LX 600 VIP…) — lưới trang chủ.

    Nút "Nhận báo giá" mang data-variant: popup (lead.js) gửi kèm variant_id,
    nên danh sách Lead ở admin ghi đúng mẫu khách quan tâm. Không JS thì link
    /bao-gia?xe=…&phien-ban=… chọn sẵn phiên bản trên trang báo giá.

    `data-category` gồm slug danh mục + "hybrid" nếu là bản hybrid — bộ lọc
    trang chủ chạy bằng CSS `:has()` giống trang danh sách xe.

    Biến: $variant (ProductVariant, đã nạp ->product->category)
--}}
@php
    $product  = $variant->product;
    $isHybrid = (bool) preg_match('/\d{3}h\b/i', $variant->name);
    $cats     = trim(($product->category?->slug ?? '').($isHybrid ? ' hybrid' : ''));
    $img      = catalog_image($variant->image) ?: catalog_image(data_get($product->hero, 'src'));
    $title    = str_starts_with($variant->name, 'Lexus') ? $variant->name : 'Lexus '.$variant->name;
@endphp
<article class="model-card variant-card" data-category="{{ $cats }}">
    @php $variantUrl = filled($variant->slug) ? \App\Support\Url::variant($product->slug, $variant->slug) : route('products.show', $product->slug).'#versions'; @endphp
    <a class="model-image" href="{{ $variantUrl }}">
        @if ($img)
            <x-img :src="$variant->image ?: data_get($product->hero, 'src')" :alt="$title"
                   sizes="(max-width: 600px) 100vw, (max-width: 1100px) 50vw, 25vw" />
        @endif
        @if ($product->category)
            <span class="model-label">{{ mb_strtoupper($product->category->name) }}</span>
        @endif
    </a>
    {{--
        Mỗi hàng trong thẻ có chiều cao cố định (tên 2 dòng, ghi chú 2 dòng)
        nên tên, giá và nút của các thẻ cùng hàng luôn thẳng nhau dù tên dài
        ngắn khác nhau — xem .variant-card trong style.css.
    --}}
    <div class="model-info">
        <h3><a href="{{ $variantUrl }}"><span class="variant-card__brand">Lexus</span> <span class="variant-card__name">{{ \Illuminate\Support\Str::after($title, 'Lexus ') }}</span></a></h3>
        <p>{{ $variant->note }}</p>
        <div class="model-price"><small>Giá niêm yết</small><strong @class(['is-pending' => ! $variant->price])>{{ catalog_money($variant->price) ?: 'Đang cập nhật' }}</strong></div>
    </div>
    <div class="card-links">
        <a href="{{ $variantUrl }}">Chi tiết phiên bản ↗</a>
        <a href="{{ route('quote', ['xe' => $product->slug, 'phien-ban' => $variant->getKey()]) }}"
           data-quote data-product="{{ $product->getKey() }}"
           data-variant="{{ $variant->getKey() }}" data-variant-name="{{ $variant->name }}">Nhận báo giá</a>
    </div>
</article>
