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

    $heroSrc = $isModel ? data_get($product->hero, 'src') : null;
    $img = ($isModel ? catalog_image($heroSrc) : ($product['image'] ?? null))
        ?: asset('assets/'.$slug.'.webp');

    // $eager: thẻ đầu lưới nằm ngay màn hình đầu (ảnh LCP của trang danh sách).
    $eager = $eager ?? false;
@endphp
<article class="model-card" data-category="{{ $cat }}" id="{{ $slug }}-card">
    <a class="model-image" href="{{ route('products.show', $slug) }}">
        {{-- Ảnh trong kho → srcset: thẻ chỉ rộng 1/3 màn hình, không tải bản 1600px. --}}
        @if (filled($heroSrc) && catalog_image($heroSrc))
            <x-img :src="$heroSrc" :alt="$name" :eager="$eager"
                   sizes="(max-width: 600px) 100vw, (max-width: 850px) 50vw, 33vw" />
        @else
            <img src="{{ $img }}" alt="{{ $name }}"
                 width="1600" height="1067" loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async">
        @endif
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
