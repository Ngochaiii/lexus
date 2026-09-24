{{--
    Kiểu mặc định: ảnh kèm nhãn/mô tả tuỳ chọn, bố cục theo `layout`.

    Bốn bố cục dựng theo bản thiết kế cần markup riêng chứ không chỉ CSS:
      carousel — một ảnh lớn, mũi tên chuyển, có nhãn và bộ đếm
      tabs     — tab đánh số 01/02/03, mỗi tab một ảnh + tiêu đề + mô tả
      gallery  — 1 ảnh to bên trái, 2 ảnh nhỏ xếp chồng bên phải
      split    — đoạn mở đầu một bên, ảnh một bên

    Tắt JS: carousel hiện ảnh đầu rồi các ảnh sau xếp dọc, tabs hiện tất cả
    — vẫn đọc được hết, không có nút bấm chết.
--}}
@php
    $layout = $section['layout'] ?? 'cols-3';
    $items  = collect($section['items'] ?? [])->values();
@endphp

@if ($layout === 'carousel' && $items->count() > 1)
    <div class="gallery" data-gallery>
        <div class="gallery__stage">
            @foreach ($items as $i => $item)
                {{-- Nhãn và mô tả của slide để ở `data-gal-title` / `data-gal-desc`,
                     KHÔNG dùng lại tên `data-gal-label` của ô hiển thị bên dưới:
                     trùng tên thì querySelector bắt trúng chính figure này rồi
                     ghi đè textContent, xoá luôn cả ảnh bên trong. --}}
                <figure class="gallery__slide {{ $i === 0 ? 'is-on' : '' }}"
                        data-gal-slide
                        data-gal-title="{{ $item['label'] ?? '' }}"
                        data-gal-desc="{{ $item['desc'] ?? '' }}"
                        aria-hidden="{{ $i === 0 ? 'false' : 'true' }}">
                    @if ($src = catalog_image($item['image'] ?? null))
                        <x-img :src="$src" :alt="$item['label'] ?? ''" sizes="(max-width: 960px) 100vw, 66vw" />
                    @else
                        <div class="ph" style="height:100%">[ {{ $item['label'] ?? 'ảnh' }} ]</div>
                    @endif
                </figure>
            @endforeach
        </div>

        <div class="gallery__bar">
            <div class="gallery__text">
                <span class="gallery__label" data-gal-label>{{ $items->first()['label'] ?? '' }}</span>
                <p class="gallery__desc" data-gal-desc-out>{{ $items->first()['desc'] ?? '' }}</p>
            </div>
            <div class="gallery__controls">
                <span class="gallery__count" data-gal-count>01 / {{ str_pad($items->count(), 2, '0', STR_PAD_LEFT) }}</span>
                <button type="button" class="arrow arrow--line" data-gal-prev aria-label="Ảnh trước">‹</button>
                <button type="button" class="arrow arrow--line" data-gal-next aria-label="Ảnh sau">›</button>
            </div>
        </div>
    </div>

@elseif ($layout === 'tabs' && $items->count() > 1)
    <div class="tabs" data-tabs>
        <div class="tabs__nav" role="tablist">
            @foreach ($items as $i => $item)
                <button type="button" class="tabs__tab {{ $i === 0 ? 'is-on' : '' }}"
                        data-tab="{{ $i }}" role="tab" aria-selected="{{ $i === 0 ? 'true' : 'false' }}">
                    <span class="tabs__num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    {{ $item['label'] ?? '' }}
                </button>
            @endforeach
        </div>

        @foreach ($items as $i => $item)
            <div class="tabs__panel {{ $i === 0 ? 'is-on' : '' }}" data-tab-panel role="tabpanel">
                <div class="tabs__media">
                    @if ($src = catalog_image($item['image'] ?? null))
                        <x-img :src="$src" :alt="$item['label'] ?? ''" sizes="(max-width: 960px) 100vw, 60vw" />
                    @else
                        <div class="ph" style="height:100%">[ {{ $item['label'] ?? 'ảnh' }} ]</div>
                    @endif
                </div>
                <div class="tabs__body">
                    @isset($item['label'])<h3>{{ $item['label'] }}</h3>@endisset
                    @isset($item['desc'])<p>{{ $item['desc'] }}</p>@endisset
                </div>
            </div>
        @endforeach
    </div>

