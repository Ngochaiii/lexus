{{-- Mục video: nhúng iframe, không autoplay (đặc tả mục 19). --}}
@php $url = $section['video_url'] ?? null; @endphp
@if ($url)
    <section class="container section" id="muc-{{ $index }}">
        <div class="section-heading">
            <div>
                <div class="eyebrow">{{ $number }} / {{ mb_strtoupper($title ?? '') }}</div>
                @if ($intro)<h2>{{ $intro }}</h2>@endif
            </div>
        </div>
        @if (str_contains($url, 'youtube') || str_contains($url, 'youtu.be'))
            <iframe style="width:100%;aspect-ratio:16/9;border:0" loading="lazy"
                    src="{{ str_replace(['watch?v=', 'youtu.be/'], ['embed/', 'youtube.com/embed/'], $url) }}"
                    title="{{ $title }}" allowfullscreen></iframe>
        @else
            <video controls preload="none" style="width:100%"><source src="{{ $url }}"></video>
        @endif
    </section>
@endif
