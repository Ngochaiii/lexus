{{-- Khối thông báo: vạch màu bên trái, nền kem, nhãn nhỏ rồi tới nội dung.
     Tên mục làm nhãn bên trong hộp nên sections.blade.php không dựng
     .section__head cho kiểu này (xem $headInside). --}}
<div class="notice">
    @isset($section['title'])
        <span class="notice__label">{{ $section['title'] }}</span>
    @endisset

    @isset($section['intro'])
        <p class="notice__intro">{{ $section['intro'] }}</p>
    @endisset

    <div class="notice__body prose">{!! catalog_rich_text($section['body'] ?? '') !!}</div>
</div>