@elseif ($layout === 'bleed' && $items->count())
    {{-- Băng ảnh tràn hết chiều ngang màn hình. Chỗ nghỉ mắt giữa hai section
         dày chữ; các hãng xe dùng để đổi nhịp trước khi vào phần tiếp theo. --}}
    @php $shot = $items->first(); @endphp
    <figure class="bleed" data-bleed>
        <div class="bleed__frame">
            @if ($src = catalog_image($shot['image'] ?? null))
                <x-img :src="$src" :alt="$shot['label'] ?? ($section['title'] ?? '')" sizes="100vw" />
            @else
                <div class="ph" style="height:100%">[ {{ $shot['label'] ?? 'ảnh' }} ]</div>
            @endif
        </div>

        @php
            // Nút hành động khai ở cấp mục (`cta_*`), không phải ở từng ảnh —
            // một băng ảnh chỉ có một lời mời hành động.
            $ctas = collect([
                ['label' => $section['cta_label'] ?? null,  'url' => $section['cta_url'] ?? null],
                ['label' => $section['cta2_label'] ?? null, 'url' => $section['cta2_url'] ?? null],
            ])->filter(fn ($c) => filled($c['label']) && filled($c['url']))->values();
        @endphp

        @if (filled($shot['label'] ?? null) || filled($shot['desc'] ?? null) || $ctas->isNotEmpty())
            <figcaption class="bleed__cap">
                <div class="wrap">
                    @if (filled($shot['label'] ?? null))<b>{{ $shot['label'] }}</b>@endif
                    @if (filled($shot['desc'] ?? null))<span class="bleed__desc">{{ $shot['desc'] }}</span>@endif

                    @if ($ctas->isNotEmpty())
                        <div class="bleed__actions">
                            @foreach ($ctas as $i => $cta)
                                <a class="btn {{ $i === 0 ? 'btn--light' : 'btn--ghost' }}"
                                   href="{{ $cta['url'] }}">{{ $cta['label'] }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </figcaption>
        @endif
    </figure>

@elseif ($layout === 'slider' && $items->count())
    {{-- Dải cuộn ngang. Khác `carousel` ở chỗ carousel đổi ảnh tại chỗ, còn
         dải này để khách kéo ngang xem liên tục — hợp khi có nhiều ảnh.

         Cuộn và bám mép là CSS thuần (scroll-snap), nên tắt JS vẫn kéo được;
         JS chỉ thêm hai nút bấm cho người dùng chuột. --}}
    <div class="hstrip" data-hstrip>
        <div class="hstrip__rail" data-hstrip-rail>
            @foreach ($items as $item)
                <figure class="hstrip__item">
                    <div class="hstrip__media">
                        @if ($src = catalog_image($item['image'] ?? null))
                            <x-img :src="$src" :alt="$item['label'] ?? ''" sizes="(max-width: 680px) 82vw, 46vw" />
                        @else
                            <div class="ph" style="height:100%">[ {{ $item['label'] ?? 'ảnh' }} ]</div>
                        @endif
                    </div>

                    @if (filled($item['label'] ?? null) || filled($item['desc'] ?? null))
                        <figcaption>
                            @if (filled($item['label'] ?? null))<b>{{ $item['label'] }}</b>@endif
                            @if (filled($item['desc'] ?? null))<span>{{ $item['desc'] }}</span>@endif
                        </figcaption>
                    @endif
                </figure>
            @endforeach
        </div>

        <div class="hstrip__nav" data-hstrip-nav hidden>
            <button type="button" class="arrow arrow--line" data-hstrip-prev aria-label="Ảnh trước">‹</button>
            <button type="button" class="arrow arrow--line" data-hstrip-next aria-label="Ảnh sau">›</button>
        </div>
    </div>

@elseif ($layout === 'feature-rows' && $items->count())
    {{-- Nhiều điểm nhấn trong MỘT mục, thay vì mỗi ý một mục `split` riêng.

         Người nhập chỉ thêm ảnh vào danh sách; hàng nào đảo bên, hàng nào to
         hơn là việc của frontend. Trước đây phải tạo ba mục và tự nhớ chọn
         split / split-alt xen kẽ — sai một cái là cả trang lệch nhịp. --}}
    <div class="frows">
        @foreach ($items as $i => $item)
            <div class="frow {{ $i === 0 ? 'frow--lead' : '' }}">
                <div class="frow__media">
                    @if ($src = catalog_image($item['image'] ?? null))
                        <x-img :src="$src" :alt="$item['label'] ?? ''"
                               sizes="(max-width: 960px) 100vw, {{ $i === 0 ? '62vw' : '52vw' }}" />
                    @else
                        <div class="ph" style="height:100%">[ {{ $item['label'] ?? 'ảnh' }} ]</div>
                    @endif
                </div>

                <div class="frow__body">
                    <span class="frow__num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    @if (filled($item['label'] ?? null))<h3>{{ $item['label'] }}</h3>@endif
                    @if (filled($item['desc'] ?? null))<p>{{ $item['desc'] }}</p>@endif
                </div>
            </div>
        @endforeach
    </div>

@elseif ($layout === 'sticky' && $items->count())
    {{-- Ảnh đứng yên một bên, các đoạn nội dung cuộn qua bên kia; ảnh đổi
         theo đoạn đang đọc. Không có JS thì mọi ảnh xếp dọc cạnh đoạn của nó,
         vẫn đọc được hết. --}}
    <div class="scrolly" data-scrolly>
        <div class="scrolly__media">
            @foreach ($items as $i => $item)
                <figure class="scrolly__shot {{ $i === 0 ? 'is-on' : '' }}" data-scrolly-shot="{{ $i }}">
                    @if ($src = catalog_image($item['image'] ?? null))
                        <x-img :src="$src" :alt="$item['label'] ?? ''" sizes="(max-width: 960px) 100vw, 55vw" />
                    @else
                        <div class="ph" style="height:100%">[ {{ $item['label'] ?? 'ảnh' }} ]</div>
                    @endif
                </figure>
            @endforeach
        </div>

        <div class="scrolly__steps">
            @foreach ($items as $i => $item)
                <div class="scrolly__step {{ $i === 0 ? 'is-on' : '' }}" data-scrolly-step="{{ $i }}">
                    <span class="scrolly__num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    @if (filled($item['label'] ?? null))<h3>{{ $item['label'] }}</h3>@endif
                    @if (filled($item['desc'] ?? null))<p>{{ $item['desc'] }}</p>@endif
                </div>
            @endforeach
        </div>
    </div>

@elseif ($layout === 'hotspot' && $items->count())
    {{-- Ảnh nền là ảnh của mục đầu tiên; mỗi mục sau là một chấm đặt theo
         toạ độ phần trăm (x, y) người nhập điền trong admin. Chấm nào thiếu
         toạ độ thì bỏ qua thay vì dồn hết về góc trái trên. --}}
    @php
        $base = $items->first();
        $pins = $items->slice(1)->filter(fn ($p) => isset($p['x'], $p['y']) && $p['x'] !== '' && $p['y'] !== '')->values();
    @endphp
    <div class="hotspot" data-hotspot>
        <div class="hotspot__media">
            @if ($src = catalog_image($base['image'] ?? null))
                <x-img :src="$src" :alt="$base['label'] ?? ($section['title'] ?? '')" sizes="(max-width: 960px) 100vw, 1232px" />
            @else
                <div class="ph" style="aspect-ratio:16/9">[ {{ $base['label'] ?? 'ảnh' }} ]</div>
            @endif

            @foreach ($pins as $i => $pin)
                <button type="button"
                        class="hotspot__pin"
                        data-hotspot-pin="{{ $i }}"
                        style="left: {{ (float) $pin['x'] }}%; top: {{ (float) $pin['y'] }}%"
                        aria-expanded="false"
                        aria-label="{{ $pin['label'] ?? 'Điểm '.($i + 1) }}">
                    <span class="hotspot__dot" aria-hidden="true"></span>
                    <span class="hotspot__tip">
                        @if (filled($pin['label'] ?? null))<b>{{ $pin['label'] }}</b>@endif
                        @if (filled($pin['desc'] ?? null))<span>{{ $pin['desc'] }}</span>@endif
                    </span>
                </button>
            @endforeach
        </div>

        {{-- Không có JS/chuột thì danh sách này là bản đọc được của các chấm. --}}
        @if ($pins->isNotEmpty())
            <ul class="hotspot__list">
                @foreach ($pins as $pin)
                    <li>
                        @if (filled($pin['label'] ?? null))<b>{{ $pin['label'] }}</b>@endif
                        @if (filled($pin['desc'] ?? null))<span>{{ $pin['desc'] }}</span>@endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

@elseif (in_array($layout, ['split', 'split-alt'], true))
    {{-- Chữ một cột, ảnh một cột. `split-alt` đảo bên: ảnh trước, chữ sau. --}}
    <div class="items layout-split {{ $layout === 'split-alt' ? 'split--media-first' : '' }}">
        <div class="split__body">
            @isset($section['title'])<h2>{{ $section['title'] }}</h2>@endisset
            @isset($section['intro'])<p>{{ $section['intro'] }}</p>@endisset
        </div>

        @php $item = $items->first() ?? []; @endphp
        <div class="item__media">
            @if ($src = catalog_image($item['image'] ?? null))
                <x-img :src="$src" :alt="$item['label'] ?? ($section['title'] ?? '')" sizes="(max-width: 960px) 100vw, 55vw" />
            @else
                <div class="ph" style="height:100%">[ {{ $item['label'] ?? 'ảnh' }} ]</div>
            @endif
        </div>
    </div>

@else
    <div class="items layout-{{ $layout }}">
        @foreach ($items as $item)
            <figure class="item">
                <div class="item__media">
                    @if ($src = catalog_image($item['image'] ?? null))
                        <x-img :src="$src" :alt="$item['label'] ?? ''" sizes="(max-width: 680px) 100vw, (max-width: 1180px) 50vw, 420px" />
                    @else
                        <div class="ph" style="height:100%">[ {{ $item['label'] ?? 'ảnh' }} ]</div>
                    @endif
                </div>

                @isset($item['label'])
                    <figcaption>{{ $item['label'] }}</figcaption>
                @endisset

                @isset($item['desc'])
                    <p>{{ $item['desc'] }}</p>
                @endisset
            </figure>
        @endforeach
    </div>
@endif
