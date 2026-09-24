{{-- Mục văn bản: dùng khối .article rộng 820px như trang bài viết. --}}
<section class="container section text-section" id="muc-{{ $index }}">
    <div class="section-heading">
        <div>
            @if (filled($title))<div class="eyebrow">{{ ($numbered ?? true) ? $number.' / ' : '' }}{{ mb_strtoupper($title) }}</div>@endif
            @if ($intro)<h2>{{ $intro }}</h2>@endif
        </div>
    </div>
    @if (filled($section['body'] ?? null))
        {{-- catalog_rich_text() dọn HTML (bỏ <script>, onclick, link javascript:,
             thêm rel cho link ngoài) và vẫn giữ xuống dòng của nội dung cũ
             nhập bằng ô văn bản thuần. Không dùng e() vì sẽ mất hết định dạng
             khi người nhập dán từ Word. --}}
        <article class="article" style="margin:0">
            {!! catalog_rich_text($section['body']) !!}
        </article>
    @endif
</section>
