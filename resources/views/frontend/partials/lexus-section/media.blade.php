{{--
    Mục ảnh — bố cục quyết định dùng khối nào của bản thiết kế.

    Quy ước chung cho mọi bố cục có chữ:
      title  → eyebrow (dòng nhỏ chữ hoa)
      intro  → h2
      body   → đoạn mô tả
      cta_*  → nút / text-link

    $numbered (mặc định true) thêm tiền tố "01 /" vào eyebrow. Trang chi tiết
    xe đánh số theo bản thiết kế; trang chủ và trang tĩnh thì không.
--}}
@php
    $img      = fn ($item) => catalog_image($item['image'] ?? null);
    $first    = $items[0] ?? null;

    // Ảnh gốc hẹp hơn khung (ảnh tham khảo của một số dòng xe chỉ ~500px)
    // thì KHÔNG kéo giãn cho vỡ: đặt ở kích thước thật, đóng khung trên nền
    // đá. Kích thước đọc từ manifest của `php artisan catalog:images`.
    $isSmall = function ($item, int $min = 1000): bool {
        $d = \App\Support\Media::dimensions($item['image'] ?? null);

        return $d !== null && $d['w'] < $min;
    };
    $body     = $section['body'] ?? null;
    $numbered = $numbered ?? true;

    $eyebrow = trim(($numbered ? $number.' / ' : '').mb_strtoupper($title ?? ''));

    $ctaUrl   = $section['cta_url'] ?? null;
    $ctaLabel = $section['cta_label'] ?? null;
    $cta2Url  = $section['cta2_url'] ?? null;
    $cta2Label = $section['cta2_label'] ?? null;
@endphp

@if (in_array($layout, ['split', 'split-alt'], true) && $first)
    <section class="split {{ $layout === 'split-alt' ? 'reverse' : '' }} {{ $index % 2 === 0 ? 'stone' : '' }}"
             id="muc-{{ $index }}">
        @if ($layout === 'split-alt')
            <div class="split-copy">
                @if (filled($eyebrow))<div class="eyebrow">{{ $eyebrow }}</div>@endif
                @if ($intro)<h2>{!! nl2br(e($intro)) !!}</h2>@endif
                @if (filled($body))<p>{!! catalog_rich_text($body) !!}</p>@endif
                @if (filled($ctaUrl))<a class="text-link" href="{{ $ctaUrl }}">{{ $ctaLabel ?: 'Tìm hiểu thêm' }}</a>@endif
            </div>
            <div class="split-media {{ $isSmall($first) ? 'is-small' : '' }}">
                <x-img :src="$first['image'] ?? null" :alt="$first['label'] ?? $title" :eager="$eager ?? false"
                       sizes="(max-width: 600px) 100vw, 50vw" />
            </div>
        @else
            <div class="split-media {{ $isSmall($first) ? 'is-small' : '' }}">
                <x-img :src="$first['image'] ?? null" :alt="$first['label'] ?? $title" :eager="$eager ?? false"
                       sizes="(max-width: 600px) 100vw, 50vw" />
            </div>
            <div class="split-copy">
                @if (filled($eyebrow))<div class="eyebrow">{{ $eyebrow }}</div>@endif
                @if ($intro)<h2>{!! nl2br(e($intro)) !!}</h2>@endif
                @if (filled($body))<p>{!! catalog_rich_text($body) !!}</p>@endif
                @if (filled($ctaUrl))<a class="text-link" href="{{ $ctaUrl }}">{{ $ctaLabel ?: 'Tìm hiểu thêm' }}</a>@endif
            </div>
        @endif
    </section>

@elseif ($layout === 'bleed' && $first)
    <section class="story" id="muc-{{ $index }}">
        <x-img :src="$first['image'] ?? null" :alt="$first['label'] ?? $title" sizes="100vw" :eager="$eager ?? false" />
        <div class="story-content">
            @if (filled($eyebrow))<div class="eyebrow">{{ $eyebrow }}</div>@endif
            @if ($intro)<h2>{!! nl2br(e($intro)) !!}</h2>@endif
            @if (filled($body))<p>{!! catalog_rich_text($body) !!}</p>@endif
            @if (filled($ctaUrl) || filled($cta2Url))
                <div class="actions">
                    @if (filled($ctaUrl))<a class="text-link" href="{{ $ctaUrl }}">{{ $ctaLabel ?: 'Khám phá' }}</a>@endif
                    @if (filled($cta2Url))<a class="text-link" href="{{ $cta2Url }}">{{ $cta2Label ?: 'Xem thêm' }}</a>@endif
                </div>
            @endif
        </div>
    </section>

