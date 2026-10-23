{{--
    Bảng giá toàn bộ phiên bản — trang /bang-gia.

    Dựng từ bảng xe + phiên bản chứ không nhập tay trong trang tĩnh: admin
    sửa giá một chỗ (màn hình Xe) là bảng giá, trang xe và JSON-LD cùng đổi,
    không bao giờ lệch nhau.

    Một bảng HTML thật (<table> + <caption> + <th scope>) thay vì thẻ rời:
    người đọc lướt dễ so sánh, còn Google và các công cụ AI đọc được đúng
    cặp "phiên bản → giá" để trích dẫn.

    Trang đích quảng cáo "giá xe lexus": khách xem giá niêm yết là đi, nên
    bảng hiện luôn lăn bánh tạm tính (như trang xe) và nút cuối dòng mở Zalo
    chuyên viên — hỏi lăn bánh bằng một chạm, không phải điền form; nút Gọi
    đứng trước cho khách thích gọi thẳng (23/10/2026). Chưa cài
    Zalo thì quay về form báo giá.
--}}
@php
    $priceProducts = \App\Support\Catalog::query('product')->published()
        ->with(['variants' => fn ($q) => $q->orderBy('sort'), 'category'])
        ->orderBy('price_from')->get()
        ->filter(fn ($p) => $p->variants->isNotEmpty())
        // Xe có giá lên trước theo giá; xe đang cập nhật giá xuống cuối.
        ->sortBy(fn ($p) => $p->price_from ?? PHP_INT_MAX);

    $updatedAt = $priceProducts->flatMap->variants->max('updated_at');

    $onRoad  = config('catalog.on_road');
    $advisor = catalog_setting('advisor_name', 'chuyên viên');
    $zaloRaw = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
    $zalo    = $zaloRaw ? (\Illuminate\Support\Str::startsWith($zaloRaw, 'http') ? $zaloRaw : 'https://zalo.me/'.$zaloRaw) : null;
    $phone   = catalog_setting('advisor_phone') ?: catalog_setting('hotline');
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
                Giá niêm yết đã gồm VAT. Lăn bánh tạm tính tại {{ $onRoad['region'] }} = giá niêm yết
                + lệ phí trước bạ {{ rtrim(rtrim(number_format($onRoad['tax_rate'] * 100, 1, ',', ''), '0'), ',') }}% (xe điện chạy pin 0%)
                + biển số {{ catalog_money_short($onRoad['plate_fee']) }}; chưa gồm đăng kiểm, phí đường bộ, bảo hiểm.
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
                        <th scope="col">Lăn bánh {{ $onRoad['region'] }} <small>tạm tính</small></th>
                        <th scope="col"><span class="studio-sr">Báo giá lăn bánh</span></th>
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
                                <td>@if (filled($variant->slug))<a href="{{ \App\Support\Url::variant($product->slug, $variant->slug) }}">{{ $variant->name }}</a>@else{{ $variant->name }}@endif
                                    @if (filled($variant->note))<small>{{ $variant->note }}</small>@endif
                                    @if ($stock = \App\Support\Availability::label($variant))<span class="stock-badge stock-badge--{{ \App\Support\Availability::key($variant) }}">{{ $stock }}</span>@endif</td>
                                <td class="price-cell" data-label="Giá niêm yết">{{ catalog_money($variant->price) ?: 'Đang cập nhật' }}</td>
                                @php $estimate = \App\Support\OnRoadPrice::for($variant); @endphp
                                <td class="price-cell price-cell--road" data-label="Lăn bánh {{ $onRoad['region'] }} (tạm tính)">{{ $estimate ? catalog_money_short($estimate['total']) : '—' }}</td>
                                <td class="price-action">
                                    @if ($phone)
                                        <a class="text-link" href="tel:{{ $phone }}"
                                           aria-label="Gọi {{ $advisor }} hỏi giá lăn bánh {{ $variant->name }}">Gọi</a>
                                    @endif
                                    @if ($zalo)
                                        <a class="text-link" href="{{ $zalo }}" rel="noopener" target="_blank"
                                           aria-label="Nhắn Zalo {{ $advisor }} hỏi giá lăn bánh {{ $variant->name }}">Hỏi Zalo</a>
                                    @else
                                        <a class="text-link" href="{{ route('quote', ['xe' => $product->slug, 'phien-ban' => $variant->id]) }}"
                                           data-quote data-product="{{ $product->id }}"
                                           data-variant="{{ $variant->id }}" data-variant-name="{{ $variant->name }}">Giá lăn bánh</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
