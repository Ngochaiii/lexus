{{--
    Cột phải trang bài viết (desktop dính theo khi cuộn; mobile nằm sau bài).

      1. Chuyên viên: ảnh, tên, Gọi / Zalo / Nhận báo giá — khách đọc tới đâu
         cũng liên hệ được ngay, không phải cuộn xuống cuối bài.
      2. Giá dòng xe bài đang nói tới: niêm yết + lăn bánh tạm tính, cùng nguồn
         với bảng giá (không lệch số với thân bài), link sang trang phiên bản.
      3. Mục lục (thu gọn, bấm để mở): nhảy tới từng mục — chỉ desktop.

    Nằm SAU thân bài trong HTML: Google/AI đọc nội dung chính trước.
    Biến: $car (Product|null) · $toc (array)
--}}
@php
    $advisor   = catalog_setting('advisor_name');
    $role      = catalog_setting('advisor_role', 'Chuyên viên tư vấn');
    $phone     = catalog_setting('advisor_phone') ?: catalog_setting('hotline');
    $phoneFmt  = \App\Support\Phone::format($phone);
    $zaloRaw   = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
    $zalo      = $zaloRaw ? (\Illuminate\Support\Str::startsWith($zaloRaw, 'http') ? $zaloRaw : 'https://zalo.me/'.$zaloRaw) : null;
    $avatar    = catalog_image(catalog_setting('advisor_image'));
    $variants  = $car ? $car->variants->filter(fn ($v) => filled($v->price)) : collect();
@endphp
<aside class="post-aside" aria-label="Liên hệ chuyên viên và giá xe">
    <div class="post-aside__sticky">
        @if ($phone || $zalo)
            <section class="aside-card aside-advisor">
                <div class="aside-advisor__who">
                    <img src="{{ $avatar ?: asset('assets/personal/avatar-160.webp') }}"
                         @unless ($avatar) srcset="{{ asset('assets/personal/avatar-160.webp') }} 160w, {{ asset('assets/personal/avatar-320.webp') }} 320w" sizes="64px" @endunless
                         width="64" height="64" alt="{{ $advisor ? 'Chuyên viên '.$advisor : 'Chuyên viên tư vấn' }}" loading="lazy" decoding="async">
                    <div>
                        <p class="aside-eyebrow">Hỏi trực tiếp người viết</p>
                        @if ($advisor)<strong>{{ $advisor }}</strong>@endif
                        <span>{{ $role }}</span>
                        @if (filled($experience = catalog_setting('advisor_experience')))<span>{{ $experience }}</span>@endif
                    </div>
                </div>
                <p class="aside-note">Báo giá lăn bánh, phương án trả góp và lịch lái thử theo đúng phiên bản bạn chọn — miễn phí.</p>
                <div class="aside-actions">
                    @if ($phone)<a class="button" href="tel:{{ $phone }}">Gọi {{ $phoneFmt }}</a>@endif
                    @if ($zalo)<a class="button outline" href="{{ $zalo }}" rel="noopener" target="_blank">Nhắn Zalo</a>@endif
                    <a class="text-link" href="{{ route('quote', $car ? ['xe' => $car->slug] : []) }}" data-quote @if ($car) data-product="{{ $car->id }}" @endif>Nhận báo giá lăn bánh</a>
                </div>
            </section>
        @endif

        @if ($variants->isNotEmpty())
            <section class="aside-card aside-price">
                <p class="aside-eyebrow">Giá {{ $car->name }} tại Hà Nội</p>
                <ul>
                    @foreach ($variants as $variant)
                        @php $road = \App\Support\OnRoadPrice::for($variant); @endphp
                        <li>
                            @if (filled($variant->slug))
                                <a href="{{ \App\Support\Url::variant($car->slug, $variant->slug) }}">{{ $variant->name }}</a>
                            @else
                                <span>{{ $variant->name }}</span>
                            @endif
                            <strong>{{ catalog_money_short($variant->price) }}</strong>
                            @if ($road)<small>Lăn bánh khoảng {{ catalog_money_short($road['total']) }}</small>@endif
                        </li>
                    @endforeach
                </ul>
                <p class="aside-foot">Giá niêm yết đã gồm VAT; lăn bánh tạm tính gồm trước bạ và biển số Hà Nội.</p>
            </section>
        @endif

        @if (filled($toc))
            {{-- Thu gọn mặc định: cột dính theo phải vừa màn hình để thẻ chuyên
                 viên và giá luôn thấy; link vẫn có trong HTML cho Google/AI. --}}
            <nav class="aside-card aside-toc" aria-label="Mục lục bài viết">
                <details>
                    <summary class="aside-eyebrow">Trong bài này · {{ count($toc) }} mục</summary>
                    <ol>
                        @foreach ($toc as $item)
                            <li><a href="#{{ $item['id'] }}">{{ $item['title'] }}</a></li>
                        @endforeach
                    </ol>
                </details>
            </nav>
        @endif
    </div>
</aside>