@elseif ($layout === 'shortcuts')
    {{-- Lưới ô bấm đánh số. Ảnh không dùng — chỉ Nhãn + Mô tả + Link. --}}
    <section class="container section" id="muc-{{ $index }}">
        <div class="section-heading">
            <div>
                @if (filled($eyebrow))<div class="eyebrow">{{ $eyebrow }}</div>@endif
                @if ($intro)<h2>{!! nl2br(e($intro)) !!}</h2>@endif
            </div>
        </div>
        <div class="shortcut-grid">
            @foreach ($items as $i => $item)
                @php $href = $item['url'] ?? null; @endphp
                @if ($href)
                    <a class="shortcut" href="{{ $href }}">
                        <span class="number">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        @if (filled($item['label'] ?? null))<h3>{{ $item['label'] }}</h3>@endif
                        @if (filled($item['desc'] ?? null))<p>{{ $item['desc'] }}</p>@endif
                    </a>
                @else
                    <div class="shortcut">
                        <span class="number">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        @if (filled($item['label'] ?? null))<h3>{{ $item['label'] }}</h3>@endif
                        @if (filled($item['desc'] ?? null))<p>{{ $item['desc'] }}</p>@endif
                    </div>
                @endif
            @endforeach
        </div>
    </section>

@elseif ($layout === 'gallery')
    {{-- Thư viện: lưới ảnh, bấm vào phóng lớn bằng :target — không JS. --}}
    <section class="container section" id="muc-{{ $index }}">
        <div class="section-heading">
            <div>
                @if (filled($eyebrow))<div class="eyebrow">{{ $eyebrow }}</div>@endif
                @if ($intro)<h2>{!! nl2br(e($intro)) !!}</h2>@endif
            </div>
        </div>
        <div class="gallery-grid">
            @foreach ($items as $i => $item)
                @continue (! ($src = $img($item)))
                <a href="#anh-{{ $index }}-{{ $i }}" aria-label="Mở ảnh {{ $i + 1 }}">
                    <x-img :src="$item['image']" :alt="$item['label'] ?? ($title.' — ảnh '.($i + 1))"
                           :sizes="$i === 0 ? '(max-width: 600px) 100vw, 50vw' : '(max-width: 600px) 50vw, 25vw'" />
                </a>
            @endforeach
        </div>
    </section>

    @foreach ($items as $i => $item)
        @continue (! ($src = $img($item)))
        <div class="lightbox" id="anh-{{ $index }}-{{ $i }}" role="region" aria-label="Ảnh phóng lớn">
            <a class="close" href="#muc-{{ $index }}" aria-label="Đóng ảnh">×</a>
            <img src="{{ $src }}" alt="{{ $item['label'] ?? 'Ảnh phóng lớn '.($i + 1) }}"
                 width="1600" height="1067" loading="lazy" decoding="async">
        </div>
    @endforeach

