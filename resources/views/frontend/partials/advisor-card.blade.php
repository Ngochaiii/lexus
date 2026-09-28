{{--
    Khối chuyên viên trên trang đích quảng cáo (trang xe, phiên bản, bảng giá,
    liên hệ, tài chính): ảnh, tên, kinh nghiệm, Gọi / Zalo / Nhận báo giá, và
    lời mời mua trả góp.

    Vì sao cần: quảng cáo Google của chuyên viên ghi "10 Năm Kinh Nghiệm Bán
    Ô Tô", "Phương Án Trả Góp" — trang đích phải có đúng thông tin đó, nếu không
    Google coi là tuyên bố không có căn cứ và hạ điểm liên quan trang đích.

    Biến (đều tuỳ chọn):
      $car         Product đang xem → nút báo giá chọn sẵn xe, câu mời ghi tên xe
      $variant     ProductVariant đang xem → báo giá chọn sẵn phiên bản
      $hideFinance true trên chính trang tài chính
--}}
@php
    $advisor    = catalog_setting('advisor_name');
    $role       = catalog_setting('advisor_role', 'Chuyên viên tư vấn');
    $experience = catalog_setting('advisor_experience');
    $phone      = catalog_setting('advisor_phone') ?: catalog_setting('hotline');
    $zaloRaw    = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
    $zalo       = $zaloRaw ? (\Illuminate\Support\Str::startsWith($zaloRaw, 'http') ? $zaloRaw : 'https://zalo.me/'.$zaloRaw) : null;
    $photo      = catalog_image(catalog_setting('advisor_image'));
    $car        = $car ?? null;
    $variant    = $variant ?? null;
    // "Lexus ES 350h Premium" (phiên bản) hoặc "Lexus ES" (dòng xe) — như JsonLd::forVariant.
    $carName    = $variant
        ? (str_starts_with($variant->name, 'Lexus') ? $variant->name : 'Lexus '.$variant->name)
        : $car?->name;
    $quoteQuery = array_filter(['xe' => $car?->slug, 'phien-ban' => $variant?->id]);
@endphp

@if (filled($advisor) && ! catalog_setting('advisor_off'))
    <section class="advisor-card-band" aria-label="Chuyên viên tư vấn {{ $advisor }}">
        <div class="container advisor-card">
            <div class="advisor-card__who">
                <img class="advisor-avatar" src="{{ $photo ?: asset('assets/personal/avatar-320.webp') }}"
                     @unless ($photo)
                         srcset="{{ asset('assets/personal/avatar-160.webp') }} 160w,
                                 {{ asset('assets/personal/avatar-320.webp') }} 320w"
                     @endunless
                     sizes="96px" width="320" height="320"
                     alt="Chân dung chuyên viên {{ $advisor }}" loading="lazy" decoding="async">
                <div>
                    <p class="eyebrow">NGƯỜI TƯ VẤN CỦA BẠN</p>
                    <strong>{{ $advisor }}</strong>
                    <span>{{ $role }}</span>
                    @if (filled($experience))<span class="advisor-card__exp">{{ $experience }}</span>@endif
                </div>
            </div>

            <div class="advisor-card__offer">
                <p>
                    Báo giá lăn bánh {{ $carName ?: 'Lexus' }} theo đúng phiên bản bạn chọn, phương án mua trả góp
                    và lịch lái thử tại showroom — {{ $advisor }} trực tiếp tư vấn, miễn phí.
                </p>
                <div class="advisor-card__actions">
                    @if ($phone)<a class="button" href="tel:{{ $phone }}">Gọi {{ \App\Support\Phone::format($phone) }}</a>@endif
                    @if ($zalo)<a class="button outline" href="{{ $zalo }}" rel="noopener" target="_blank">Nhắn Zalo</a>@endif
                    <a class="button outline" href="{{ route('quote', $quoteQuery) }}" data-quote
                       @if ($car) data-product="{{ $car->id }}" @endif
                       @if ($variant) data-variant="{{ $variant->id }}" data-variant-name="{{ $variant->name }}" @endif>Nhận báo giá</a>
                </div>
                @unless ($hideFinance ?? false)
                    <a class="text-link advisor-card__finance" href="{{ route('pages.show', 'tai-chinh') }}">Mua {{ $carName ?: 'Lexus' }} trả góp: xem phương án vay</a>
                @endunless
            </div>
        </div>
    </section>
@endif
