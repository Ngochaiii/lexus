{{--
    Thẻ một bài viết trong lưới.

    $post là model Post (hoặc mảng cùng khoá, dùng cho bản cắt tĩnh).
    Chưa upload ảnh bìa thì lùi về ảnh của bản thiết kế.
--}}
@php
    $isModel = $post instanceof \Illuminate\Database\Eloquent\Model;

    $slug  = $isModel ? $post->slug  : $post['slug'];
    $title = $isModel ? $post->title : $post['title'];
    $cat   = $isModel ? ($post->category?->name ?? 'Lexus Journal') : ($post['category'] ?? '');
    $date  = $isModel ? $post->published_at?->format('d.m.Y') : ($post['date'] ?? '');

    $cover = $isModel ? $post->cover : null;
    $img   = ($isModel ? catalog_image($cover) : ($post['image'] ?? null))
        ?: asset('assets/co-so/mat-tien.webp');
@endphp
<article class="news-card">
    <a href="{{ route('posts.show', $slug) }}">
        {{-- Ảnh bìa trong kho → srcset (thẻ chỉ rộng ~1/3 màn hình, không tải bản 1600px). --}}
        @if (filled($cover) && catalog_image($cover))
            <x-img :src="$cover" :alt="$title" sizes="(max-width: 600px) 100vw, (max-width: 1100px) 50vw, 33vw" />
        @else
            <img src="{{ $img }}" alt="{{ $title }}"
                 width="1600" height="1067" loading="lazy" decoding="async">
        @endif
    </a>
    <div class="meta">{{ mb_strtoupper($cat) }}@if ($date) &nbsp; / &nbsp; {{ $date }}@endif</div>
    <h3><a href="{{ route('posts.show', $slug) }}">{{ $title }}</a></h3>
    <a class="text-link" href="{{ route('posts.show', $slug) }}">Đọc câu chuyện</a>
</article>
