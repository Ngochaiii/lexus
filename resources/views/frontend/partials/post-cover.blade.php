{{-- Ảnh bìa bài viết (LCP: không lazy). Biến: $post · $cover · $coverWidth --}}
@php
    $coverSize   = \App\Support\Media::dimensions($post->cover);
    $coverSrcset = \App\Support\Media::srcset($post->cover);
    $coverSizes  = [
        'narrow' => '(max-width: 860px) 100vw, 820px',
        'wide'   => '(max-width: 1440px) 100vw, 1440px',
        'full'   => '100vw',
    ][$coverWidth];
@endphp
<figure class="post-cover post-cover--{{ $coverWidth }}">
    <img src="{{ $cover }}" alt="{{ $post->title }}"
         @if ($coverSrcset) srcset="{{ $coverSrcset }}" sizes="{{ $coverSizes }}" @endif
         width="{{ $coverSize['w'] ?? 1600 }}" height="{{ $coverSize['h'] ?? 1067 }}" fetchpriority="high" decoding="async">
</figure>
