{{--
    Thẻ một dòng xe trong lưới.

    $product là model Product (hoặc mảng cùng khoá, dùng cho bản cắt tĩnh).
    `data-category` phải chứa slug danh mục vì bộ lọc ở trang danh sách chạy
    bằng CSS `:has()` chứ không phải JS — xem frontend/products.blade.php.

    Ảnh lấy từ `hero.src`; chưa upload thì lùi về ảnh cùng tên slug trong
    public/assets/ của bản thiết kế, để site mới dựng không trống trơn.
--}}
@php
    $isModel = $product instanceof \Illuminate\Database\Eloquent\Model;

    $slug  = $isModel ? $product->slug : $product['slug'];
    $name  = $isModel ? $product->name : $product['name'];
    $desc  = $isModel ? $product->tagline : ($product['desc'] ?? null);
    $cat   = $isModel ? ($product->category?->slug ?? '') : ($product['cat'] ?? '');
    $label = $isModel ? ($product->category?->name ?? '') : ($product['type'] ?? '');

    $price = $isModel
        ? catalog_money($product->price_from)
        : (filled($product['price'] ?? null) ? $product['price'].' ₫' : null);

    $img = ($isModel ? catalog_image(data_get($product->hero, 'src')) : ($product['image'] ?? null))
        ?: asset('assets/'.$slug.'.webp');
@endphp
<article class="model-card" data-category="{{ $cat }}" id="{{ $slug }}-card">
    <a class="model-image" href="{{ route('products.show', $slug) }}">
        <img src="{{ $img }}" alt="{{ $name }}"
             width="1600" height="1067" loading="lazy" decoding="async">
        @if (filled($label))
            <span class="model-label">{{ mb_strtoupper($label) }}</span>
        @endif
    </a>
    <div class="model-info">
        <div>
            <h3>{{ $name }}</h3>
            @if (filled($desc))<p>{{ $desc }}</p>@endif
        </div>
        @if (filled($price))
            <div class="model-price"><small>GIÁ TỪ</small>{{ $price }}</div>
        @endif
    </div>
    <div class="card-links">
        <a href="{{ route('products.show', $slug) }}">Khám phá dòng xe ↗</a>
        <a href="{{ route('quote', ['xe' => $slug]) }}" data-quote @if ($isModel) data-product="{{ $product->getKey() }}" @endif>Nhận báo giá</a>
    </div>
</article>
