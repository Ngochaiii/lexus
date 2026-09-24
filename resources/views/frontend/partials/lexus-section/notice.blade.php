{{--
    Mục thông báo: dải viền đồng của bản thiết kế (.notice trong style.css).

    Tên mục hiện ĐÚNG MỘT LẦN, nằm trong hộp — không lặp lại thành <h2> ở
    trên như các mục khác. Class `notice__label` là móc CSS cho nhãn đó.
--}}
<div class="container" id="muc-{{ $index }}">
    <div class="notice">
        @if ($title)<span class="notice__label">{{ $title }}</span>@endif
        {!! catalog_rich_text($section['body'] ?? $intro ?? '') !!}
    </div>
</div>
