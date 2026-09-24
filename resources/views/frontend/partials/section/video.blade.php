{{--
    Video nhúng. Link YouTube/Vimeo đổi sang dạng /embed (App\Support\Media),
    còn file .mp4 tự host thì dùng thẻ <video> để không cần player ngoài.
    Mục video vẫn có thể kèm ảnh như mục media — render nốt phía dưới.
--}}
@php
    $url = \App\Support\Media::embed($section['video_url'] ?? null);
@endphp

@if ($url)
    <div class="video-block">
        @if (\App\Support\Media::isFile($url))
            <video controls preload="metadata" src="{{ $url }}"></video>
        @else
            <iframe src="{{ $url }}" title="{{ $section['title'] ?? 'Video' }}"
                    loading="lazy" allowfullscreen
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture"></iframe>
        @endif
    </div>
@endif

@if (filled($section['items'] ?? null))
    @include('frontend.partials.section.media', ['section' => $section])
@endif
