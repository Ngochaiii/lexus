{{--
    Bộ render mục (`sections`) theo hệ CSS Lexus.

    Thay bản của cars-backend — bản đó dùng class của giao diện VinFast
    (`wrap`, `block`, `bleed`, `frows`…) không có trong style.css của Lexus,
    nên mục nào cũng hiện sai kiểu dáng.

    Mỗi mục người nhập tự đặt tên và chọn layout trong admin. Các layout
    dưới đây bám đúng những khối có sẵn của bản thiết kế:

      split / split-alt  → .split (ảnh một bên, chữ một bên)
      gallery            → .gallery-grid + phóng ảnh bằng :target
      cols-1/2/3, slider → .model-grid (slider không có JS nên về lưới)
      bleed              → .story (ảnh tràn, chữ đè lên)

    $sections là mảng đã qua renderableSections(). $index dùng để id ảnh
    phóng lớn không trùng nhau.

    $numbered (mặc định true) quyết định có thêm tiền tố "01 /" vào eyebrow
    hay không: trang chi tiết xe đánh số theo bản thiết kế, trang chủ và
    trang tĩnh thì không.

    Mỗi mục có tiêu đề được gắn thêm một neo theo slug của tiêu đề (ví dụ
    "Omotenashi" → #omotenashi), để link kiểu /the-gioi-lexus#omotenashi
    nhảy đúng chỗ. Neo trùng thì chỉ mục đầu tiên nhận.
--}}
@php
    $numbered = $numbered ?? true;
    $anchors  = [];
@endphp
@foreach ($sections as $index => $section)
    @php
        $type   = $section['type'] ?? 'media';
        $layout = $section['layout'] ?? 'cols-3';
        $title  = $section['title'] ?? null;
        $intro  = $section['intro'] ?? null;
        $items  = $section['items'] ?? [];
        $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
        $anchor = \Illuminate\Support\Str::slug((string) $title);
        $anchor = ($anchor !== '' && ! isset($anchors[$anchor])) ? $anchor : null;
        if ($anchor) {
            $anchors[$anchor] = true;
        }
    @endphp

    @if ($anchor)<span class="section-anchor" id="{{ $anchor }}"></span>@endif

    @includeIf('frontend.partials.lexus-section.'.$type, [
        'section' => $section,
        'layout'  => $layout,
        'title'   => $title,
        'intro'   => $intro,
        'items'   => $items,
        'number'   => $number,
        'index'    => $index,
        'numbered' => $numbered,
        'eager'    => ($eagerFirst ?? false) && $loop->first,
    ])
@endforeach