@elseif ($layout === 'groups')
    {{-- Thẻ chia nhóm theo tab (Mâm xe / Nội thất / Ốp trang trí…). Nhóm =
         ô "eyebrow" của từng ảnh. Tab là radio + CSS (xem .og-* trong
         style.css) — không JS, phím mũi tên đổi tab như radio thường. --}}
    @php
        $groups = collect($items)->filter(fn ($i) => filled($i['image'] ?? null))
            ->groupBy(fn ($i) => $i['eyebrow'] ?? 'Chi tiết')->take(8);
        $gid = 'nhom-'.$index;
    @endphp
    @if ($groups->isNotEmpty())
        <section class="section stone" id="muc-{{ $index }}">
            <div class="container">
                <div class="section-heading">
                    <div>
                        @if (filled($eyebrow))<div class="eyebrow">{{ $eyebrow }}</div>@endif
                        @if ($intro)<h2>{!! nl2br(e($intro)) !!}</h2>@endif
                    </div>
                    @if (filled($body))<p>{{ strip_tags($body) }}</p>@endif
                </div>
                <div class="og">
                    @foreach ($groups as $name => $group)
                        <input class="og-radio" type="radio" name="{{ $gid }}" id="{{ $gid }}-{{ $loop->index }}"
                               @checked($loop->first) aria-label="{{ $name }}">
                    @endforeach
                    <div class="og-tabs" aria-hidden="true">
                        @foreach ($groups as $name => $group)
                            <label for="{{ $gid }}-{{ $loop->index }}">{{ $name }}<small>{{ $group->count() }}</small></label>
                        @endforeach
                    </div>
                    <div class="og-panels">
                        @foreach ($groups as $name => $group)
                            <div class="og-panel">
                                @foreach ($group as $item)
                                    <figure class="og-card">
                                        <div class="og-media">
                                            <x-img :src="$item['image']" :alt="($item['label'] ?? $name).(filled($item['desc'] ?? null) ? ' — '.$item['desc'] : '')"
                                                   sizes="(max-width: 600px) 100vw, 33vw" />
                                        </div>
                                        <figcaption>
                                            @if (filled($item['label'] ?? null))<h3>{{ $item['label'] }}</h3>@endif
                                            @if (filled($item['desc'] ?? null))<p>{{ $item['desc'] }}</p>@endif
                                        </figcaption>
                                    </figure>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

@elseif ($layout === 'cols-2')
    {{-- Thẻ editorial: ảnh + nhãn + mô tả + link riêng, hai cột. --}}
    <section class="section stone" id="muc-{{ $index }}">
        <div class="container">
            <div class="section-heading">
                <div>
                    @if (filled($eyebrow))<div class="eyebrow">{{ $eyebrow }}</div>@endif
                    @if ($intro)<h2>{!! nl2br(e($intro)) !!}</h2>@endif
                </div>
                @if (filled($ctaUrl))
                    <a class="text-link" href="{{ $ctaUrl }}">{{ $ctaLabel ?: 'Xem thêm' }}</a>
                @endif
            </div>
            <div class="editorial-grid">
                @foreach ($items as $item)
                    <article class="editorial-card">
                        <x-img :src="$item['image'] ?? null" :alt="$item['label'] ?? $title"
                               sizes="(max-width: 600px) 100vw, 50vw" />
                        @if (filled($item['eyebrow'] ?? null))
                            <div class="eyebrow">{{ mb_strtoupper($item['eyebrow']) }}</div>
                        @endif
                        @if (filled($item['label'] ?? null))<h3>{{ $item['label'] }}</h3>@endif
                        @if (filled($item['desc'] ?? null))<p>{{ $item['desc'] }}</p>@endif
                        @if (filled($item['url'] ?? null))
                            <a class="text-link" href="{{ $item['url'] }}">Tìm hiểu thêm</a>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>

@else
    {{-- cols-1/3 và slider (không JS nên về lưới). Mỗi ảnh là một thẻ:
         ảnh · nhãn · mô tả — dùng cho "Công nghệ an toàn" ở trang xe, nơi
         mỗi tính năng cần một câu giải thích chứ không chỉ cái tên. --}}
    <section class="container section" id="muc-{{ $index }}">
        <div class="section-heading">
            <div>
                @if (filled($eyebrow))<div class="eyebrow">{{ $eyebrow }}</div>@endif
                @if ($intro)<h2>{!! nl2br(e($intro)) !!}</h2>@endif
            </div>
            @if (filled($body))<p>{{ strip_tags($body) }}</p>@endif
        </div>
        <div class="news-grid feature-grid">
            @foreach ($items as $item)
                <article class="news-card feature-card">
                    @if (filled($item['image'] ?? null))
                        <x-img :src="$item['image']" :alt="$item['label'] ?? $title"
                               sizes="(max-width: 600px) 100vw, 33vw" />
                    @endif
                    @if (filled($item['eyebrow'] ?? null))
                        <div class="meta">{{ mb_strtoupper($item['eyebrow']) }}</div>
                    @endif
                    @if (filled($item['label'] ?? null))<h3>{{ $item['label'] }}</h3>@endif
                    @if (filled($item['desc'] ?? null))<p>{{ $item['desc'] }}</p>@endif
                </article>
            @endforeach
        </div>
    </section>
@endif
