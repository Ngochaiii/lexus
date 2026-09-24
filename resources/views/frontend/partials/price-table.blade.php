{{--
    Bảng giá toàn bộ phiên bản — trang /bang-gia.

    Dựng từ bảng xe + phiên bản chứ không nhập tay trong trang tĩnh: admin
    sửa giá một chỗ (màn hình Xe) là bảng giá, trang xe và JSON-LD cùng đổi,
    không bao giờ lệch nhau.

    Một bảng HTML thật (<table> + <caption> + <th scope>) thay vì thẻ rời:
    người đọc lướt dễ so sánh, còn Google và các công cụ AI đọc được đúng
    cặp "phiên bản → giá" để trích dẫn.
--}}
@php
    $priceProducts = \App\Support\Catalog::query('product')->published()
        ->with(['variants' => fn ($q) => $q->orderBy('sort'), 'category'])
        ->orderBy('price_from')->get()
        ->filter(fn ($p) => $p->variants->isNotEmpty())
        // Xe có giá lên trước theo giá; xe đang cập nhật giá xuống cuối.
        ->sortBy(fn ($p) => $p->price_from ?? PHP_INT_MAX);

    $updatedAt = $priceProducts->flatMap->variants->max('updated_at');
@endphp

@if ($priceProducts->isNotEmpty())
    <section class="container section price-table" id="bang-gia-chi-tiet">
        <div class="section-heading">
            <div>
                <div class="eyebrow">GIÁ NIÊM YẾT {{ $updatedAt ? 'THÁNG '.$updatedAt->format('n/Y') : '' }}</div>
                <h2>Giá xe Lexus theo từng phiên bản.</h2>
            </div>
            <p class="small">
                {{ $priceProducts->count() }} dòng xe · {{ $priceProducts->sum(fn ($p) => $p->variants->count()) }} phiên bản.
                Giá đã gồm VAT, chưa gồm lệ phí trước bạ và phí đăng ký.
                @if ($updatedAt)<br>Cập nhật <time datetime="{{ $updatedAt->toDateString() }}">{{ $updatedAt->format('d/m/Y') }}</time>.@endif
            </p>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <caption class="studio-sr">Bảng giá xe Lexus tại {{ catalog_setting('site_name', 'Lexus Thăng Long') }}</caption>
                <thead>
                    <tr>
                        <th scope="col">Dòng xe</th>
                        <th scope="col">Phiên bản</th>
                        <th scope="col">Giá niêm yết</th>
                        <th scope="col"><span class="studio-sr">Báo giá</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($priceProducts as $product)
                        @foreach ($product->variants as $variant)
                            <tr>
                                @if ($loop->first)
                                    <th scope="rowgroup" rowspan="{{ $product->variants->count() }}">
                                        <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                                        @if ($product->category)<small>{{ $product->category->name }}</small>@endif
                                    </th>
                                @endif
                                <td>{{ $variant->name }}@if (filled($variant->note))<small>{{ $variant->note }}</small>@endif</td>
                                <td class="price-cell">{{ catalog_money($variant->price) ?: 'Đang cập nhật' }}</td>
                                <td>
                                    <a class="text-link" href="{{ route('quote', ['xe' => $product->slug, 'phien-ban' => $variant->id]) }}"
                                       data-quote data-product="{{ $product->id }}"
                                       data-variant="{{ $variant->id }}" data-variant-name="{{ $variant->name }}">Giá lăn bánh</a>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
